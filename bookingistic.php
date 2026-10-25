<?php
/**
 * Plugin Name:       Bookingistic
 * Plugin URI:        https://bookingistic.com
 * Description:       Advanced Booking, Calendar & Email Automation for WordPress. Turn your site into a premium booking system with services, staff, availability, calendar views, and automated emails.
 * Version:           2.6.0-dev
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Shuvo Chandra Sarker
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bookingistic
 * Domain Path:       /languages
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'BOOKINGISTIC_VERSION' ) ) {
	return;
}

define( 'BOOKINGISTIC_VERSION', '2.6.0-dev' );
define( 'BOOKINGISTIC_FILE', __FILE__ );
define( 'BOOKINGISTIC_DIR', plugin_dir_path( __FILE__ ) );
define( 'BOOKINGISTIC_URL', plugin_dir_url( __FILE__ ) );
define( 'BOOKINGISTIC_BASENAME', plugin_basename( __FILE__ ) );
define( 'BOOKINGISTIC_REST_NAMESPACE', 'bookingistic/v1' );

spl_autoload_register(
	static function ( $class ) {
		if ( strpos( $class, 'Bookingistic\\' ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( 'Bookingistic\\' ) );
		$path     = BOOKINGISTIC_DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

register_activation_hook( __FILE__, [ \Bookingistic\Core\Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \Bookingistic\Core\Deactivator::class, 'deactivate' ] );

add_action(
	'plugins_loaded',
	static function () {
		// Translations for the WordPress.org slug load automatically since
		// WP 4.6 — no load_plugin_textdomain() call is needed.
		\Bookingistic\Core\Plugin::instance()->boot();
	},
	5
);
