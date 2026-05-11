<?php
/**
 * Plugin Name:       Bookingistic
 * Plugin URI:        https://www.wordpressistic.com/solutions/booking/
 * Description:       Advanced Booking, Calendar & Email Automation for WordPress. Turn your site into a premium booking system with services, staff, availability, calendar views, and automated emails.
 * Version:           2.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            WordPressistic
 * Author URI:        https://www.wordpressistic.com
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

define( 'BOOKINGISTIC_VERSION', '2.0.0' );
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
		load_plugin_textdomain( 'bookingistic', false, dirname( BOOKINGISTIC_BASENAME ) . '/languages' );
		\Bookingistic\Core\Plugin::instance()->boot();
	},
	5
);
