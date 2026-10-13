<?php
/**
 * WooCommerce payment gateway — ledger adapter over the proven order flow.
 *
 * The WooCommerce_Integration owns order creation + booking status flips
 * (E2E-verified). This gateway layers the payment_records ledger on top,
 * and lets site admins toggle WooCommerce as the payment method for paid
 * bookings from Bookingistic → Payments (independent of the integration
 * toggle — enabling either keeps the same order lifecycle).
 *
 * @package Bookingistic
 */

namespace Bookingistic\Payments\Gateways;

use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Tables;
use Bookingistic\Integrations\WooCommerce\WooCommerce_Integration;
use Bookingistic\Payments\Payment_Gateway;
use Bookingistic\Payments\Payment_Manager;

defined( 'ABSPATH' ) || exit;

class WooCommerce_Gateway implements Payment_Gateway {

	private ?WooCommerce_Integration $integration = null;

	public function slug(): string {
		return 'woocommerce';
	}

	public function label(): string {
		return __( 'WooCommerce Orders', 'bookingistic' );
	}

	public function description(): string {
		return __( 'Collect payment for paid bookings through a WooCommerce order per booking. Customers pay via any WooCommerce-enabled gateway; payment status flows back to the booking automatically.', 'bookingistic' );
	}

	public function is_available(): bool {
		return class_exists( '\\WooCommerce' ) || function_exists( 'WC' );
	}

	public function is_enabled(): bool {
		return Payment_Manager::is_gateway_enabled( $this->slug() );
	}

	public function boot(): void {
		// Ledger entries — run after the integration's own handlers (10/20).
		add_action( 'bookingistic_booking_submitted', [ $this, 'ledger_pending' ], 30, 3 );
		add_action( 'woocommerce_order_status_processing', [ $this, 'ledger_paid' ], 20 );
		add_action( 'woocommerce_order_status_completed', [ $this, 'ledger_paid' ], 20 );
		add_action( 'woocommerce_order_status_refunded', [ $this, 'ledger_refunded' ], 20 );
		add_action( 'woocommerce_order_status_cancelled', [ $this, 'ledger_cancelled' ], 20 );
	}

	/**
	 * Order created (or integration disabled → create it here so enabling
	 * the gateway alone is enough), then open a `pending` ledger record.
	 */
	public function ledger_pending( array $booking, $customer = null, $service = null ): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$integration = $this->integration();
		$integration->create_order( $booking, $customer, $service );

		$order_id = $this->order_id_for_booking( (int) $booking['id'] );
		if ( ! $order_id ) {
			return;
		}

		Payment_Manager::record_event(
			(int) $booking['id'],
			$this->slug(),
			'pending',
			(float) $booking['payment_amount'],
			(string) $order_id,
			[ 'wc_order_id' => $order_id ]
		);
	}

	public function ledger_paid( int $order_id ): void {
		$this->ledger_transition( $order_id, 'paid' );
	}

	public function ledger_refunded( int $order_id ): void {
		$this->ledger_transition( $order_id, 'refunded' );
	}

	public function ledger_cancelled( int $order_id ): void {
		$this->ledger_transition( $order_id, 'cancelled' );
	}

	/* ===== Helpers ===== */

	private function ledger_transition( int $order_id, string $status ): void {
		if ( ! $this->is_enabled() ) {
			return;
		}
		$booking_id = $this->booking_id_for_order( $order_id );
		if ( ! $booking_id ) {
			return;
		}
		$booking = Booking_Repository::find( $booking_id );
		if ( ! $booking ) {
			return;
		}
		Payment_Manager::record_event( $booking_id, $this->slug(), $status, (float) $booking['payment_amount'], (string) $order_id, [ 'wc_order_id' => $order_id ] );
	}

	private function integration(): WooCommerce_Integration {
		if ( null === $this->integration ) {
			$this->integration = new WooCommerce_Integration();
		}
		return $this->integration;
	}

	private function order_id_for_booking( int $booking_id ): int {
		global $wpdb;
		$table = Tables::booking_meta_table();
		$value = $wpdb->get_var(
			$wpdb->prepare( "SELECT meta_value FROM {$table} WHERE booking_id = %d AND meta_key = '_wc_order_id' ORDER BY id DESC LIMIT 1", $booking_id ) // phpcs:ignore
		);
		return $value ? (int) $value : 0;
	}

	private function booking_id_for_order( int $order_id ): int {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return 0;
		}
		return (int) $order->get_meta( WooCommerce_Integration::META_BOOKING_ID );
	}
}
