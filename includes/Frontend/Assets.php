<?php
/**
 * Frontend asset registration.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Frontend;

defined( 'ABSPATH' ) || exit;

class Assets {

	public const HANDLE_CSS = 'bookingistic-frontend';
	public const HANDLE_JS  = 'bookingistic-frontend';

	private static bool $registered = false;

	public static function register(): void {
		add_action( 'wp_enqueue_scripts', [ self::class, 'register_handles' ] );
	}

	public static function register_handles(): void {
		if ( self::$registered ) {
			return;
		}
		wp_register_style(
			self::HANDLE_CSS,
			BOOKINGISTIC_URL . 'assets/frontend/css/booking-form.css',
			[],
			BOOKINGISTIC_VERSION
		);
		wp_register_script(
			self::HANDLE_JS,
			BOOKINGISTIC_URL . 'assets/frontend/js/booking-form.js',
			[],
			BOOKINGISTIC_VERSION,
			true
		);
		self::$registered = true;
	}

	public static function enqueue_frontend(): void {
		self::register_handles();
		wp_enqueue_style( self::HANDLE_CSS );
		wp_enqueue_script( self::HANDLE_JS );
		wp_localize_script(
			self::HANDLE_JS,
			'BookingisticConfig',
			[
				'restUrl' => esc_url_raw( rest_url( BOOKINGISTIC_REST_NAMESPACE . '/' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => [
					'pickDate'   => __( 'Pick a date to see available times', 'bookingistic' ),
					'loading'    => __( 'Loading…', 'bookingistic' ),
					'noSlots'    => __( 'No times available on this date.', 'bookingistic' ),
					'submitting' => __( 'Submitting…', 'bookingistic' ),
					'errorGeneric' => __( 'Something went wrong. Please try again.', 'bookingistic' ),
				],
			]
		);
	}
}
