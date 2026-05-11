<?php
/**
 * Booking Manager — orchestrates customer upsert, validation, persistence, and event hooks.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Booking;

use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Customer_Repository;
use Bookingistic\Database\Service_Repository;
use Bookingistic\Helpers\Date_Time;

defined( 'ABSPATH' ) || exit;

class Booking_Manager {

	/**
	 * Create a full booking with start/end. Returns ['booking' => ..., 'customer' => ..., 'service' => ...].
	 *
	 * @param array $data {
	 *     @type int    service_id
	 *     @type int    staff_id    optional
	 *     @type string start_utc   "Y-m-d H:i:s" (UTC)
	 *     @type string timezone    customer tz
	 *     @type array  customer    [first_name,last_name,email,phone,company,website,timezone,notes]
	 *     @type string notes
	 *     @type string source
	 * }
	 */
	public static function create_booking( array $data ) {
		$service_id = (int) ( $data['service_id'] ?? 0 );
		$staff_id   = (int) ( $data['staff_id'] ?? 0 );
		$start_utc  = (string) ( $data['start_utc'] ?? '' );

		$validation = Booking_Validator::validate_slot( $service_id, $start_utc, $staff_id );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$customer = Customer_Repository::upsert( $data['customer'] ?? [] );
		if ( is_wp_error( $customer ) ) {
			return $customer;
		}

		$service = $validation['service'];
		$status  = $service['confirmation_mode'] === 'manual' ? 'pending' : 'confirmed';

		$booking = Booking_Repository::create(
			[
				'service_id'     => $service_id,
				'staff_id'       => $staff_id,
				'customer_id'    => (int) $customer['id'],
				'start_datetime' => $start_utc,
				'end_datetime'   => $validation['end_utc'],
				'timezone'       => sanitize_text_field( $data['timezone'] ?? ( $customer['timezone'] ?? wp_timezone_string() ) ),
				'status'         => $status,
				'payment_status' => $service['price'] > 0 ? 'pending' : 'free',
				'payment_amount' => (float) $service['price'],
				'source'         => sanitize_text_field( $data['source'] ?? 'frontend' ),
				'notes'          => sanitize_textarea_field( $data['notes'] ?? '' ),
			]
		);
		if ( is_wp_error( $booking ) ) {
			return $booking;
		}

		do_action( 'bookingistic_booking_submitted', $booking, $customer, $service );
		if ( $status === 'confirmed' ) {
			do_action( 'bookingistic_booking_confirmed', $booking, $customer, $service );
		}

		return [
			'booking'  => $booking,
			'customer' => $customer,
			'service'  => $service,
		];
	}

	/**
	 * Lead-only booking (no time slot) — used by the v1 backward-compat flow.
	 * Always lands as `pending` and never blocks the calendar.
	 */
	public static function create_lead_booking( array $data ) {
		$service = Service_Repository::find( (int) ( $data['service_id'] ?? 0 ) );
		if ( ! $service ) {
			return new \WP_Error( 'invalid_service', __( 'Service not available.', 'bookingistic' ) );
		}
		$customer = Customer_Repository::upsert( $data['customer'] ?? [] );
		if ( is_wp_error( $customer ) ) {
			return $customer;
		}

		$booking = Booking_Repository::create(
			[
				'service_id'     => (int) $service['id'],
				'staff_id'       => 0,
				'customer_id'    => (int) $customer['id'],
				'start_datetime' => null,
				'end_datetime'   => null,
				'timezone'       => $customer['timezone'] ?: wp_timezone_string(),
				'status'         => 'pending',
				'payment_status' => 'free',
				'source'         => sanitize_text_field( $data['source'] ?? 'legacy' ),
				'notes'          => sanitize_textarea_field( $data['notes'] ?? '' ),
			]
		);
		if ( is_wp_error( $booking ) ) {
			return $booking;
		}

		do_action( 'bookingistic_booking_submitted', $booking, $customer, $service );

		return [
			'booking_id' => (int) $booking['id'],
			'booking'    => $booking,
			'customer'   => $customer,
			'service'    => $service,
		];
	}

	public static function set_status( int $booking_id, string $status ) {
		$booking = Booking_Repository::find( $booking_id );
		if ( ! $booking ) {
			return new \WP_Error( 'not_found', __( 'Booking not found.', 'bookingistic' ) );
		}
		if ( $booking['status'] === $status ) {
			return $booking;
		}
		$updated = Booking_Repository::update( $booking_id, [ 'status' => $status ] );
		do_action( 'bookingistic_booking_status_changed', $updated, $booking['status'] );
		if ( $status === 'confirmed' ) {
			do_action( 'bookingistic_booking_confirmed', $updated, null, null );
		}
		if ( $status === 'cancelled' ) {
			do_action( 'bookingistic_booking_cancelled', $updated, null, null );
		}
		if ( $status === 'completed' ) {
			do_action( 'bookingistic_booking_completed', $updated, null, null );
		}
		return $updated;
	}
}
