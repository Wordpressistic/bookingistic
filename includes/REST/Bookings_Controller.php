<?php
/**
 * Bookings REST controller.
 *
 * POST /bookings — public submit (rate-limited, honeypot)
 * GET  /bookings — manage only
 * GET  /bookings/{id} — manage only
 * PATCH /bookings/{id} — manage only (status / notes)
 * POST /bookings/{id}/reschedule — public-with-token (uses booking reschedule_token)
 *
 * @package Bookingistic
 */

namespace Bookingistic\REST;

use Bookingistic\Booking\Booking_Manager;
use Bookingistic\Booking\Reschedule_Manager;
use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Customer_Repository;
use Bookingistic\Database\Service_Repository;
use Bookingistic\Helpers\Sanitizer;
use Bookingistic\Helpers\Security;

defined( 'ABSPATH' ) || exit;

class Bookings_Controller {

	public static function register_routes(): void {
		$ns = BOOKINGISTIC_REST_NAMESPACE;

		register_rest_route(
			$ns,
			'/bookings',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ self::class, 'create' ],
					'permission_callback' => '__return_true',
					'args'                => self::create_args(),
				],
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ self::class, 'index' ],
					'permission_callback' => [ REST_Controller::class, 'require_manage' ],
				],
			]
		);

		register_rest_route(
			$ns,
			'/bookings/(?P<id>\d+)',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ self::class, 'show' ],
					'permission_callback' => [ REST_Controller::class, 'require_manage' ],
				],
				[
					'methods'             => [ 'PATCH', 'PUT' ],
					'callback'            => [ self::class, 'patch' ],
					'permission_callback' => [ REST_Controller::class, 'require_manage' ],
				],
			]
		);

		register_rest_route(
			$ns,
			'/bookings/(?P<id>\d+)/reschedule',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ self::class, 'reschedule' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	private static function create_args(): array {
		return [
			'service_id' => [ 'required' => true,  'sanitize_callback' => 'absint' ],
			'staff_id'   => [ 'required' => false, 'sanitize_callback' => 'absint' ],
			'start_utc'  => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
			'name'       => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
			'email'      => [ 'required' => true,  'sanitize_callback' => 'sanitize_email' ],
			'phone'      => [ 'required' => false, 'sanitize_callback' => [ Sanitizer::class, 'phone' ] ],
			'company'    => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
			'timezone'   => [ 'required' => false, 'sanitize_callback' => [ Sanitizer::class, 'timezone' ] ],
			'notes'      => [ 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ],
		];
	}

	public static function create( \WP_REST_Request $req ) {
		$params = $req->get_params();

		if ( Security::honeypot_filled( $params ) ) {
			return rest_ensure_response( [ 'ok' => true, 'silent' => true ] );
		}
		if ( ! Security::rate_limit_ok( 'bookings', 5, HOUR_IN_SECONDS ) ) {
			return new \WP_Error( 'rate_limited', __( 'Too many requests. Try again in an hour.', 'bookingistic' ), [ 'status' => 429 ] );
		}
		if ( ! is_email( $params['email'] ?? '' ) ) {
			return new \WP_Error( 'bad_email', __( 'A valid email is required.', 'bookingistic' ), [ 'status' => 422 ] );
		}

		$first = $params['name'];
		$last  = '';
		if ( strpos( $params['name'], ' ' ) !== false ) {
			[ $first, $last ] = array_pad( explode( ' ', $params['name'], 2 ), 2, '' );
		}

		$result = Booking_Manager::create_booking(
			[
				'service_id' => (int) $params['service_id'],
				'staff_id'   => (int) ( $params['staff_id'] ?? 0 ),
				'start_utc'  => $params['start_utc'],
				'timezone'   => $params['timezone'] ?? '',
				'customer'   => [
					'first_name' => $first,
					'last_name'  => $last,
					'email'      => $params['email'],
					'phone'      => $params['phone'] ?? '',
					'company'    => $params['company'] ?? '',
					'timezone'   => $params['timezone'] ?? '',
					'notes'      => $params['notes'] ?? '',
					'source'     => 'rest',
				],
				'notes'      => $params['notes'] ?? '',
				'source'     => 'rest',
			]
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			[
				'ok'      => true,
				'id'      => $result['booking']['id'],
				'status'  => $result['booking']['status'],
				'message' => $result['booking']['status'] === 'confirmed'
					? __( 'Booking confirmed. Check your inbox for the details.', 'bookingistic' )
					: __( 'Booking received. We will confirm shortly.', 'bookingistic' ),
			]
		);
	}

	public static function index( \WP_REST_Request $req ) {
		$args = [
			'page'       => (int) $req->get_param( 'page' ),
			'per_page'   => (int) $req->get_param( 'per_page' ),
			'status'     => sanitize_key( (string) $req->get_param( 'status' ) ),
			'service_id' => (int) $req->get_param( 'service_id' ),
			'staff_id'   => (int) $req->get_param( 'staff_id' ),
			'from'       => sanitize_text_field( (string) $req->get_param( 'from' ) ),
			'to'         => sanitize_text_field( (string) $req->get_param( 'to' ) ),
		];
		$result = Booking_Repository::query( array_filter( $args ) );
		return rest_ensure_response( $result );
	}

	public static function show( \WP_REST_Request $req ) {
		$booking = Booking_Repository::find( (int) $req['id'] );
		if ( ! $booking ) {
			return new \WP_Error( 'not_found', __( 'Booking not found.', 'bookingistic' ), [ 'status' => 404 ] );
		}
		$booking['customer'] = Customer_Repository::find( (int) $booking['customer_id'] );
		$booking['service']  = Service_Repository::find( (int) $booking['service_id'] );
		return rest_ensure_response( $booking );
	}

	public static function patch( \WP_REST_Request $req ) {
		$id      = (int) $req['id'];
		$payload = $req->get_json_params() ?: $req->get_params();
		if ( ! empty( $payload['status'] ) ) {
			$result = Booking_Manager::set_status( $id, sanitize_key( $payload['status'] ) );
		} else {
			$result = Booking_Repository::update( $id, $payload );
		}
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_ensure_response( $result );
	}

	public static function reschedule( \WP_REST_Request $req ) {
		$id      = (int) $req['id'];
		$token   = sanitize_text_field( (string) $req->get_param( 'token' ) );
		$start   = sanitize_text_field( (string) $req->get_param( 'start_utc' ) );
		$staff   = (int) $req->get_param( 'staff_id' );

		if ( ! $token || ! $start ) {
			return new \WP_Error( 'bad_request', __( 'Missing token or start_utc.', 'bookingistic' ), [ 'status' => 422 ] );
		}
		if ( ! Security::rate_limit_ok( 'reschedule', 10, HOUR_IN_SECONDS ) ) {
			return new \WP_Error( 'rate_limited', __( 'Too many requests.', 'bookingistic' ), [ 'status' => 429 ] );
		}

		$updated = Reschedule_Manager::apply( $id, $token, $start, $staff );
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}
		return rest_ensure_response( [ 'ok' => true, 'booking' => $updated ] );
	}
}
