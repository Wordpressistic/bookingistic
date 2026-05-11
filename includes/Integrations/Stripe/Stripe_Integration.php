<?php
/**
 * Stripe direct payments — premium-tier integration.
 *
 * Flow:
 *  1. Booking submitted on a paid service → create a Stripe Checkout
 *     Session via the Stripe REST API, store the session id in booking
 *     meta, and expose the URL as {payment_link} in customer emails.
 *  2. Stripe POSTs `checkout.session.completed` to our webhook endpoint:
 *     `/wp-json/bookingistic/v1/stripe/webhook` — signature-verified
 *     against the configured webhook secret. We flip the booking
 *     payment_status to `paid` and confirm it if still pending.
 *  3. `charge.refunded` → payment_status `refunded`.
 *
 * Requires an active license (License_Manager::is_active()).
 *
 * @package Bookingistic
 */

namespace Bookingistic\Integrations\Stripe;

use Bookingistic\Core\License_Manager;
use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Customer_Repository;
use Bookingistic\Database\Service_Repository;
use Bookingistic\Database\Tables;
use Bookingistic\Integrations\Integration_Base;

defined( 'ABSPATH' ) || exit;

class Stripe_Integration extends Integration_Base {

	private const API_BASE         = 'https://api.stripe.com/v1';
	public const META_SESSION_ID   = '_stripe_session_id';
	public const META_PAYMENT_INT  = '_stripe_payment_intent';

	public function slug(): string { return 'stripe'; }
	public function label(): string { return __( 'Stripe payments', 'bookingistic' ); }

	public function description(): string {
		return __( 'Charge customers directly through Stripe Checkout when they book a paid service. Premium licence required.', 'bookingistic' );
	}

	public function fields(): array {
		return [
			'publishable_key' => [
				'label'       => __( 'Publishable key', 'bookingistic' ),
				'type'        => 'text',
				'placeholder' => 'pk_live_...',
			],
			'secret_key' => [
				'label'       => __( 'Secret key', 'bookingistic' ),
				'type'        => 'password',
				'placeholder' => 'sk_live_...',
				'description' => __( 'Stored securely; never echoed back to the browser after save.', 'bookingistic' ),
			],
			'webhook_secret' => [
				'label'       => __( 'Webhook signing secret', 'bookingistic' ),
				'type'        => 'password',
				'placeholder' => 'whsec_...',
				'description' => __( 'Point your Stripe webhook at /wp-json/bookingistic/v1/stripe/webhook and paste the signing secret here.', 'bookingistic' ),
			],
			'currency' => [
				'label'       => __( 'Currency (ISO 4217)', 'bookingistic' ),
				'type'        => 'text',
				'placeholder' => 'USD',
			],
			'success_url' => [
				'label'       => __( 'Success redirect URL', 'bookingistic' ),
				'type'        => 'url',
				'placeholder' => home_url( '/booking-confirmed/' ),
			],
			'cancel_url' => [
				'label'       => __( 'Cancel redirect URL', 'bookingistic' ),
				'type'        => 'url',
				'placeholder' => home_url( '/booking-cancelled/' ),
			],
		];
	}

	public function is_available(): bool {
		return License_Manager::is_active();
	}

	public function boot(): void {
		add_action( 'bookingistic_booking_submitted', [ $this, 'create_checkout' ], 30, 3 );
		add_filter( 'bookingistic_booking_email_vars', [ $this, 'inject_payment_link' ], 20, 4 );
		add_action( 'rest_api_init', [ $this, 'register_webhook' ] );
	}

	public function create_checkout( array $booking, $customer = null, $service = null ): void {
		if ( $booking['payment_status'] !== 'pending' || (float) $booking['payment_amount'] <= 0 ) {
			return;
		}
		if ( $this->get_meta( (int) $booking['id'], self::META_SESSION_ID ) ) {
			return;
		}
		$secret = (string) $this->setting( 'secret_key' );
		if ( ! $secret ) {
			return;
		}

		$customer = $customer ?: Customer_Repository::find( (int) $booking['customer_id'] );
		$service  = $service ?: Service_Repository::find( (int) $booking['service_id'] );
		if ( ! $service ) {
			return;
		}

		$currency = strtolower( (string) ( $this->setting( 'currency', 'USD' ) ?: 'USD' ) );
		$cents    = (int) round( ( (float) $booking['payment_amount'] ) * 100 );

		$body = [
			'mode'                                 => 'payment',
			'success_url'                          => (string) ( $this->setting( 'success_url' ) ?: home_url( '/' ) ),
			'cancel_url'                           => (string) ( $this->setting( 'cancel_url' )  ?: home_url( '/' ) ),
			'customer_email'                       => $customer['email'] ?? '',
			'metadata[bookingistic_booking_id]'    => (string) $booking['id'],
			'line_items[0][quantity]'              => '1',
			'line_items[0][price_data][currency]'  => $currency,
			'line_items[0][price_data][unit_amount]'           => (string) $cents,
			'line_items[0][price_data][product_data][name]'    => $service['name'],
		];

		$res = wp_remote_post(
			self::API_BASE . '/checkout/sessions',
			[
				'timeout' => 12,
				'headers' => [
					'Authorization' => 'Bearer ' . $secret,
					'Content-Type'  => 'application/x-www-form-urlencoded',
				],
				'body'    => $body,
			]
		);

		if ( is_wp_error( $res ) ) {
			return;
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $data ) || empty( $data['id'] ) || empty( $data['url'] ) ) {
			return;
		}

		$this->set_meta( (int) $booking['id'], self::META_SESSION_ID, $data['id'] );
		$this->set_meta( (int) $booking['id'], '_stripe_url', $data['url'] );
	}

	public function inject_payment_link( array $vars, array $booking, $customer, $service ): array {
		if ( empty( $vars['payment_link'] ) ) {
			$url = $this->get_meta( (int) $booking['id'], '_stripe_url' );
			if ( $url ) {
				$vars['payment_link'] = $url;
			}
		}
		return $vars;
	}

	public function register_webhook(): void {
		register_rest_route(
			BOOKINGISTIC_REST_NAMESPACE,
			'/stripe/webhook',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_webhook' ],
				'permission_callback' => '__return_true', // signature-verified below
			]
		);
	}

	public function handle_webhook( \WP_REST_Request $req ) {
		$payload = $req->get_body();
		$sig     = $req->get_header( 'stripe-signature' );
		$secret  = (string) $this->setting( 'webhook_secret' );

		if ( ! $this->verify_signature( $payload, (string) $sig, $secret ) ) {
			return new \WP_Error( 'invalid_signature', 'Invalid signature', [ 'status' => 400 ] );
		}

		$event = json_decode( $payload, true );
		if ( ! is_array( $event ) || empty( $event['type'] ) ) {
			return new \WP_Error( 'invalid_event', 'Invalid event', [ 'status' => 400 ] );
		}

		$type = (string) $event['type'];
		$obj  = $event['data']['object'] ?? [];

		if ( $type === 'checkout.session.completed' ) {
			$booking_id = (int) ( $obj['metadata']['bookingistic_booking_id'] ?? 0 );
			if ( $booking_id ) {
				$booking = Booking_Repository::find( $booking_id );
				if ( $booking ) {
					Booking_Repository::update(
						$booking_id,
						[
							'payment_status' => 'paid',
							'status'         => $booking['status'] === 'pending' ? 'confirmed' : $booking['status'],
						]
					);
					$this->set_meta( $booking_id, self::META_PAYMENT_INT, (string) ( $obj['payment_intent'] ?? '' ) );
					do_action( 'bookingistic_booking_confirmed', Booking_Repository::find( $booking_id ), null, null );
				}
			}
		} elseif ( $type === 'charge.refunded' ) {
			$pi = (string) ( $obj['payment_intent'] ?? '' );
			$booking_id = $this->booking_id_by_payment_intent( $pi );
			if ( $booking_id ) {
				Booking_Repository::update( $booking_id, [ 'payment_status' => 'refunded' ] );
			}
		}

		return rest_ensure_response( [ 'received' => true ] );
	}

	/** Stripe webhook signature verification. */
	private function verify_signature( string $payload, string $sig_header, string $secret ): bool {
		if ( ! $secret || ! $sig_header ) {
			return false;
		}
		$parts = [];
		foreach ( explode( ',', $sig_header ) as $kv ) {
			[ $k, $v ] = array_pad( explode( '=', trim( $kv ), 2 ), 2, '' );
			$parts[ $k ][] = $v;
		}
		$timestamp = $parts['t'][0] ?? '';
		$signatures = $parts['v1'] ?? [];
		if ( ! $timestamp || ! $signatures ) {
			return false;
		}
		// Reject events older than 5 minutes to prevent replay.
		if ( abs( time() - (int) $timestamp ) > 300 ) {
			return false;
		}
		$expected = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );
		foreach ( $signatures as $candidate ) {
			if ( hash_equals( $expected, $candidate ) ) {
				return true;
			}
		}
		return false;
	}

	/* ===== meta helpers ===== */

	private function set_meta( int $booking_id, string $key, string $value ): void {
		global $wpdb;
		$wpdb->insert(
			Tables::booking_meta_table(),
			[ 'booking_id' => $booking_id, 'meta_key' => $key, 'meta_value' => $value ],
			[ '%d', '%s', '%s' ]
		);
	}

	private function get_meta( int $booking_id, string $key ): ?string {
		global $wpdb;
		$value = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT meta_value FROM ' . Tables::booking_meta_table() . ' WHERE booking_id = %d AND meta_key = %s ORDER BY id DESC LIMIT 1',
				$booking_id,
				$key
			)
		);
		return $value !== null ? (string) $value : null;
	}

	private function booking_id_by_payment_intent( string $pi ): int {
		if ( ! $pi ) {
			return 0;
		}
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT booking_id FROM ' . Tables::booking_meta_table() . ' WHERE meta_key = %s AND meta_value = %s ORDER BY id DESC LIMIT 1',
				self::META_PAYMENT_INT,
				$pi
			)
		);
	}
}
