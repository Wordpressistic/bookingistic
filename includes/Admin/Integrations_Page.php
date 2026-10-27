<?php
/**
 * Admin Integrations page.
 *
 * One row per registered integration with an enable toggle + credential fields.
 * Each integration is responsible for its own settings schema (see Integration_Base::fields()).
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Integrations\Integration_Base;
use Bookingistic\Integrations\Integrations_Manager;

defined( 'ABSPATH' ) || exit;

class Integrations_Page {

	public static function render(): void {
		$integrations = Integrations_Manager::all();

		// UI metadata: dashicon + one-line category tag per slug (card grid only).
		$ui_map = [
			'woocommerce'     => [ 'icon' => 'dashicons-cart',                        'tag' => __( 'Payments', 'bookingistic' ) ],
			'google_calendar' => [ 'icon' => 'dashicons-calendar-alt',                'tag' => __( 'Scheduling', 'bookingistic' ) ],
			'outlook'         => [ 'icon' => 'dashicons-email',                       'tag' => __( 'Scheduling', 'bookingistic' ) ],
			'messageistic'    => [ 'icon' => 'dashicons-smartphone',                  'tag' => __( 'Messaging', 'bookingistic' ) ],
			'crmistic'        => [ 'icon' => 'dashicons-groups',                      'tag' => __( 'CRM', 'bookingistic' ) ],
		];
		$ui_map = apply_filters( 'bookingistic_integrations_ui_map', $ui_map );

		include BOOKINGISTIC_DIR . 'templates/admin/integrations.php';
	}

	public static function maybe_save(): void {
		// Dispatcher peek: bail on action mismatch BEFORE any nonce/capability
		// check — every handler runs on each admin POST.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified below before any processing.
		$action = sanitize_key( wp_unslash( $_POST['bookingistic_admin_action'] ?? '' ) );
		if ( 'save_integration' !== $action ) {
			return;
		}
		\Bookingistic\Core\Capabilities::verify( \Bookingistic\Core\Capabilities::MANAGE );
		check_admin_referer( 'bookingistic_admin_integration' );

		$slug        = sanitize_key( wp_unslash( $_POST['integration_slug'] ?? '' ) );
		$integration = Integrations_Manager::find( $slug );
		if ( ! $integration ) {
			wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-integrations' ) );
			exit;
		}

		$values = [ '_enabled' => ! empty( $_POST['_enabled'] ) ];
		foreach ( $integration->fields() as $key => $schema ) {
			$values[ $key ] = match ( $schema['type'] ?? 'text' ) {
				'checkbox' => ! empty( $_POST[ $key ] ) ? 1 : 0,
				'url'      => esc_url_raw( wp_unslash( $_POST[ $key ] ?? '' ) ),
				'email'    => sanitize_email( wp_unslash( $_POST[ $key ] ?? '' ) ),
				'textarea' => sanitize_textarea_field( wp_unslash( $_POST[ $key ] ?? '' ) ),
				// text, password (API keys / secrets) and any other type.
				default    => sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) ),
			};
		}
		$integration->update_settings( $values );

		wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-integrations&saved=' . $slug ) );
		exit;
	}
}
