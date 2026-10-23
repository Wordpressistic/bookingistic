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

// Read-only queries over the plugin's custom email-log table (identifiers from
// Tables:: class constants, values prepared). DirectQuery/NoCaching disabled
// by design; see includes/Database/Booking_Repository.php.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

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

		$where = [ '1=1' ];
		$prep  = [];
		if ( $status ) {
			$where[] = 'status = %s';
			$prep[]  = $status;
		}
		if ( $automation_id ) {
			$where[] = 'automation_id = %d';
			$prep[]  = $automation_id;
		}
		$where_sql = implode( ' AND ', $where );

		$total = (int) ( $prep
			? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", ...$prep ) ) // phpcs:ignore
			: $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) ); // phpcs:ignore

		$prep[] = $per_page;
		$prep[] = $offset;
		$rows   = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d", ...$prep ), // phpcs:ignore
			ARRAY_A
		) ?: [];

		$automations = Email_Automation_Repository::all();
		$pages       = (int) ceil( $total / $per_page );

		include BOOKINGISTIC_DIR . 'templates/admin/email-logs.php';
	}
}
