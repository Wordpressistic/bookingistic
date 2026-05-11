<?php
/**
 * Staff REST controller.
 *
 * @package Bookingistic
 */

namespace Bookingistic\REST;

use Bookingistic\Database\Staff_Repository;

defined( 'ABSPATH' ) || exit;

class Staff_Controller {

	public static function register_routes(): void {
		$ns = BOOKINGISTIC_REST_NAMESPACE;

		register_rest_route(
			$ns,
			'/staff',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ self::class, 'index' ],
					'permission_callback' => '__return_true',
				],
			]
		);
	}

	public static function index( \WP_REST_Request $req ) {
		$service_id = (int) $req->get_param( 'service_id' );
		$rows       = $service_id ? Staff_Repository::for_service( $service_id ) : Staff_Repository::all( [ 'status' => 'active' ] );

		// Frontend exposure: trim private fields.
		$is_admin = REST_Controller::require_manage();
		return rest_ensure_response(
			array_map(
				static function ( $r ) use ( $is_admin ) {
					if ( ! $is_admin ) {
						unset( $r['days_off'], $r['special_availability'], $r['booking_limit_per_day'] );
					}
					return $r;
				},
				$rows
			)
		);
	}
}
