<?php
/**
 * Admin asset registration — scoped to Bookingistic pages only.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

defined( 'ABSPATH' ) || exit;

class Admin_Assets {

	public static function register(): void {
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue' ] );
	}

	public static function enqueue( string $hook ): void {
		if ( strpos( (string) $hook, 'bookingistic' ) === false ) {
			return;
		}
		wp_enqueue_style(
			'bookingistic-admin',
			BOOKINGISTIC_URL . 'assets/admin/css/admin.css',
			[],
			BOOKINGISTIC_VERSION
		);
		wp_enqueue_script(
			'bookingistic-admin',
			BOOKINGISTIC_URL . 'assets/admin/js/admin.js',
			[],
			BOOKINGISTIC_VERSION,
			true
		);
		wp_localize_script(
			'bookingistic-admin',
			'BookingisticAdmin',
			[
				'restUrl'      => esc_url_raw( rest_url( BOOKINGISTIC_REST_NAMESPACE . '/' ) ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'bookingsUrl'  => admin_url( 'admin.php?page=bookingistic-bookings' ),
				'customersUrl' => admin_url( 'admin.php?page=bookingistic-customers' ),
				'i18n'         => [
					'loading'      => __( 'Loading…', 'bookingistic' ),
					'noEvents'     => __( 'No bookings in this range.', 'bookingistic' ),
					'errorGeneric' => __( 'Could not load bookings.', 'bookingistic' ),
					'dows'         => [
						__( 'Mon', 'bookingistic' ),
						__( 'Tue', 'bookingistic' ),
						__( 'Wed', 'bookingistic' ),
						__( 'Thu', 'bookingistic' ),
						__( 'Fri', 'bookingistic' ),
						__( 'Sat', 'bookingistic' ),
						__( 'Sun', 'bookingistic' ),
					],
				],
			]
		);
	}
}
