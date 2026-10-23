<?php
/**
 * Admin bookings: list / detail / new.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Booking\Booking_Manager;
use Bookingistic\Booking\Booking_Validator;
use Bookingistic\Booking\Reschedule_Manager;
use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Customer_Repository;
use Bookingistic\Database\Service_Repository;
use Bookingistic\Database\Staff_Repository;
use Bookingistic\Helpers\Date_Time;

defined( 'ABSPATH' ) || exit;

class Bookings_Page {

	public static function render(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display filter.
		$view_id = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;
		$new     = isset( $_GET['new'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display filter.

		if ( $view_id ) {
			self::render_detail( $view_id );
			return;
		}
		if ( $new ) {
			self::render_new();
			return;
		}
		self::render_list();
	}

	public static function maybe_act(): void {
		// Action dispatcher peek: each branch verifies its own nonce below
		// before any processing.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified below before any processing.
		$action = sanitize_key( wp_unslash( $_POST['bookingistic_admin_action'] ?? '' ) );
		if ( ! in_array( $action, [ 'booking_status', 'booking_create', 'booking_reschedule', 'booking_cancel', 'booking_notes' ], true ) ) {
			return;
		}

		// Authorization up front, before any per-action nonce check: creating a
		// booking needs only the create capability, every other mutation here
		// (status, cancel, reschedule, notes) is an edit.
		\Bookingistic\Core\Capabilities::verify(
			$action === 'booking_create'
				? \Bookingistic\Core\Capabilities::CREATE_BOOKINGS
				: \Bookingistic\Core\Capabilities::EDIT_BOOKINGS
		);

		switch ( $action ) {
			case 'booking_status':
				check_admin_referer( 'bookingistic_admin_booking' );
				$id     = absint( wp_unslash( $_POST['booking_id'] ?? 0 ) );
				$status = sanitize_key( (string) ( $_POST['status'] ?? '' ) );
				if ( $id && $status ) {
					Booking_Manager::set_status( $id, $status );
				}
				wp_safe_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=bookingistic-bookings' ) );
				exit;

			case 'booking_create':
				check_admin_referer( 'bookingistic_admin_booking_new' );
				$result = self::handle_create(
					[
						'service_id' => absint( wp_unslash( $_POST['service_id'] ?? 0 ) ),
						'staff_id'   => absint( wp_unslash( $_POST['staff_id'] ?? 0 ) ),
						'start_utc'  => sanitize_text_field( wp_unslash( $_POST['start_utc'] ?? '' ) ),
						'timezone'   => sanitize_text_field( wp_unslash( $_POST['timezone'] ?? wp_timezone_string() ) ),
						'first_name' => sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) ),
						'last_name'  => sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) ),
						'email'      => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
						'phone'      => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
						'company'    => sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) ),
						'notes'      => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
					]
				);
				if ( is_wp_error( $result ) ) {
					wp_safe_redirect( add_query_arg( [ 'page' => 'bookingistic-bookings', 'new' => 1, 'error' => rawurlencode( $result->get_error_message() ) ], admin_url( 'admin.php' ) ) );
				} else {
					wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-bookings&view=' . (int) $result['booking']['id'] ) );
				}
				exit;

			case 'booking_reschedule':
				check_admin_referer( 'bookingistic_admin_booking_reschedule' );
				$id      = absint( wp_unslash( $_POST['booking_id'] ?? 0 ) );
				$booking = Booking_Repository::find( $id );
				if ( ! $booking ) {
					wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-bookings' ) );
					exit;
				}
				$result = Reschedule_Manager::apply(
					$id,
					$booking['reschedule_token'],
					sanitize_text_field( wp_unslash( $_POST['start_utc'] ?? '' ) ),
					absint( wp_unslash( $_POST['staff_id'] ?? 0 ) )
				);
				$redirect = admin_url( 'admin.php?page=bookingistic-bookings&view=' . $id );
				if ( is_wp_error( $result ) ) {
					$redirect = add_query_arg( 'error', rawurlencode( $result->get_error_message() ), $redirect );
				} else {
					$redirect = add_query_arg( 'rescheduled', '1', $redirect );
				}
				wp_safe_redirect( $redirect );
				exit;

			case 'booking_cancel':
				check_admin_referer( 'bookingistic_admin_booking' );
				$id = absint( wp_unslash( $_POST['booking_id'] ?? 0 ) );
				if ( $id ) {
					Booking_Manager::set_status( $id, 'cancelled' );
				}
				wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-bookings&view=' . $id ) );
				exit;

			case 'booking_notes':
				check_admin_referer( 'bookingistic_admin_booking_notes' );
				$id = absint( wp_unslash( $_POST['booking_id'] ?? 0 ) );
				if ( $id ) {
					Booking_Repository::update( $id, [ 'internal_notes' => sanitize_textarea_field( wp_unslash( $_POST['internal_notes'] ?? '' ) ) ] );
				}
				wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-bookings&view=' . $id ) );
				exit;
		}
	}

	private static function render_list(): void {
		// Read-only display filters; no state changes on this screen.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$args = [
			'page'     => isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1,
			'per_page' => 25,
			'status'   => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
			'service_id' => isset( $_GET['service_id'] ) ? absint( wp_unslash( $_GET['service_id'] ) ) : 0,
			'staff_id' => isset( $_GET['staff_id'] ) ? absint( wp_unslash( $_GET['staff_id'] ) ) : 0,
			'payment_status' => isset( $_GET['payment_status'] ) ? sanitize_key( wp_unslash( $_GET['payment_status'] ) ) : '',
		];
		// phpcs:enable
		$args   = array_filter( $args, static fn( $v ) => $v !== '' && $v !== 0 );
		$result = Booking_Repository::query( $args );

		$rows = array_map(
			static function ( $b ) {
				$b['service']  = Service_Repository::find( (int) $b['service_id'] );
				$b['customer'] = Customer_Repository::find( (int) $b['customer_id'] );
				return $b;
			},
			$result['items']
		);

		$services = Service_Repository::all();
		$staff    = Staff_Repository::all();

		include BOOKINGISTIC_DIR . 'templates/admin/bookings-list.php';
	}

	private static function render_detail( int $id ): void {
		$booking = Booking_Repository::find( $id );
		if ( ! $booking ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Booking not found.', 'bookingistic' ) . '</h1></div>';
			return;
		}
		$service  = Service_Repository::find( (int) $booking['service_id'] );
		$customer = Customer_Repository::find( (int) $booking['customer_id'] );
		$staff    = $booking['staff_id'] ? Staff_Repository::find( (int) $booking['staff_id'] ) : null;
		$services = Service_Repository::all( [ 'status' => 'active' ] );
		$staff_list = Staff_Repository::all( [ 'status' => 'active' ] );

		include BOOKINGISTIC_DIR . 'templates/admin/booking-detail.php';
	}

	private static function render_new(): void {
		$services = Service_Repository::all( [ 'status' => 'active' ] );
		$staff    = Staff_Repository::all( [ 'status' => 'active' ] );
		include BOOKINGISTIC_DIR . 'templates/admin/booking-new.php';
	}

	/**
	 * Create a booking from pre-sanitized admin form input (built in
	 * maybe_act() after the nonce + capability checks).
	 *
	 * @param array $input Sanitized form fields.
	 * @return array|\WP_Error
	 */
	private static function handle_create( array $input ) {
		$service_id = $input['service_id'];
		$staff_id   = $input['staff_id'];
		$start_utc  = $input['start_utc'];
		$timezone   = $input['timezone'];
		$email      = $input['email'];

		// Availability is still validated server-side (prevents double booking).
		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'bad_email', __( 'Valid email required.', 'bookingistic' ) );
		}
		if ( ! $service_id || ! $start_utc ) {
			return new \WP_Error( 'missing', __( 'Service and start time are required.', 'bookingistic' ) );
		}

		return Booking_Manager::create_booking(
			[
				'service_id' => $service_id,
				'staff_id'   => $staff_id,
				'start_utc'  => $start_utc,
				'timezone'   => $timezone,
				'customer'   => [
					'first_name' => $input['first_name'],
					'last_name'  => $input['last_name'],
					'email'      => $email,
					'phone'      => $input['phone'],
					'company'    => $input['company'],
					'timezone'   => $timezone,
					'source'     => 'admin',
				],
				'notes'      => $input['notes'],
				'source'     => 'admin',
			]
		);
	}
}
