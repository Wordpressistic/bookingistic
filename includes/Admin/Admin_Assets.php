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

	/**
	 * Shared premium page header (title + optional tagline + action buttons).
	 * UI-only helper — pages keep full control of their own markup below it.
	 *
	 * @param string                $title   Page title (already translated).
	 * @param string                $tagline Optional subtitle line.
	 * @param array<int,array{url:string,label:string,primary?:bool}> $actions Buttons.
	 * @param array<int,array{icon?:string,label:string}>                   $badges  Small pills next to the title.
	 */
	public static function page_header( string $title, string $tagline = '', array $actions = [], array $badges = [] ): void {
		echo '<header class="bookingistic-header">';
		echo '<h1>' . esc_html( $title );
		if ( $badges ) {
			echo '<span class="bookingistic-header__badges">';
			foreach ( $badges as $badge ) {
				echo '<span class="bookingistic-header__badge">';
				if ( ! empty( $badge['icon'] ) ) {
					echo '<span class="dashicons ' . esc_attr( $badge['icon'] ) . '"></span>';
				}
				echo esc_html( $badge['label'] ) . '</span>';
			}
			echo '</span>';
		}
		if ( $actions ) {
			echo '<span class="bookingistic-header__actions">';
			foreach ( $actions as $action ) {
				printf(
					'<a class="%1$s" href="%2$s">%3$s</a>',
					esc_attr( ! empty( $action['primary'] ) ? 'button button-primary' : 'button button-secondary' ),
					esc_url( $action['url'] ),
					esc_html( $action['label'] )
				);
			}
			echo '</span>';
		}
		echo '</h1>';
		if ( '' !== $tagline ) {
			echo '<p class="bookingistic-header__tagline">' . esc_html( $tagline ) . '</p>';
		}
		echo '</header>';
	}

	/**
	 * Row of stat cards (real numbers only — callers pass what they queried).
	 *
	 * @param array<int,array{label:string,value:string,hint?:string,tone?:string}> $stats Stat cards.
	 */
	public static function stat_cards( array $stats ): void {
		if ( ! $stats ) {
			return;
		}
		echo '<div class="bookingistic-stats">';
		foreach ( $stats as $stat ) {
			$tone = in_array( $stat['tone'] ?? '', [ 'green', 'amber', 'red', 'blue' ], true ) ? '-' . $stat['tone'] : '';
			echo '<div class="bookingistic-stat bookingistic-stat' . esc_attr( $tone ) . '">';
			echo '<span class="bookingistic-stat__label">' . esc_html( $stat['label'] ) . '</span>';
			echo '<span class="bookingistic-stat__value">' . esc_html( $stat['value'] ) . '</span>';
			if ( ! empty( $stat['hint'] ) ) {
				echo '<span class="bookingistic-stat__hint">' . esc_html( $stat['hint'] ) . '</span>';
			}
			echo '</div>';
		}
		echo '</div>';
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
