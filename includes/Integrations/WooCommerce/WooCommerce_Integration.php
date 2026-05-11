<?php
/**
 * WooCommerce integration — handle payment-on-booking via WooCommerce orders.
 *
 * Flow:
 *  1. Booking submitted with payment_status='pending' AND service price > 0
 *     → create a WC order (one fee line item) and store wc_order_id in
 *     booking meta + return the checkout URL via {payment_link} email var.
 *  2. WC order marked processing / completed → flip booking payment_status
 *     to 'paid' and confirm the booking if still pending.
 *  3. WC order refunded → flip booking payment_status to 'refunded'.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Integrations\WooCommerce;

use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Customer_Repository;
use Bookingistic\Database\Service_Repository;
use Bookingistic\Database\Tables;
use Bookingistic\Integrations\Integration_Base;

defined( 'ABSPATH' ) || exit;

class WooCommerce_Integration extends Integration_Base {

	public const META_BOOKING_ID = '_bookingistic_booking_id';

	public function slug(): string { return 'woocommerce'; }
	public function label(): string { return __( 'WooCommerce', 'bookingistic' ); }

	public function description(): string {
		return __( 'When a paid service is booked, create a WooCommerce order for it. WooCommerce status changes flip the booking payment status automatically.', 'bookingistic' );
	}

	public function fields(): array {
		return [
			'auto_complete_virtual' => [
				'label'       => __( 'Auto-complete the WooCommerce order on payment', 'bookingistic' ),
				'type'        => 'checkbox',
				'description' => __( 'Useful for fully virtual / consultation services that don\'t need shipping.', 'bookingistic' ),
			],
		];
	}

	public function is_available(): bool {
		return class_exists( '\\WooCommerce' ) || function_exists( 'WC' );
	}

	public function boot(): void {
		add_action( 'bookingistic_booking_submitted', [ $this, 'create_order' ], 20, 3 );
		add_action( 'woocommerce_order_status_processing', [ $this, 'on_payment_received' ] );
		add_action( 'woocommerce_order_status_completed', [ $this, 'on_payment_received' ] );
		add_action( 'woocommerce_order_status_refunded', [ $this, 'on_order_refunded' ] );
		add_action( 'woocommerce_order_status_cancelled', [ $this, 'on_order_cancelled' ] );

		add_filter( 'bookingistic_booking_email_vars', [ $this, 'inject_payment_link' ], 10, 4 );
	}

	public function create_order( array $booking, $customer = null, $service = null ): void {
		if ( $booking['payment_status'] !== 'pending' || (float) $booking['payment_amount'] <= 0 ) {
			return;
		}
		if ( $this->wc_order_for_booking( (int) $booking['id'] ) ) {
			return;
		}

		$customer = $customer ?: Customer_Repository::find( (int) $booking['customer_id'] );
		$service  = $service ?: Service_Repository::find( (int) $booking['service_id'] );
		if ( ! $customer || ! $service ) {
			return;
		}

		$order = wc_create_order(
			[
				'status'      => 'pending',
				'customer_id' => 0,
			]
		);

		// Fee item — one line, named after the service.
		$fee = new \WC_Order_Item_Fee();
		$fee->set_name( $service['name'] . ' · #' . (int) $booking['id'] );
		$fee->set_amount( (float) $service['price'] );
		$fee->set_total( (float) $service['price'] );
		$fee->set_tax_status( 'none' );
		$order->add_item( $fee );

		$order->set_address(
			[
				'first_name' => $customer['first_name'] ?? '',
				'last_name'  => $customer['last_name'] ?? '',
				'email'      => $customer['email'] ?? '',
				'phone'      => $customer['phone'] ?? '',
				'company'    => $customer['company'] ?? '',
			],
			'billing'
		);

		$order->update_meta_data( self::META_BOOKING_ID, (int) $booking['id'] );
		$order->calculate_totals();
		$order->save();

		// Persist back into booking_meta for easy lookup.
		$this->set_booking_meta( (int) $booking['id'], '_wc_order_id', (string) $order->get_id() );
	}

	public function on_payment_received( int $order_id ): void {
		$booking_id = $this->booking_id_for_order( $order_id );
		if ( ! $booking_id ) {
			return;
		}
		$booking = Booking_Repository::find( $booking_id );
		if ( ! $booking ) {
			return;
		}
		Booking_Repository::update(
			$booking_id,
			[
				'payment_status' => 'paid',
				'status'         => $booking['status'] === 'pending' ? 'confirmed' : $booking['status'],
			]
		);

		if ( $this->setting( 'auto_complete_virtual' ) ) {
			$order = wc_get_order( $order_id );
			if ( $order && $order->get_status() === 'processing' ) {
				$order->update_status( 'completed', __( 'Bookingistic: virtual service auto-completed.', 'bookingistic' ) );
			}
		}

		do_action( 'bookingistic_booking_confirmed', Booking_Repository::find( $booking_id ), null, null );
	}

	public function on_order_refunded( int $order_id ): void {
		$booking_id = $this->booking_id_for_order( $order_id );
		if ( $booking_id ) {
			Booking_Repository::update( $booking_id, [ 'payment_status' => 'refunded' ] );
		}
	}

	public function on_order_cancelled( int $order_id ): void {
		$booking_id = $this->booking_id_for_order( $order_id );
		if ( $booking_id ) {
			Booking_Repository::update( $booking_id, [ 'payment_status' => 'failed' ] );
		}
	}

	public function inject_payment_link( array $vars, array $booking, $customer, $service ): array {
		$order_id = $this->wc_order_for_booking( (int) $booking['id'] );
		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order && empty( $vars['payment_link'] ) ) {
				$vars['payment_link'] = $order->get_checkout_payment_url();
			}
		}
		return $vars;
	}

	/* ===== Helpers ===== */

	private function wc_order_for_booking( int $booking_id ): int {
		$id = $this->get_booking_meta( $booking_id, '_wc_order_id' );
		return $id ? (int) $id : 0;
	}

	private function booking_id_for_order( int $order_id ): int {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return 0;
		}
		return (int) $order->get_meta( self::META_BOOKING_ID );
	}

	private function set_booking_meta( int $booking_id, string $key, string $value ): void {
		global $wpdb;
		$table = Tables::booking_meta_table();
		$wpdb->insert(
			$table,
			[ 'booking_id' => $booking_id, 'meta_key' => $key, 'meta_value' => $value ],
			[ '%d', '%s', '%s' ]
		);
	}

	private function get_booking_meta( int $booking_id, string $key ): ?string {
		global $wpdb;
		$table = Tables::booking_meta_table();
		$value = $wpdb->get_var(
			$wpdb->prepare( "SELECT meta_value FROM {$table} WHERE booking_id = %d AND meta_key = %s ORDER BY id DESC LIMIT 1", $booking_id, $key ) // phpcs:ignore
		);
		return $value !== null ? (string) $value : null;
	}
}
