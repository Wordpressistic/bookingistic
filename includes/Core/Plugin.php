<?php
/**
 * Main plugin singleton.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Core;

use Bookingistic\Admin\Admin_Assets;
use Bookingistic\Admin\Admin_Menu;
use Bookingistic\Admin\Appearance_Page;
use Bookingistic\Booking\Cancellation_Manager;
use Bookingistic\Booking\Payment_Handler;
use Bookingistic\Booking\Reschedule_Manager;
use Bookingistic\Email\Email_Automation_Manager;
use Bookingistic\Frontend\Assets as Frontend_Assets;
use Bookingistic\Frontend\Payment_Shortcode;
use Bookingistic\Frontend\Shortcode;
use Bookingistic\Integrations\Elementor\Elementor_Module;
use Bookingistic\Integrations\Integrations_Manager;
use Bookingistic\Payments\Payment_Manager;
use Bookingistic\REST\REST_Controller;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function boot(): void {
		// Custom cron interval for calendar sync.
		add_filter( 'cron_schedules', [ Cron::class, 'register_schedules' ] );

		// Database upgrade check.
		add_action( 'init', [ Installer::class, 'maybe_upgrade' ], 1 );

		// Backward compatibility with v1 CPT + REST route.
		add_action( 'init', [ Backward_Compatibility::class, 'register_cpt' ], 5 );
		add_action( 'rest_api_init', [ Backward_Compatibility::class, 'register_legacy_route' ] );
		add_action( 'admin_post_nopriv_wpistic_bookingistic', [ Backward_Compatibility::class, 'handle_legacy_form' ] );
		add_action( 'admin_post_wpistic_bookingistic', [ Backward_Compatibility::class, 'handle_legacy_form' ] );

		// REST API.
		add_action( 'rest_api_init', [ REST_Controller::class, 'register_routes' ] );

		// Frontend.
		Shortcode::register();
		Payment_Shortcode::register();
		Frontend_Assets::register();

		// Admin.
		if ( is_admin() ) {
			Admin_Menu::register();
			Admin_Assets::register();
		}

		// Email automations listen on booking action hooks.
		Email_Automation_Manager::register();

		// Cancel / reschedule / pay public endpoints.
		Cancellation_Manager::register();
		Reschedule_Manager::register();
		Payment_Handler::register();

		// Appearance (form templates, calendar views, industry presets).
		Appearance_Page::register();

		// Elementor widgets (no-op unless Elementor is installed).
		Elementor_Module::register();

		// Integrations (WooCommerce, calendar feeds, Messageistic, CRMistic).
		Integrations_Manager::register();

		// Payments (gateway registry + WooCommerce Orders ledger adapter).
		// Late priority: WooCommerce still defines its classes after plugins_loaded:5,
		// so gateway discovery must wait for plugins to settle (same timing the
		// integrations registry uses). Calling boot() twice is a no-op — WP dedupes
		// identical hooks.
		add_action(
			'plugins_loaded',
			static function (): void {
				foreach ( Payment_Manager::gateways() as $gateway ) {
					$gateway->boot();
				}
			},
			20
		);
	}
}
