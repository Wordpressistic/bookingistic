<?php
/**
 * Staff REST controller.
 *
 * GET    /staff                — public (trimmed)
 * GET    /staff?service_id=N   — public (only staff assigned to that service)
 * POST   /staff                — manage_bookingistic
 * GET    /staff/{id}           — public (trimmed) / admin (full)
 * PATCH  /staff/{id}           — manage_bookingistic
 * DELETE /staff/{id}           — manage_bookingistic
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
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ self::class, 'create' ],
					'permission_callback' => [ REST_Controller::class, 'require_manage' ],
				],
			]
		);

		register_rest_route(
			$ns,
			'/staff/(?P<id>\d+)',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ self::class, 'show' ],
					'permission_callback' => '__return_true',
				],
				[
					'methods'             => [ 'PATCH', 'PUT' ],
					'callback'            => [ self::class, 'patch' ],
					'permission_callback' => [ REST_Controller::class, 'require_manage' ],
				],
				[
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => [ self::class, 'delete' ],
					'permission_callback' => [ REST_Controller::class, 'require_manage' ],
				],
			]
		);
	}

	public static function index( \WP_REST_Request $req ) {
		$service_id = (int) $req->get_param( 'service_id' );
		$rows       = $service_id ? Staff_Repository::for_service( $service_id ) : Staff_Repository::all( [ 'status' => 'active' ] );
		$is_admin   = REST_Controller::require_manage();

		return rest_ensure_response(
			array_map( static fn( $r ) => self::expose( $r, $is_admin ), $rows )
		);
	}

	public static function show( \WP_REST_Request $req ) {
		$row = Staff_Repository::find( (int) $req['id'] );
		if ( ! $row ) {
			return new \WP_Error( 'not_found', __( 'Staff not found.', 'bookingistic' ), [ 'status' => 404 ] );
		}
		$is_admin = REST_Controller::require_manage();
		if ( ! $is_admin && $row['status'] !== 'active' ) {
			return new \WP_Error( 'not_found', __( 'Staff not found.', 'bookingistic' ), [ 'status' => 404 ] );
		}
		return rest_ensure_response( self::expose( $row, $is_admin ) );
	}

	public static function create( \WP_REST_Request $req ) {
		$row = Staff_Repository::create( $req->get_json_params() ?: $req->get_params() );
		if ( is_wp_error( $row ) ) {
			return $row;
		}
		return rest_ensure_response( self::expose( $row, true ) );
	}

	public static function patch( \WP_REST_Request $req ) {
		$row = Staff_Repository::update( (int) $req['id'], $req->get_json_params() ?: $req->get_params() );
		if ( is_wp_error( $row ) ) {
			return $row;
		}
		return rest_ensure_response( self::expose( $row, true ) );
	}

	public static function delete( \WP_REST_Request $req ) {
		$ok = Staff_Repository::delete( (int) $req['id'] );
		return rest_ensure_response( [ 'deleted' => $ok ] );
	}

	private static function expose( array $row, bool $admin ): array {
		if ( ! $admin ) {
			// Public response: drop internal scheduling data + contact info.
			$row['services'] = Staff_Repository::services_for_staff( (int) $row['id'] );
			unset(
				$row['days_off'],
				$row['special_availability'],
				$row['booking_limit_per_day'],
				$row['working_hours'],
				$row['phone'],
				$row['user_id'],
				$row['created_at'],
				$row['updated_at']
			);
		}
		return $row;
	}
}
