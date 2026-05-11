<?php
/**
 * REST route registrar — delegates to per-resource controllers.
 *
 * @package Bookingistic
 */

namespace Bookingistic\REST;

defined( 'ABSPATH' ) || exit;

class REST_Controller {

	public static function register_routes(): void {
		Services_Controller::register_routes();
		Staff_Controller::register_routes();
		Availability_Controller::register_routes();
		Bookings_Controller::register_routes();
		Calendar_Controller::register_routes();
		Email_Automations_Controller::register_routes();
		ICS_Controller::register_routes();
	}

	public static function require_manage(): bool {
		return current_user_can( 'manage_bookingistic' ) || current_user_can( 'manage_options' );
	}
}
