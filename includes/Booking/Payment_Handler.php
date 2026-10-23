<?php
/**
 * Customer-facing payment handler.
 *
 * Listens for /?bookingistic_action=pay&id=N&t=TOKEN on any front-end URL.
 * The token is an expiring HMAC (Token::for_payment/payment_token_valid) —
 * no DB state. Valid link → redirect to the connected gateway's hosted
 * checkout (WooCommerce order payment page). No gateway / already paid →
 * friendly notice instead of a dead link. Bad/expired token → 410.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Booking;

use Bookingistic\Database\Booking_Repository;
use Bookingistic\Helpers\Token;
use Bookingistic\Payments\Payment_Manager;

defined( 'ABSPATH' ) || exit;

class Payment_Handler {

	public static function register(): void {
		add_action( 'template_redirect', [ self::class, 'maybe_handle' ] );
	}

	public static function maybe_handle(): void {
		// Public signed-token endpoint: authorization is the expiring HMAC
		// payment token checked below, not a nonce — links arrive by email,
		// outside any admin session.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['bookingistic_action'] ) || sanitize_key( wp_unslash( $_GET['bookingistic_action'] ) ) !== 'pay' ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id    = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token = isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : '';

		if ( ! $id || ! Token::payment_token_valid( $id, $token ) ) {
			wp_die( esc_html__( 'Invalid or expired payment link.', 'bookingistic' ), '', 410 );
		}

		$booking = Booking_Repository::find( $id );
		if ( ! $booking ) {
			wp_die( esc_html__( 'Booking not found.', 'bookingistic' ), '', 410 );
		}

		if ( in_array( $booking['payment_status'], [ 'paid', 'deposit_paid', 'free' ], true ) ) {
			wp_die(
				esc_html__( 'This booking is already paid. No further payment is needed.', 'bookingistic' ),
				'',
				200
			);
		}

		$url = Payment_Manager::payment_url( $booking );
		if ( ! $url ) {
			wp_die(
				esc_html__( 'Online payment is not available for this booking yet. Please contact us to complete payment.', 'bookingistic' ),
				'',
				200
			);
		}

		wp_safe_redirect( $url );
		exit;
	}
}
