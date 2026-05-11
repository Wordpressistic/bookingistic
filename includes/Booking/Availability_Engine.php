<?php
/**
 * Availability engine — the heart of the plugin.
 *
 * Compute available start slots for a service on a given date, taking into account:
 *   - service duration + buffers
 *   - slot interval, min notice, max advance window
 *   - business working hours / working days (Settings)
 *   - site holidays (Settings)
 *   - staff working hours (per-day or legacy start/end+days)
 *   - staff days off
 *   - staff daily booking limit
 *   - existing overlapping bookings (double-booking guard)
 *
 * @package Bookingistic
 */

namespace Bookingistic\Booking;

use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Service_Repository;
use Bookingistic\Database\Staff_Repository;
use Bookingistic\Helpers\Date_Time;

defined( 'ABSPATH' ) || exit;

class Availability_Engine {

	/**
	 * @return array{slots:array<int,array<string,string>>, timezone:string} or WP_Error
	 */
	public static function for_service_date( int $service_id, string $date, int $staff_id = 0, string $customer_tz = '' ) {
		$service = Service_Repository::find( $service_id );
		if ( ! $service || $service['status'] !== 'active' ) {
			return new \WP_Error( 'service_not_found', __( 'Service not found.', 'bookingistic' ), [ 'status' => 404 ] );
		}
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return new \WP_Error( 'invalid_date', __( 'Invalid date.', 'bookingistic' ), [ 'status' => 422 ] );
		}

		$settings = get_option( 'bookingistic_settings', [] );
		$tz_str   = $customer_tz ?: ( $settings['timezone'] ?? wp_timezone_string() );
		try {
			$tz = new \DateTimeZone( $tz_str );
		} catch ( \Throwable $e ) {
			$tz = Date_Time::site_timezone();
		}

		try {
			$day_obj = new \DateTimeImmutable( $date, $tz );
		} catch ( \Throwable $e ) {
			return [ 'slots' => [], 'timezone' => $tz_str ];
		}
		$dow = (int) $day_obj->format( 'N' ); // 1 = Mon … 7 = Sun

		// Site-wide holidays block the date for everyone.
		if ( self::is_holiday( $date, (array) ( $settings['holidays'] ?? [] ) ) ) {
			return [ 'slots' => [], 'timezone' => $tz_str ];
		}

		// Resolve working window: staff overrides > business default.
		$window = self::resolve_working_window( $dow, $settings, $staff_id );
		if ( ! $window ) {
			return [ 'slots' => [], 'timezone' => $tz_str ];
		}

		// Staff-specific day-off?
		if ( $staff_id > 0 ) {
			$staff = Staff_Repository::find( $staff_id );
			if ( $staff && self::is_day_off( $date, $staff['days_off'] ) ) {
				return [ 'slots' => [], 'timezone' => $tz_str ];
			}
			// Daily booking limit?
			if ( $staff && $staff['booking_limit_per_day'] > 0 ) {
				$day_start_utc = ( new \DateTimeImmutable( $date . ' 00:00:00', $tz ) )->setTimezone( Date_Time::utc() )->format( 'Y-m-d H:i:s' );
				$day_end_utc   = ( new \DateTimeImmutable( $date . ' 00:00:00', $tz ) )->modify( '+1 day' )->setTimezone( Date_Time::utc() )->format( 'Y-m-d H:i:s' );
				if ( Booking_Repository::count_for_staff_day( $staff_id, $day_start_utc, $day_end_utc ) >= $staff['booking_limit_per_day'] ) {
					return [ 'slots' => [], 'timezone' => $tz_str ];
				}
			}
		}

		$candidates = Slot_Generator::for_day(
			$date,
			$window['start'],
			$window['end'],
			(int) $service['duration_minutes'],
			(int) $service['buffer_before'],
			(int) $service['buffer_after'],
			max( 5, (int) ( $settings['slot_interval'] ?? 15 ) ),
			$tz
		);

		$min_notice  = max( 0, (int) ( $settings['min_notice_hours'] ?? 4 ) );
		$max_advance = max( 1, (int) ( $settings['max_advance_days'] ?? 60 ) );
		$earliest    = ( new \DateTimeImmutable( 'now', Date_Time::utc() ) )->modify( '+' . $min_notice . ' hours' );
		$latest      = ( new \DateTimeImmutable( 'now', Date_Time::utc() ) )->modify( '+' . $max_advance . ' days' );

		$candidates = array_values(
			array_filter(
				$candidates,
				static function ( $slot ) use ( $earliest, $latest ) {
					$start = new \DateTimeImmutable( $slot['start_utc'], Date_Time::utc() );
					return $start >= $earliest && $start <= $latest;
				}
			)
		);

		if ( ! $candidates ) {
			return [ 'slots' => [], 'timezone' => $tz_str ];
		}

		$range_start = $candidates[0]['start_utc'];
		$range_end   = end( $candidates )['end_utc'];
		$existing    = Booking_Repository::in_range( $service_id, $staff_id, $range_start, $range_end );

		$available = [];
		foreach ( $candidates as $slot ) {
			$slot_start = strtotime( $slot['start_utc'] . ' UTC' );
			$slot_end   = strtotime( $slot['end_utc'] . ' UTC' );
			$conflict   = false;
			foreach ( $existing as $b ) {
				$b_start = strtotime( $b['start_datetime'] . ' UTC' );
				$b_end   = strtotime( $b['end_datetime'] . ' UTC' );
				if ( ! $b_start || ! $b_end ) {
					continue;
				}
				if ( $slot_start < $b_end && $slot_end > $b_start ) {
					$conflict = true;
					break;
				}
			}
			if ( ! $conflict ) {
				$available[] = $slot;
			}
		}

		return [
			'slots'    => apply_filters( 'bookingistic_available_slots', $available, $service, $date, $staff_id ),
			'timezone' => $tz_str,
		];
	}

	/**
	 * Resolve the working window for the given day-of-week (1=Mon..7=Sun).
	 *
	 * @return array{start:string,end:string}|null  null = closed that day.
	 */
	private static function resolve_working_window( int $dow, array $settings, int $staff_id ): ?array {
		// Business default.
		$work_days  = array_map( 'intval', (array) ( $settings['working_days'] ?? [ 1, 2, 3, 4, 5 ] ) );
		$work_start = $settings['working_start'] ?? '09:00';
		$work_end   = $settings['working_end'] ?? '17:00';

		if ( ! in_array( $dow, $work_days, true ) ) {
			$default = null;
		} else {
			$default = [ 'start' => $work_start, 'end' => $work_end ];
		}

		if ( $staff_id <= 0 ) {
			return $default;
		}

		$staff = Staff_Repository::find( $staff_id );
		if ( ! $staff ) {
			return $default;
		}

		$wh = $staff['working_hours'];
		if ( empty( $wh ) ) {
			return $default;
		}

		// Per-day shape: {"1": {"start":"...", "end":"..."}, ...}
		if ( isset( $wh[ (string) $dow ] ) || isset( $wh[ $dow ] ) ) {
			$entry = $wh[ (string) $dow ] ?? $wh[ $dow ];
			if ( empty( $entry ) || empty( $entry['start'] ) || empty( $entry['end'] ) ) {
				return null;
			}
			return [ 'start' => $entry['start'], 'end' => $entry['end'] ];
		}

		// Legacy shape: {"start":"09:00","end":"17:00","days":[1,2,3,4,5]}
		if ( isset( $wh['start'], $wh['end'] ) ) {
			$days = ! empty( $wh['days'] ) ? array_map( 'intval', (array) $wh['days'] ) : $work_days;
			if ( ! in_array( $dow, $days, true ) ) {
				return null;
			}
			return [ 'start' => $wh['start'], 'end' => $wh['end'] ];
		}

		return $default;
	}

	private static function is_day_off( string $date, array $days_off ): bool {
		if ( empty( $days_off ) ) {
			return false;
		}
		foreach ( $days_off as $entry ) {
			if ( is_array( $entry ) ) {
				$from = $entry['from'] ?? '';
				$to   = $entry['to'] ?? $from;
				if ( $from && $to && $date >= $from && $date <= $to ) {
					return true;
				}
			} elseif ( is_string( $entry ) && $date === $entry ) {
				return true;
			}
		}
		return false;
	}

	private static function is_holiday( string $date, array $holidays ): bool {
		return self::is_day_off( $date, $holidays );
	}
}
