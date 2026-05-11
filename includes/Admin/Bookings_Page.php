<?php
/**
 * Admin bookings list + detail.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Booking\Booking_Manager;
use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Customer_Repository;
use Bookingistic\Database\Service_Repository;

defined( 'ABSPATH' ) || exit;

class Bookings_Page {

	public static function render(): void {
		$args = [
			'page'     => max( 1, (int) ( $_GET['paged'] ?? 1 ) ),
			'per_page' => 25,
			'status'   => sanitize_key( (string) ( $_GET['status'] ?? '' ) ),
		];
		$args   = array_filter( $args );
		$result = Booking_Repository::query( $args );

		// Resolve service + customer for display.
		$rows = array_map(
			static function ( $b ) {
				$b['service']  = Service_Repository::find( (int) $b['service_id'] );
				$b['customer'] = Customer_Repository::find( (int) $b['customer_id'] );
				return $b;
			},
			$result['items']
		);

		include BOOKINGISTIC_DIR . 'templates/admin/bookings-list.php';
	}

	public static function maybe_act(): void {
		if ( ( $_POST['bookingistic_admin_action'] ?? '' ) !== 'booking_status' ) {
			return;
		}
		check_admin_referer( 'bookingistic_admin_booking' );
		$id     = (int) ( $_POST['booking_id'] ?? 0 );
		$status = sanitize_key( (string) ( $_POST['status'] ?? '' ) );
		if ( $id && $status ) {
			Booking_Manager::set_status( $id, $status );
		}
		wp_safe_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=bookingistic-bookings' ) );
		exit;
	}
}
