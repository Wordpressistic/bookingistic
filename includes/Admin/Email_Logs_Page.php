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

		$status        = sanitize_key( (string) ( $_GET['status'] ?? '' ) );
		$automation_id = (int) ( $_GET['automation_id'] ?? 0 );
		$page          = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
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
