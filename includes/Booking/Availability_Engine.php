<?php
/**
 * Availability engine — the heart of the plugin.
 *
 * Computes available start slots for a service on a given date, taking into account
 * working hours, buffers, slot interval, minimum notice, max advance window,
 * existing overlapping bookings, and (when set) staff working hours / days off.
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

		// Determine working hours: staff override > business default.
		$work_start = $settings['working_start'] ?? '09:00';
		$work_end   = $settings['working_end'] ?? '17:00';
		$work_days  = $settings['working_days'] ?? [ 1, 2, 3, 4, 5 ];

		if ( $staff_id > 0 ) {
			$staff = Staff_Repository::find( $staff_id );
			if ( $staff && ! empty( $staff['working_hours']['start'] ) ) {
				$work_start = $staff['working_hours']['start'];
			}
			if ( $staff && ! empty( $staff['working_hours']['end'] ) ) {
				$work_end = $staff['working_hours']['end'];
			}
			if ( $staff && ! empty( $staff['working_hours']['days'] ) ) {
				$work_days = $staff['working_hours']['days'];
			}
			if ( $staff && self::is_day_off( $date, $staff['days_off'] ) ) {
				return [ 'slots' => [], 'timezone' => $tz_str ];
			}
		}

		// Day-of-week check (1=Mon..7=Sun).
		try {
			$day_obj = new \DateTimeImmutable( $date, $tz );
		} catch ( \Throwable $e ) {
			return [ 'slots' => [], 'timezone' => $tz_str ];
		}
		$dow = (int) $day_obj->format( 'N' );
		if ( ! in_array( $dow, array_map( 'intval', (array) $work_days ), true ) ) {
			return [ 'slots' => [], 'timezone' => $tz_str ];
		}

		// Generate candidate slots.
		$candidates = Slot_Generator::for_day(
			$date,
			$work_start,
			$work_end,
			(int) $service['duration_minutes'],
			(int) $service['buffer_before'],
			(int) $service['buffer_after'],
			max( 5, (int) ( $settings['slot_interval'] ?? 15 ) ),
			$tz
		);

		// Filter by min notice + max advance window.
		$min_notice = max( 0, (int) ( $settings['min_notice_hours'] ?? 4 ) );
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

		// Fetch existing bookings in window and subtract overlaps.
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

		/**
		 * Filter available slots before they are returned.
		 *
		 * @param array $available  List of slots.
		 * @param array $service    Service row.
		 * @param string $date      YYYY-MM-DD.
		 * @param int   $staff_id   Staff id (0 if any).
		 */
		return [
			'slots'    => apply_filters( 'bookingistic_available_slots', $available, $service, $date, $staff_id ),
			'timezone' => $tz_str,
		];
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
}
