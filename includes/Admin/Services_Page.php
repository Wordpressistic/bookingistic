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
		$edit_id = (int) ( $_GET['edit'] ?? 0 );
		$service = $edit_id ? Service_Repository::find( $edit_id ) : null;

		if ( isset( $_GET['edit'] ) || isset( $_GET['new'] ) ) {
			include BOOKINGISTIC_DIR . 'templates/admin/service-edit.php';
			return;
		}

		$services = Service_Repository::all();
		include BOOKINGISTIC_DIR . 'templates/admin/services-list.php';
	}

	public static function maybe_save(): void {
		if ( ( $_POST['bookingistic_admin_action'] ?? '' ) !== 'save_service' ) {
			return;
		}
		check_admin_referer( 'bookingistic_admin_service' );

		$payload = [
			'name'              => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'slug'              => sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) ),
			'description'       => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
			'short_description' => sanitize_textarea_field( wp_unslash( $_POST['short_description'] ?? '' ) ),
			'duration_minutes'  => (int) ( $_POST['duration_minutes'] ?? 30 ),
			'buffer_before'     => (int) ( $_POST['buffer_before'] ?? 0 ),
			'buffer_after'      => (int) ( $_POST['buffer_after'] ?? 0 ),
			'price'             => (float) ( $_POST['price'] ?? 0 ),
			'category'          => sanitize_text_field( wp_unslash( $_POST['category'] ?? '' ) ),
			'booking_type'      => sanitize_key( $_POST['booking_type'] ?? 'one_to_one' ),
			'confirmation_mode' => sanitize_key( $_POST['confirmation_mode'] ?? 'auto' ),
			'max_attendees'     => (int) ( $_POST['max_attendees'] ?? 1 ),
			'status'            => sanitize_key( $_POST['status'] ?? 'active' ),
			'sort_order'        => (int) ( $_POST['sort_order'] ?? 0 ),
		];

		$id = (int) ( $_POST['service_id'] ?? 0 );
		if ( $id > 0 ) {
			Service_Repository::update( $id, $payload );
		} else {
			Service_Repository::create( $payload );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-services&saved=1' ) );
		exit;
	}
}
