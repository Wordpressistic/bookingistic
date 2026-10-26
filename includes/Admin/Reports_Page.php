<?php
/**
 * Admin reports page — read-only summaries over existing tables.
 *
 * No writes, no schema changes: pure COUNT/GROUP BY queries on the bookings
 * table so the screen always reflects real data.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Database\Cache;
use Bookingistic\Database\Tables;

defined( 'ABSPATH' ) || exit;

class Reports_Page {

	public static function render(): void {
		global $wpdb;

		$bookings = Tables::bookings_table();
		$services = Tables::services_table();

		// Status distribution (real counts).
		$cache_key = Cache::key( 'reports_status', [], [ Cache::BOOKINGS ] );
		$status_rows = wp_cache_get( $cache_key, Cache::BOOKINGS, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$status_rows = $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) AS n FROM %i GROUP BY status ORDER BY n DESC', $bookings ), ARRAY_A ) ?: [];
			wp_cache_set( $cache_key, $status_rows, Cache::BOOKINGS );
		}
		$total       = array_sum( array_map( static fn( $r ) => (int) $r['n'], $status_rows ) );

		// Top services by booking count.
		$cache_key = Cache::key( 'reports_services', [], [ Cache::BOOKINGS, Cache::SERVICES ] );
		$service_rows = wp_cache_get( $cache_key, Cache::BOOKINGS, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$service_rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT s.name, COUNT(b.id) AS n
					FROM %i s
					LEFT JOIN %i b ON b.service_id = s.id
					GROUP BY s.id, s.name
					ORDER BY n DESC, s.name ASC
					LIMIT 8',
					$services,
					$bookings
				),
				ARRAY_A
			) ?: [];
			wp_cache_set( $cache_key, $service_rows, Cache::BOOKINGS );
		}

		// Booking volume for the last 8 weeks (ISO year-week).
		$cache_key = Cache::key( 'reports_weeks', [], [ Cache::BOOKINGS ] );
		$week_rows = wp_cache_get( $cache_key, Cache::BOOKINGS, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$week_rows = $wpdb->get_results( $wpdb->prepare( "SELECT DATE_FORMAT(start_datetime, '%%x-W%%v') AS wk, COUNT(*) AS n FROM %i WHERE start_datetime IS NOT NULL GROUP BY wk ORDER BY wk DESC LIMIT 8", $bookings ), ARRAY_A ) ?: [];
			wp_cache_set( $cache_key, $week_rows, Cache::BOOKINGS );
		}
		$week_rows = array_reverse( $week_rows );

		$max_week = 0;
		foreach ( $week_rows as $w ) {
			$max_week = max( $max_week, (int) $w['n'] );
		}

		$status_labels = [
			'pending'      => __( 'Pending', 'bookingistic' ),
			'confirmed'    => __( 'Confirmed', 'bookingistic' ),
			'completed'    => __( 'Completed', 'bookingistic' ),
			'cancelled'    => __( 'Cancelled', 'bookingistic' ),
			'no_show'      => __( 'No show', 'bookingistic' ),
			'rescheduled'  => __( 'Rescheduled', 'bookingistic' ),
		];

		$tone_map = [
			'pending'     => 'amber',
			'confirmed'   => 'blue',
			'completed'   => 'green',
			'cancelled'   => 'red',
			'no_show'     => 'red',
			'rescheduled' => '',
		];

		$stats = [];
		foreach ( $status_rows as $row ) {
			$status  = (string) $row['status'];
			$stats[] = [
				'label' => $status_labels[ $status ] ?? ucwords( str_replace( '_', ' ', $status ) ),
				'value' => number_format_i18n( (int) $row['n'] ),
				'hint'  => $total > 0 ? sprintf( /* translators: %s: percentage of all bookings */ __( '%s%% of all bookings', 'bookingistic' ), round( ( (int) $row['n'] / $total ) * 100, 1 ) ) : '',
				'tone'  => $tone_map[ $status ] ?? '',
			];
		}

		include BOOKINGISTIC_DIR . 'templates/admin/reports.php';
	}
}
