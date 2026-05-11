<?php
/**
 * Admin dashboard page.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Email_Log_Repository;
use Bookingistic\Database\Tables;

defined( 'ABSPATH' ) || exit;

class Dashboard_Page {

	public static function render(): void {
		global $wpdb;
		$bookings_table = Tables::bookings_table();
		$now            = current_time( 'mysql', true );

		$total     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bookings_table}" );
		$upcoming  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bookings_table} WHERE start_datetime >= %s AND status IN ('pending','confirmed','rescheduled')", $now ) );
		$pending   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bookings_table} WHERE status = 'pending'" );
		$completed = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bookings_table} WHERE status = 'completed'" );
		$cancelled = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bookings_table} WHERE status = 'cancelled'" );
		$logs      = Email_Log_Repository::recent( 10 );

		$recent_bookings = Booking_Repository::query( [ 'per_page' => 10 ] );

		include BOOKINGISTIC_DIR . 'templates/admin/dashboard.php';
	}
}
