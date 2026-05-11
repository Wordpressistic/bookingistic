<?php
/**
 * Customer-facing cancellation handler.
 *
 * Listens for /?bookingistic_action=cancel&id=N&t=TOKEN on any front-end URL,
 * validates the signed token, applies the cancellation window, and updates the booking.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Booking;

use Bookingistic\Database\Booking_Repository;
use Bookingistic\Helpers\Token;

defined( 'ABSPATH' ) || exit;

class Cancellation_Manager {

	public static function register(): void {
		add_action( 'template_redirect', [ self::class, 'maybe_handle' ] );
	}

	public static function maybe_handle(): void {
		if ( ( $_GET['bookingistic_action'] ?? '' ) !== 'cancel' ) {
			return;
		}
		$id    = (int) ( $_GET['id'] ?? 0 );
		$token = sanitize_text_field( wp_unslash( $_GET['t'] ?? '' ) );

		$booking = Booking_Repository::find( $id );
		if ( ! $booking || ! Token::consume_equals( $booking['cancel_token'], $token ) ) {
			wp_die( esc_html__( 'Invalid or expired cancellation link.', 'bookingistic' ), '', 410 );
		}
		if ( Token::is_expired( $booking['created_at'] ) ) {
			wp_die( esc_html__( 'This cancellation link has expired.', 'bookingistic' ), '', 410 );
		}

		// Cancellation window.
		$settings   = get_option( 'bookingistic_settings', [] );
		$window_hrs = (int) ( $settings['cancel_window_hrs'] ?? 12 );
		if ( $booking['start_datetime'] ) {
			$start_ts = strtotime( $booking['start_datetime'] . ' UTC' );
			if ( $start_ts && ( $start_ts - time() ) < ( $window_hrs * HOUR_IN_SECONDS ) ) {
				wp_die(
					sprintf(
						/* translators: %d hours window. */
						esc_html__( 'Cancellation is only allowed up to %d hours before the booking. Please contact us instead.', 'bookingistic' ),
						$window_hrs
					),
					'',
					400
				);
			}
		}
		if ( in_array( $booking['status'], [ 'cancelled', 'completed' ], true ) ) {
			wp_die( esc_html__( 'This booking is already finalised.', 'bookingistic' ), '', 400 );
		}

		Booking_Manager::set_status( (int) $booking['id'], 'cancelled' );
		wp_safe_redirect( add_query_arg( 'bookingistic', 'cancelled', home_url( '/' ) ) );
		exit;
	}
}
