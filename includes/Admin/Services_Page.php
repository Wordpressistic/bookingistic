<?php
/**
 * Admin services list + edit.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Database\Service_Repository;

defined( 'ABSPATH' ) || exit;

class Services_Page {

	public static function render(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display filter; saves are nonce+capability checked in maybe_save().
		$edit_id = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;
		$service = $edit_id ? Service_Repository::find( $edit_id ) : null;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display filter.
		if ( isset( $_GET['edit'] ) || isset( $_GET['new'] ) ) {
			include BOOKINGISTIC_DIR . 'templates/admin/service-edit.php';
			return;
		}

		$services = Service_Repository::all();
		include BOOKINGISTIC_DIR . 'templates/admin/services-list.php';
	}

	public static function maybe_save(): void {
		// Dispatcher peek: bail on action mismatch BEFORE any nonce/capability
		// check — every handler runs on each admin POST.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified below before any processing.
		$action = sanitize_key( wp_unslash( $_POST['bookingistic_admin_action'] ?? '' ) );
		if ( 'save_service' !== $action ) {
			return;
		}
		\Bookingistic\Core\Capabilities::verify( \Bookingistic\Core\Capabilities::MANAGE_SERVICES );
		check_admin_referer( 'bookingistic_admin_service' );

		$payload = [
			'name'              => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'slug'              => sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) ),
			'description'       => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
			'short_description' => sanitize_textarea_field( wp_unslash( $_POST['short_description'] ?? '' ) ),
			'duration_minutes'  => absint( wp_unslash( $_POST['duration_minutes'] ?? 30 ) ),
			'buffer_before'     => absint( wp_unslash( $_POST['buffer_before'] ?? 0 ) ),
			'buffer_after'      => absint( wp_unslash( $_POST['buffer_after'] ?? 0 ) ),
			'price'             => floatval( wp_unslash( $_POST['price'] ?? 0 ) ),
			'deposit_percent'   => floatval( wp_unslash( $_POST['deposit_percent'] ?? 0 ) ),
			'category'          => sanitize_text_field( wp_unslash( $_POST['category'] ?? '' ) ),
			'booking_type'      => sanitize_key( wp_unslash( $_POST['booking_type'] ?? 'one_to_one' ) ),
			'confirmation_mode' => sanitize_key( wp_unslash( $_POST['confirmation_mode'] ?? 'auto' ) ),
			'max_attendees'     => absint( wp_unslash( $_POST['max_attendees'] ?? 1 ) ),
			'status'            => sanitize_key( wp_unslash( $_POST['status'] ?? 'active' ) ),
			'sort_order'        => absint( wp_unslash( $_POST['sort_order'] ?? 0 ) ),
		];

		$id = absint( wp_unslash( $_POST['service_id'] ?? 0 ) );
		if ( $id > 0 ) {
			Service_Repository::update( $id, $payload );
		} else {
			Service_Repository::create( $payload );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-services&saved=1' ) );
		exit;
	}
}
