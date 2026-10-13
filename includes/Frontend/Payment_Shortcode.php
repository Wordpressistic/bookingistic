<?php
/**
 * `[bookingistic_payment]` shortcode — customer pay-now / status block.
 *
 * Attributes:
 *   id     Booking ID (required unless `status` view).
 *   email  Customer email — must match the booking (prevents ID probing).
 *   status Show this literal status word instead of a pay button
 *          (e.g. after email merge-tag flows). Empty = pay button.
 *
 * Renders nothing for bookings that don't need payment.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Frontend;

use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Customer_Repository;
use Bookingistic\Payments\Payment_Manager;

defined( 'ABSPATH' ) || exit;

class Payment_Shortcode {

	public static function register(): void {
		add_shortcode( 'bookingistic_payment', [ self::class, 'render' ] );
	}

	public static function render( $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'id'     => 0,
				'email'  => '',
				'status' => '',
			],
			(array) $atts,
			'bookingistic_payment'
		);

		$id = (int) $atts['id'];
		if ( $id <= 0 ) {
			return '';
		}

		$booking = Booking_Repository::find( $id );
		if ( ! $booking ) {
			return '';
		}

		// Light ownership check: email must match when provided; always
		// required to render any payment detail (ID alone is not enough).
		$email = sanitize_email( (string) $atts['email'] );
		if ( ! $email ) {
			return '';
		}
		$customer = Customer_Repository::find( (int) $booking['customer_id'] );
		if ( ! $customer || strcasecmp( (string) $customer['email'], $email ) !== 0 ) {
			return '<div class="bookingistic-error">' . esc_html__( 'Payment details are available to the booking customer only.', 'bookingistic' ) . '</div>';
		}

		// Status-only display mode.
		if ( '' !== (string) $atts['status'] ) {
			return '<div class="bookingistic-payment-status bookingistic-payment-status--' . esc_attr( sanitize_key( (string) $atts['status'] ) ) . '">'
				. esc_html( ucfirst( sanitize_key( (string) $atts['status'] ) ) )
				. '</div>';
		}

		$payment_status = (string) $booking['payment_status'];
		if ( in_array( $payment_status, [ 'paid', 'deposit_paid', 'free' ], true ) ) {
			return '<div class="bookingistic-payment-status bookingistic-payment-status--paid">' . esc_html__( 'Paid — thank you!', 'bookingistic' ) . '</div>';
		}

		$amount = Payment_Manager::amount_due( $booking );
		if ( $amount <= 0 ) {
			return '';
		}

		$url = Payment_Manager::payment_url( $booking );
		if ( ! $url ) {
			return '<div class="bookingistic-payment-notice">'
				. esc_html( sprintf(
					/* translators: %s: formatted amount. */
					__( 'Payment of %s is due. Please contact us to complete payment.', 'bookingistic' ),
					number_format_i18n( $amount, 2 )
				) )
				. '</div>';
		}

		return '<div class="bookingistic-payment-box">'
			. '<a class="bookingistic-payment-box__button" href="' . esc_url( $url ) . '">'
			. esc_html( sprintf(
				/* translators: %s: formatted amount. */
				__( 'Pay now — %s', 'bookingistic' ),
				number_format_i18n( $amount, 2 )
			) )
			. '</a></div>';
	}
}
