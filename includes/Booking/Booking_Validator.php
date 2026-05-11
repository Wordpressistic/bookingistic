<?php
/**
 * Validate a booking submission before it is committed.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Booking;

use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Service_Repository;

defined( 'ABSPATH' ) || exit;

class Booking_Validator {

	/**
	 * Re-checks availability on the server before saving. The frontend
	 * MUST NOT be trusted to send a valid slot.
	 *
	 * Returns the resolved service row + computed end_utc on success.
	 */
	public static function validate_slot( int $service_id, string $start_utc, int $staff_id = 0, int $exclude_id = 0 ) {
		$service = Service_Repository::find( $service_id );
		if ( ! $service || $service['status'] !== 'active' ) {
			return new \WP_Error( 'invalid_service', __( 'Service not available.', 'bookingistic' ), [ 'status' => 422 ] );
		}

		$start_ts = strtotime( $start_utc . ' UTC' );
		if ( ! $start_ts ) {
			return new \WP_Error( 'invalid_start', __( 'Invalid start datetime.', 'bookingistic' ), [ 'status' => 422 ] );
		}

		$end_utc = gmdate( 'Y-m-d H:i:s', $start_ts + ( $service['duration_minutes'] * 60 ) );

		// Min notice / max advance.
		$settings    = get_option( 'bookingistic_settings', [] );
		$min_notice  = max( 0, (int) ( $settings['min_notice_hours'] ?? 4 ) );
		$max_advance = max( 1, (int) ( $settings['max_advance_days'] ?? 60 ) );
		$now         = time();
		if ( $start_ts < ( $now + $min_notice * HOUR_IN_SECONDS ) ) {
			return new \WP_Error( 'too_soon', __( 'Slot is below the minimum-notice window.', 'bookingistic' ), [ 'status' => 422 ] );
		}
		if ( $start_ts > ( $now + $max_advance * DAY_IN_SECONDS ) ) {
			return new \WP_Error( 'too_far', __( 'Slot is beyond the maximum-advance window.', 'bookingistic' ), [ 'status' => 422 ] );
		}

		$start_str = gmdate( 'Y-m-d H:i:s', $start_ts );

		// Group bookings: allow up to max_attendees to share an identical slot.
		$is_group = $service['booking_type'] === 'group' && (int) $service['max_attendees'] > 1;
		if ( $is_group ) {
			$existing = Booking_Repository::in_range( $service_id, $staff_id, $start_str, $end_utc );
			$same_slot = 0;
			foreach ( $existing as $b ) {
				if ( (int) $b['id'] === $exclude_id ) {
					continue;
				}
				if ( $b['start_datetime'] === $start_str && $b['end_datetime'] === $end_utc ) {
					$same_slot++;
				} else {
					// Any other overlap inside the window is a different group slot — block.
					return new \WP_Error( 'slot_taken', __( 'That slot is no longer available. Please pick another time.', 'bookingistic' ), [ 'status' => 409 ] );
				}
			}
			if ( $same_slot >= (int) $service['max_attendees'] ) {
				return new \WP_Error( 'slot_full', __( 'Sorry, this session is full.', 'bookingistic' ), [ 'status' => 409 ] );
			}
		} elseif ( Booking_Repository::has_overlap( $service_id, $staff_id, $start_str, $end_utc, $exclude_id ) ) {
			return new \WP_Error( 'slot_taken', __( 'That slot is no longer available. Please pick another time.', 'bookingistic' ), [ 'status' => 409 ] );
		}

		return [
			'service'  => $service,
			'end_utc'  => $end_utc,
			'duration' => (int) $service['duration_minutes'],
		];
	}
}
