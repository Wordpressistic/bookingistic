<?php
/**
 * Plugin deactivation.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Core;

defined( 'ABSPATH' ) || exit;

class Deactivator {

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'bookingistic_send_reminders' );
		wp_clear_scheduled_hook( 'bookingistic_gcal_sync' );
		flush_rewrite_rules();
	}
}
