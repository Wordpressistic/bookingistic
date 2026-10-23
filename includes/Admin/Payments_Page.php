<?php
/**
 * Admin payments page — ledger + gateway switches.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Payments\Payment_Manager;
use Bookingistic\Database\Payment_Repository;

defined( 'ABSPATH' ) || exit;

class Payments_Page {

	public const NONCE = 'bookingistic_admin_payments';

	public static function render(): void {
		global $wpdb;

		// Read-only display filters; state changes are nonce+capability checked in maybe_save()/maybe_act().
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status   = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$provider = isset( $_GET['provider'] ) ? sanitize_key( wp_unslash( $_GET['provider'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page     = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		$per_page = 50;
		$offset   = ( $page - 1 ) * $per_page;

		$total = Payment_Repository::count( [ 'status' => $status, 'provider' => $provider ] );
		$rows  = Payment_Repository::query(
			[
				'status'   => $status,
				'provider' => $provider,
				'per_page' => $per_page,
				'offset'   => $offset,
			]
		);

		$gateways = Payment_Manager::gateways();
		$pages    = (int) ceil( $total / $per_page );

		include BOOKINGISTIC_DIR . 'templates/admin/payments.php';
	}

	/**
	 * Manual offline actions (mark paid / refund) — nonce + capability gated.
	 * Invoked from Admin_Menu::handle_post_actions().
	 */
	public static function maybe_act(): void {
		// Dispatcher peek: bail on action mismatch BEFORE any nonce/capability
		// check — every handler runs on each admin POST.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified below before any processing.
		$action = sanitize_key( wp_unslash( $_POST['bookingistic_admin_action'] ?? '' ) );
		if ( ! in_array( $action, [ 'payment_mark_paid', 'payment_refund', 'payment_fail' ], true ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_bookingistic' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( self::NONCE );

		$booking_id = absint( wp_unslash( $_POST['booking_id'] ?? 0 ) );
		$booking    = $booking_id ? \Bookingistic\Database\Booking_Repository::find( $booking_id ) : null;
		if ( ! $booking ) {
			wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-payments' ) );
			exit;
		}

		// Actions: payment_mark_paid → paid, payment_refund → refunded, payment_fail → failed.
		$map = [ 'payment_mark_paid' => 'paid', 'payment_refund' => 'refunded', 'payment_fail' => 'failed' ];
		$status = $map[ $action ];
		Payment_Manager::record_event(
			$booking_id,
			'manual',
			$map[ $action ],
			(float) $booking['payment_amount'],
			(string) get_current_user_id(), // actor for audit trail.
			[ 'manual' => true, 'user_id' => get_current_user_id() ]
		);

		wp_safe_redirect( add_query_arg( [ 'page' => 'bookingistic-payments', 'updated' => 1 ], admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handles the gateway enable/disable form. Invoked from Admin_Menu::handle_post_actions().
	 */
	public static function maybe_save(): void {
		// Dispatcher peek: bail on action mismatch BEFORE any nonce/capability
		// check — every handler runs on each admin POST.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified below before any processing.
		$action = sanitize_key( wp_unslash( $_POST['bookingistic_admin_action'] ?? '' ) );
		if ( 'save_payments' !== $action ) {
			return;
		}
		if ( ! current_user_can( 'manage_bookingistic' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( self::NONCE );

		$gateways = Payment_Manager::gateways();
		$map      = [];
		foreach ( array_keys( $gateways ) as $slug ) {
			$map[ $slug ] = isset( $_POST[ 'gateway_' . $slug ] ) ? 1 : 0;
		}

		$settings         = (array) get_option( 'bookingistic_settings', [] );
		$settings['payment_gateways'] = $map;
		update_option( 'bookingistic_settings', $settings );

		do_action( 'bookingistic_payment_gateways_saved', $map );

		wp_safe_redirect( add_query_arg( [ 'page' => 'bookingistic-payments', 'saved' => 1 ], admin_url( 'admin.php' ) ) );
		exit;
	}
}
