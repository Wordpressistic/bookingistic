<?php
/**
 * Admin email logs page.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

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

		$automations = Email_Automation_Repository::all();
		$pages       = (int) ceil( $total / $per_page );

		include BOOKINGISTIC_DIR . 'templates/admin/email-logs.php';
	}
}
