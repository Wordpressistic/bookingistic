<?php
/**
 * Main plugin singleton.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Core;

use Bookingistic\Admin\Admin_Assets;
use Bookingistic\Admin\Admin_Menu;
use Bookingistic\Booking\Cancellation_Manager;
use Bookingistic\Booking\Reschedule_Manager;
use Bookingistic\Email\Email_Automation_Manager;
use Bookingistic\Frontend\Assets as Frontend_Assets;
use Bookingistic\Frontend\Shortcode;
use Bookingistic\Integrations\Integrations_Manager;
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
		Frontend_Assets::register();

		// Admin.
		if ( is_admin() ) {
			Admin_Menu::register();
			Admin_Assets::register();
		}

		// Email automations listen on booking action hooks.
		Email_Automation_Manager::register();

		// Cancel / reschedule public endpoints.
		Cancellation_Manager::register();
		Reschedule_Manager::register();

		// Integrations (WooCommerce, calendar feeds, Messageistic, CRMistic).
		Integrations_Manager::register();
	}
}
