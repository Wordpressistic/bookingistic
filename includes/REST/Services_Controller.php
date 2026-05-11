<?php
/**
 * Services REST controller.
 *
 * GET  /services  — public (only active services exposed)
 * POST /services  — manage_bookingistic
 * GET  /services/{id} — public (only active)
 * PUT  /services/{id} — manage
 * DELETE /services/{id} — manage
 *
 * @package Bookingistic
 */

namespace Bookingistic\REST;

use Bookingistic\Database\Service_Repository;

defined( 'ABSPATH' ) || exit;

class Services_Controller {

	public static function register_routes(): void {
		$ns = BOOKINGISTIC_REST_NAMESPACE;

		register_rest_route(
			$ns,
			'/services',
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
			'/services/(?P<id>\d+)',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ self::class, 'show' ],
					'permission_callback' => '__return_true',
				],
				[
					'methods'             => [ 'PUT', 'PATCH' ],
					'callback'            => [ self::class, 'update' ],
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
		$is_admin = REST_Controller::require_manage();
		$rows = Service_Repository::all( $is_admin ? [] : [ 'status' => 'active' ] );
		return rest_ensure_response(
			array_map(
				static fn( $r ) => self::expose( $r, $is_admin ),
				$rows
			)
		);
	}

	public static function show( \WP_REST_Request $req ) {
		$id      = (int) $req['id'];
		$service = Service_Repository::find( $id );
		if ( ! $service ) {
			return new \WP_Error( 'not_found', __( 'Service not found.', 'bookingistic' ), [ 'status' => 404 ] );
		}
		$is_admin = REST_Controller::require_manage();
		if ( ! $is_admin && $service['status'] !== 'active' ) {
			return new \WP_Error( 'not_found', __( 'Service not found.', 'bookingistic' ), [ 'status' => 404 ] );
		}
		return rest_ensure_response( self::expose( $service, $is_admin ) );
	}

	public static function create( \WP_REST_Request $req ) {
		$service = Service_Repository::create( $req->get_json_params() ?: $req->get_params() );
		if ( is_wp_error( $service ) ) {
			return $service;
		}
		return rest_ensure_response( self::expose( $service, true ) );
	}

	public static function update( \WP_REST_Request $req ) {
		$service = Service_Repository::update( (int) $req['id'], $req->get_json_params() ?: $req->get_params() );
		if ( is_wp_error( $service ) ) {
			return $service;
		}
		return rest_ensure_response( self::expose( $service, true ) );
	}

	public static function delete( \WP_REST_Request $req ) {
		$ok = Service_Repository::delete( (int) $req['id'] );
		return rest_ensure_response( [ 'deleted' => $ok ] );
	}

	private static function expose( array $service, bool $admin ): array {
		if ( ! $admin ) {
			unset( $service['created_at'], $service['updated_at'], $service['sort_order'] );
		}
		return $service;
	}
}
