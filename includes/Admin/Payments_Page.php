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

		$status   = sanitize_key( (string) ( $_GET['status'] ?? '' ) );
		$provider = sanitize_key( (string) ( $_GET['provider'] ?? '' ) );
		$page     = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
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
	 * Handles the gateway enable/disable form. Invoked from Admin_Menu::handle_post_actions().
	 */
	public static function maybe_save(): void {
		if ( empty( $_POST['bookingistic_admin_action'] ) || 'save_payments' !== $_POST['bookingistic_admin_action'] ) {
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
