<?php
/**
 * Plugin activation.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Core;

defined( 'ABSPATH' ) || exit;

class Activator {

	public static function activate(): void {
		Installer::install();
		Capabilities::add_for_admin();
		flush_rewrite_rules();
	}
}
