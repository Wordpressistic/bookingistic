<?php
/**
 * Availability REST controller.
 *
 * GET /availability?service_id=N&date=YYYY-MM-DD[&staff_id=N&tz=America/Toronto]
 *
 * @package Bookingistic
 */

namespace Bookingistic\REST;

use Bookingistic\Booking\Availability_Engine;
use Bookingistic\Helpers\Sanitizer;

defined( 'ABSPATH' ) || exit;

class Availability_Controller {

	public static function register_routes(): void {
		register_rest_route(
			BOOKINGISTIC_REST_NAMESPACE,
			'/availability',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'get_availability' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'service_id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
					'date'       => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
					'staff_id'   => [ 'required' => false, 'sanitize_callback' => 'absint' ],
					'tz'         => [ 'required' => false, 'sanitize_callback' => [ Sanitizer::class, 'timezone' ] ],
				],
			]
		);
	}

	public static function get_availability( \WP_REST_Request $req ) {
		$service_id = (int) $req->get_param( 'service_id' );
		$date       = (string) $req->get_param( 'date' );
		$staff_id   = (int) $req->get_param( 'staff_id' );
		$tz         = (string) $req->get_param( 'tz' );

		// Cache for 30 seconds per (service, date, staff, tz). Hits are common.
		$cache_key = sprintf( 'bookingistic_av_%d_%s_%d_%s', $service_id, $date, $staff_id, md5( $tz ) );
		$cached    = get_transient( $cache_key );
		if ( $cached ) {
			return rest_ensure_response( $cached );
		}

		$result = Availability_Engine::for_service_date( $service_id, $date, $staff_id, $tz );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		set_transient( $cache_key, $result, 30 );
		return rest_ensure_response( $result );
	}
}
