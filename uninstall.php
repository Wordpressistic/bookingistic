<?php
/**
 * Uninstall — only removes data when BOOKINGISTIC_DELETE_DATA is true.
 * Default: preserve all booking history. Site owners must explicitly opt in
 * to data deletion via `define( 'BOOKINGISTIC_DELETE_DATA', true );` in wp-config.php.
 *
 * @package Bookingistic
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'BOOKINGISTIC_DELETE_DATA' ) || ! BOOKINGISTIC_DELETE_DATA ) {
	return;
}

require_once __DIR__ . '/includes/Database/Tables.php';
\Bookingistic\Database\Tables::drop_all();

delete_option( 'bookingistic_settings' );
delete_option( 'bookingistic_db_version' );

wp_clear_scheduled_hook( 'bookingistic_send_reminders' );

// Capabilities cleanup.
$role = get_role( 'administrator' );
if ( $role ) {
	foreach ( [
		'manage_bookingistic',
		'view_bookingistic',
		'create_bookingistic_bookings',
		'edit_bookingistic_bookings',
		'delete_bookingistic_bookings',
		'manage_bookingistic_services',
		'manage_bookingistic_staff',
		'manage_bookingistic_settings',
		'manage_bookingistic_email_automations',
	] as $cap ) {
		$role->remove_cap( $cap );
	}
}
