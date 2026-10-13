<?php
/**
 * Payments manager — gateway registry + booking payment lifecycle.
 *
 * Gateways are pluggable via the `bookingistic_payment_gateways` filter
 * (alias: `bookingistic_gateways`). Each gateway implements Payment_Gateway.
 * The manager records every transition in the payment_records ledger and
 * applies the resulting booking payment_status + status transitions.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Payments;

use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Payment_Repository;
use Bookingistic\Database\Service_Repository;

defined( 'ABSPATH' ) || exit;

class Payment_Manager {

	/** @var Payment_Gateway[]|null */
	private static ?array $gateways = null;

	/**
	 * Registered gateways, keyed by slug. Default set ships WooCommerce
	 * (the proven order-based flow). Additional gateways (Stripe direct,
	 * PayPal, Razorpay…) plug in via the filter — none are enabled by
	 * default and no third-party billing decision is made in free core.
	 *
	 * @return array<string,Payment_Gateway>
	 */
	public static function gateways(): array {
		if ( null === self::$gateways ) {
			$defaults = [];

			if ( class_exists( '\\WooCommerce' ) || function_exists( 'WC' ) ) {
				$defaults['woocommerce'] = new Gateways\WooCommerce_Gateway();
			}

			/**
			 * Filter the payment gateway registry.
			 *
			 * @param array<string,Payment_Gateway> $defaults
			 */
			$gateways = apply_filters( 'bookingistic_payment_gateways', $defaults );

			/**
			 * Alias filter (short name) — receives the same registry. Results
			 * merge; both filters exist so integrations can use either name.
			 *
			 * @param array<string,Payment_Gateway> $gateways
			 */
			$gateways = apply_filters( 'bookingistic_gateways', $gateways );

			$clean = [];
			foreach ( (array) $gateways as $slug => $gateway ) {
				if ( $gateway instanceof Payment_Gateway && $gateway->is_available() ) {
					$clean[ $slug ] = $gateway;
				}
			}
			self::$gateways = $clean;
		}
		return self::$gateways;
	}

	public static function gateway( string $slug ): ?Payment_Gateway {
		return self::gateways()[ $slug ] ?? null;
	}

	/**
	 * Amount due for a booking: full price, or deposit when the service
	 * sets one (deposit_percent 1-100).
	 */
	public static function amount_due( array $booking ): float {
		$amount = (float) ( $booking['payment_amount'] ?? 0 );
		if ( $amount <= 0 ) {
			return 0.0;
		}
		$service = Service_Repository::find( (int) $booking['service_id'] );
		$percent = $service ? (float) ( $service['deposit_percent'] ?? 0 ) : 0.0;
		if ( $percent <= 0 || $percent >= 100 ) {
			return round( $amount, 2 );
		}
		return round( $amount * $percent / 100, 2 );
	}

	/**
	 * Ledger + booking update when a gateway reports a payment event.
	 *
	 * Status lifecycle: pending → paid | failed | refunded | cancelled.
	 * Booking status map: first `paid` event confirms a pending booking;
	 * `refunded`/`failed` never auto-cancel (admin decides).
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $provider   Gateway slug.
	 * @param string $status     pending|paid|failed|refunded|cancelled (record status).
	 * @param float  $amount     Amount in the booking/service currency.
	 * @param string $external_id Provider-side reference (order id, charge id…).
	 * @param mixed  $payload    Raw provider payload (JSON-encoded into the ledger).
	 */
	public static function record_event( int $booking_id, string $provider, string $status, float $amount, string $external_id = '', $payload = null ): void {
		$booking = Booking_Repository::find( $booking_id );
		if ( ! $booking ) {
			return;
		}

		$existing = Payment_Repository::latest_for_booking( $booking_id, $provider );

		if ( $existing ) {
			Payment_Repository::mark( (int) $existing['id'], $status, $status . ' · ' . $external_id );
			$record_id = (int) $existing['id'];
		} else {
			$record_id = Payment_Repository::create(
				[
					'booking_id'  => $booking_id,
					'provider'    => $provider,
					'external_id' => $external_id,
					'amount'      => $amount,
					'currency'    => self::currency(),
					'status'      => $status,
					'payload'     => $payload,
				]
			);
		}

		// Map ledger status → booking payment_status (keeps both in sync).
		$booking_payment_status = in_array( $status, [ 'paid', 'refunded', 'failed' ], true ) ? $status : $booking['payment_status'];

		$update = [];
		if ( $booking_payment_status !== $booking['payment_status'] ) {
			$update['payment_status'] = $booking_payment_status;
		}
		// First paid event on a still-pending booking confirms it (same rule the WC integration applies).
		if ( 'paid' === $status && 'pending' === $booking['status'] ) {
			$update['status'] = 'confirmed';
		}
		if ( ! empty( $update ) ) {
			Booking_Repository::update( $booking_id, $update );
		}

		/**
		 * Fires after a payment event has been recorded on a booking.
		 *
		 * @param int   $booking_id
		 * @param array $record     Ledger row (or empty when ledger write failed).
		 * @param array $update     Booking fields that were changed.
		 */
		do_action( 'bookingistic_payment_recorded', $booking_id, Payment_Repository::find( $record_id ) ?: [], $update );
	}

	/**
	 * Display currency. Free core has no global currency setting yet —
	 * gateways resolve their own (WooCommerce uses the WC store currency).
	 * Filter lets premium/hosts pin one.
	 */
	public static function currency(): string {
		$currency = '';
		if ( function_exists( 'get_woocommerce_currency' ) ) {
			$currency = (string) get_woocommerce_currency();
		}

		/**
		 * Filter the currency used for payment ledger entries.
		 *
		 * @param string $currency
		 */
		return strtoupper( substr( (string) apply_filters( 'bookingistic_payment_currency', $currency ), 0, 10 ) );
	}

	/**
	 * Payment settings (per-gateway enable map) under the main settings option.
	 */
	public static function enabled_gateways(): array {
		$settings = (array) get_option( 'bookingistic_settings', [] );
		$enabled  = (array) ( $settings['payment_gateways'] ?? [] );
		return array_intersect_key( $enabled, self::gateways() );
	}

	public static function is_gateway_enabled( string $slug ): bool {
		$enabled = self::enabled_gateways();
		return ! empty( $enabled[ $slug ] );
	}

	/**
	 * Whether at least one gateway is enabled AND able to take payments now.
	 */
	public static function has_active_gateway(): bool {
		foreach ( self::gateways() as $gateway ) {
			if ( $gateway->is_enabled() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Hosted payment URL for a booking (gateway-provided), or '' when the
	 * booking needs no payment / no gateway is connected.
	 */
	public static function payment_url( array $booking ): string {
		if ( 'pending' !== $booking['payment_status'] || (float) ( $booking['payment_amount'] ?? 0 ) <= 0 ) {
			return '';
		}
		$record = Payment_Repository::latest_for_booking( (int) $booking['id'] );
		if ( ! $record || 'woocommerce' !== $record['provider'] ) {
			return '';
		}
		$gateway = self::gateway( (string) $record['provider'] );
		if ( ! $gateway instanceof Gateways\WooCommerce_Gateway ) {
			return '';
		}
		return $gateway->checkout_url( (int) $booking['id'] );
	}
}
