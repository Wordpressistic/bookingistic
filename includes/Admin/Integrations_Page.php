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
		include BOOKINGISTIC_DIR . 'templates/admin/integrations.php';
	}

	public static function maybe_save(): void {
		if ( ( $_POST['bookingistic_admin_action'] ?? '' ) !== 'save_integration' ) {
			return;
		}
		check_admin_referer( 'bookingistic_admin_integration' );

		$slug        = sanitize_key( wp_unslash( $_POST['integration_slug'] ?? '' ) );
		$integration = Integrations_Manager::find( $slug );
		if ( ! $integration ) {
			wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-integrations' ) );
			exit;
		}

		$values = [ '_enabled' => ! empty( $_POST['_enabled'] ) ];
		foreach ( $integration->fields() as $key => $schema ) {
			$raw = wp_unslash( $_POST[ $key ] ?? '' );
			$values[ $key ] = self::sanitize_field( $raw, $schema['type'] ?? 'text' );
		}
		$integration->update_settings( $values );

		wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-integrations&saved=' . $slug ) );
		exit;
	}

	private static function sanitize_field( $value, string $type ) {
		return match ( $type ) {
			'checkbox' => ! empty( $value ) ? 1 : 0,
			'url'      => esc_url_raw( (string) $value ),
			'email'    => sanitize_email( (string) $value ),
			'password' => (string) $value, // store as-is; never echoed back outside form
			'textarea' => sanitize_textarea_field( (string) $value ),
			default    => sanitize_text_field( (string) $value ),
		};
	}
}
