<?php
/**
 * Admin dashboard page.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Database\Cache;
use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Email_Log_Repository;
use Bookingistic\Database\Tables;

defined( 'ABSPATH' ) || exit;

class Dashboard_Page {

	public static function render(): void {
		global $wpdb;
		$bookings_table = Tables::bookings_table();
		$now            = current_time( 'mysql', true );

		$cache_key = Cache::key( 'dashboard_total', [], [ Cache::BOOKINGS ] );
		$total = wp_cache_get( $cache_key, Cache::BOOKINGS, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$total = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $bookings_table ) );
			wp_cache_set( $cache_key, $total, Cache::BOOKINGS );
		}
		$cache_key = Cache::key( 'dashboard_upcoming', [ $now ], [ Cache::BOOKINGS ] );
		$upcoming = wp_cache_get( $cache_key, Cache::BOOKINGS, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$upcoming = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE start_datetime >= %s AND status IN ('pending','confirmed','rescheduled')", $bookings_table, $now ) );
			wp_cache_set( $cache_key, $upcoming, Cache::BOOKINGS );
		}
		$cache_key = Cache::key( 'dashboard_status', [ 'pending' ], [ Cache::BOOKINGS ] );
		$pending = wp_cache_get( $cache_key, Cache::BOOKINGS, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$pending = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE status = %s', $bookings_table, 'pending' ) );
			wp_cache_set( $cache_key, $pending, Cache::BOOKINGS );
		}
		$cache_key = Cache::key( 'dashboard_status', [ 'completed' ], [ Cache::BOOKINGS ] );
		$completed = wp_cache_get( $cache_key, Cache::BOOKINGS, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$completed = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE status = %s', $bookings_table, 'completed' ) );
			wp_cache_set( $cache_key, $completed, Cache::BOOKINGS );
		}
		$cache_key = Cache::key( 'dashboard_status', [ 'cancelled' ], [ Cache::BOOKINGS ] );
		$cancelled = wp_cache_get( $cache_key, Cache::BOOKINGS, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$cancelled = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE status = %s', $bookings_table, 'cancelled' ) );
			wp_cache_set( $cache_key, $cancelled, Cache::BOOKINGS );
		}
		$logs      = Email_Log_Repository::recent( 10 );

		$recent_bookings = Booking_Repository::query( [ 'per_page' => 10 ] );

		include BOOKINGISTIC_DIR . 'templates/admin/dashboard.php';
	}
}
