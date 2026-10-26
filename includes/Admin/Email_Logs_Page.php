<?php
/**
 * Admin email logs page.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Database\Cache;
use Bookingistic\Database\Email_Automation_Repository;
use Bookingistic\Database\Tables;

defined( 'ABSPATH' ) || exit;

class Email_Logs_Page {

	public static function render(): void {
		global $wpdb;
		$table = Tables::email_logs_table();

		// Read-only display filters; no state changes on this screen.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status        = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$automation_id = isset( $_GET['automation_id'] ) ? absint( wp_unslash( $_GET['automation_id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page          = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		$per_page      = 50;
		$offset        = ( $page - 1 ) * $per_page;

		// Optional filters are value-bound; '' / 0 disables a filter.
		$cache_key = Cache::key( 'email_logs_count', [ $status, $automation_id ], [ Cache::EMAIL_LOGS ] );
		$total = wp_cache_get( $cache_key, Cache::EMAIL_LOGS, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM %i WHERE ( %s = '' OR status = %s ) AND ( %d = 0 OR automation_id = %d )",
					$table,
					$status,
					$status,
					$automation_id,
					$automation_id
				)
			);
			wp_cache_set( $cache_key, $total, Cache::EMAIL_LOGS );
		}

		$cache_key = Cache::key( 'email_logs_page', [ $status, $automation_id, $per_page, $offset ], [ Cache::EMAIL_LOGS ] );
		$rows = wp_cache_get( $cache_key, Cache::EMAIL_LOGS, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM %i WHERE ( %s = '' OR status = %s ) AND ( %d = 0 OR automation_id = %d ) ORDER BY created_at DESC LIMIT %d OFFSET %d",
					$table,
					$status,
					$status,
					$automation_id,
					$automation_id,
					$per_page,
					$offset
				),
				ARRAY_A
			) ?: [];
			wp_cache_set( $cache_key, $rows, Cache::EMAIL_LOGS );
		}

		$automations = Email_Automation_Repository::all();
		$pages       = (int) ceil( $total / $per_page );

		include BOOKINGISTIC_DIR . 'templates/admin/email-logs.php';
	}
}
