<?php
/**
 * Payments REST controller.
 *
 * GET  /payments — ledger list (manage only)
 * GET  /payments/gateways — registry status (manage only)
 *
 * @package Bookingistic
 */

namespace Bookingistic\REST;

use Bookingistic\Booking\Payment_Handler;
use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Payment_Repository;
use Bookingistic\Helpers\Token;
use Bookingistic\Payments\Payment_Manager;

defined( 'ABSPATH' ) || exit;

class Payments_Controller {

	public static function register_routes(): void {
		$ns = BOOKINGISTIC_REST_NAMESPACE;

		register_rest_route(
			$ns,
			'/payments',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ self::class, 'index' ],
					'permission_callback' => [ REST_Controller::class, 'require_manage' ],
					'args'                => [
						'status'   => [ 'sanitize_callback' => 'sanitize_key' ],
						'provider' => [ 'sanitize_callback' => 'sanitize_key' ],
						'per_page' => [ 'sanitize_callback' => 'absint' ],
						'offset'   => [ 'sanitize_callback' => 'absint' ],
					],
				],
			]
		);

		register_rest_route(
			$ns,
			'/payments/status',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ self::class, 'public_status' ],
					'permission_callback' => '__return_true', // signed-token gated.
					'args'                => [
						'id'    => [ 'sanitize_callback' => 'absint' ],
						// Links carry `t`; `token` accepted as an explicit alias.
						't'     => [ 'sanitize_callback' => 'sanitize_text_field' ],
						'token' => [ 'sanitize_callback' => 'sanitize_text_field' ],
					],
				],
			]
		);

		register_rest_route(
			$ns,
			'/payments/gateways',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ self::class, 'gateways' ],
					'permission_callback' => [ REST_Controller::class, 'require_manage' ],
				],
			]
		);
	}

	public static function index( \WP_REST_Request $request ): \WP_REST_Response {
		$args = [
			'status'   => (string) $request->get_param( 'status' ),
			'provider' => (string) $request->get_param( 'provider' ),
			'per_page' => min( 200, max( 1, (int) $request->get_param( 'per_page' ) ?: 50 ) ),
			'offset'   => max( 0, (int) $request->get_param( 'offset' ) ),
		];

		$rows  = Payment_Repository::query( $args );
		$total = Payment_Repository::count( $args );

		// Public-safe shape: ledger has no customer PII beyond booking ref.
		$rows = array_map(
			static fn( array $r ) => [
				'id'          => (int) $r['id'],
				'booking_id'  => (int) $r['booking_id'],
				'provider'    => (string) $r['provider'],
				'external_id' => (string) $r['external_id'],
				'amount'      => (float) $r['amount'],
				'currency'    => (string) $r['currency'],
				'status'      => (string) $r['status'],
				'created_at'  => (string) $r['created_at'],
			],
			$rows
		);

		return new \WP_REST_Response(
			[
				'records' => $rows,
				'total'   => $total,
			]
		);
	}

	/**
	 * Public payment status — same signed-token model as cancel/reschedule.
	 * HMAC token (no DB state); 410 on bad token. No status writes here.
	 */
	public static function public_status( \WP_REST_Request $request ) {
		$id    = (int) $request->get_param( 'id' );
		$token = (string) ( $request->get_param( 't' ) ?: $request->get_param( 'token' ) );

		if ( ! $id || ! Token::payment_token_valid( $id, $token ) ) {
			return new \WP_Error(
				'invalid_token',
				__( 'Invalid or expired payment link.', 'bookingistic' ),
				[ 'status' => 410 ]
			);
		}

		$booking = Booking_Repository::find( $id );
		if ( ! $booking ) {
			return new \WP_Error( 'not_found', __( 'Booking not found.', 'bookingistic' ), [ 'status' => 404 ] );
		}

		$record = Payment_Repository::latest_for_booking( $id );

		return new \WP_REST_Response(
			[
				'booking_id'     => $id,
				'payment_status' => (string) $booking['payment_status'],
				'booking_status' => (string) $booking['status'],
				'amount_due'     => Payment_Manager::amount_due( $booking ),
				'currency'       => $record['currency'] ?? Payment_Manager::currency(),
				'provider'       => (string) ( $record['provider'] ?? '' ),
				'pay_url'        => Token::payment_url( $id ),
			]
		);
	}

	public static function gateways(): \WP_REST_Response {
		$out = [];
		foreach ( Payment_Manager::gateways() as $slug => $gateway ) {
			$out[] = [
				'slug'        => $slug,
				'label'       => $gateway->label(),
				'description' => $gateway->description(),
				'available'   => $gateway->is_available(),
				'enabled'     => $gateway->is_enabled(),
			];
		}
		return new \WP_REST_Response( [ 'gateways' => $out ] );
	}
}
