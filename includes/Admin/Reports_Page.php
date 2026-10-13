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

use Bookingistic\Database\Tables;

defined( 'ABSPATH' ) || exit;

class Reports_Page {

	public static function render(): void {
		global $wpdb;

		$bookings = Tables::bookings_table();
		$services = Tables::services_table();

		// Status distribution (real counts).
		$status_rows = $wpdb->get_results( "SELECT status, COUNT(*) AS n FROM {$bookings} GROUP BY status ORDER BY n DESC", ARRAY_A ) ?: []; // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$total       = array_sum( array_map( static fn( $r ) => (int) $r['n'], $status_rows ) );

		// Top services by booking count.
		$service_rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT s.name, COUNT(b.id) AS n
			 FROM {$services} s
			 LEFT JOIN {$bookings} b ON b.service_id = s.id
			 GROUP BY s.id, s.name
			 ORDER BY n DESC, s.name ASC
			 LIMIT 8",
			ARRAY_A
		) ?: [];

		// Booking volume for the last 8 weeks (ISO year-week).
		$week_rows = $wpdb->get_results( "SELECT DATE_FORMAT(start_datetime, '%x-W%v') AS wk, COUNT(*) AS n FROM {$bookings} WHERE start_datetime IS NOT NULL GROUP BY wk ORDER BY wk DESC LIMIT 8", ARRAY_A ) ?: []; // phpcs:ignore WordPress.DB.DirectDatabaseQuery
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
