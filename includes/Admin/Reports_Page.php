<?php
/**
 * Admin Reports page.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Database\Service_Repository;
use Bookingistic\Database\Staff_Repository;
use Bookingistic\Database\Tables;

defined( 'ABSPATH' ) || exit;

class Reports_Page {

	public static function render(): void {
		global $wpdb;
		$bookings = Tables::bookings_table();
		$emails   = Tables::email_logs_table();

		$days = max( 7, min( 180, (int) ( $_GET['days'] ?? 30 ) ) );
		$since = gmdate( 'Y-m-d 00:00:00', strtotime( "-{$days} days" ) );

		// Totals.
		$totals = [
			'all'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bookings}" ),
			'in_window'   => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bookings} WHERE created_at >= %s", $since ) ),
			'completed'   => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bookings} WHERE status='completed' AND created_at >= %s", $since ) ),
			'cancelled'   => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bookings} WHERE status='cancelled' AND created_at >= %s", $since ) ),
			'no_show'     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bookings} WHERE status='no_show' AND created_at >= %s", $since ) ),
			'pending'     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bookings} WHERE status='pending' AND created_at >= %s", $since ) ),
			'revenue'     => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(payment_amount),0) FROM {$bookings} WHERE payment_status IN ('paid','deposit_paid') AND created_at >= %s", $since ) ),
		];

		$rate = fn( int $num, int $den ) => $den > 0 ? round( 100 * $num / $den, 1 ) : 0.0;
		$totals['cancellation_rate'] = $rate( $totals['cancelled'], $totals['in_window'] );
		$totals['no_show_rate']      = $rate( $totals['no_show'], $totals['in_window'] );

		// Bookings by date (last N days).
		$by_date = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) AS d, COUNT(*) AS c FROM {$bookings} WHERE created_at >= %s GROUP BY d ORDER BY d ASC",
				$since
			),
			ARRAY_A
		) ?: [];

		// By service / staff.
		$by_service = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT service_id, COUNT(*) AS c FROM {$bookings} WHERE created_at >= %s GROUP BY service_id ORDER BY c DESC LIMIT 10",
				$since
			),
			ARRAY_A
		) ?: [];
		$by_staff = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT staff_id, COUNT(*) AS c FROM {$bookings} WHERE created_at >= %s GROUP BY staff_id ORDER BY c DESC LIMIT 10",
				$since
			),
			ARRAY_A
		) ?: [];

		// Revenue by service.
		$revenue_by_service = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT service_id, COALESCE(SUM(payment_amount),0) AS revenue FROM {$bookings}
				 WHERE payment_status IN ('paid','deposit_paid') AND created_at >= %s
				 GROUP BY service_id ORDER BY revenue DESC LIMIT 10",
				$since
			),
			ARRAY_A
		) ?: [];

		// Customer source.
		$by_source = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT source, COUNT(*) AS c FROM {$bookings} WHERE created_at >= %s GROUP BY source ORDER BY c DESC",
				$since
			),
			ARRAY_A
		) ?: [];

		// Email stats.
		$email_stats = [
			'sent'   => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$emails} WHERE status='sent' AND created_at >= %s", $since ) ),
			'failed' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$emails} WHERE status='failed' AND created_at >= %s", $since ) ),
		];

		// Resolve names for service/staff rows.
		$service_lookup = [];
		foreach ( Service_Repository::all() as $s ) { $service_lookup[ (int) $s['id'] ] = $s['name']; }
		$staff_lookup = [];
		foreach ( Staff_Repository::all() as $s ) { $staff_lookup[ (int) $s['id'] ] = $s['name']; }

		include BOOKINGISTIC_DIR . 'templates/admin/reports.php';
	}
}
