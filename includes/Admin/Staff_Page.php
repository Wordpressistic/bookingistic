<?php
/**
 * Admin staff: list / new / edit / delete.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Database\Service_Repository;
use Bookingistic\Database\Staff_Repository;
use Bookingistic\Helpers\Sanitizer;

defined( 'ABSPATH' ) || exit;

class Staff_Page {

	public const DAYS = [
		1 => 'Mon',
		2 => 'Tue',
		3 => 'Wed',
		4 => 'Thu',
		5 => 'Fri',
		6 => 'Sat',
		7 => 'Sun',
	];

	public static function render(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display filter; saves are nonce+capability checked in maybe_save().
		$edit_id = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;
		$new     = isset( $_GET['new'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display filter.

		if ( $edit_id || $new ) {
			$staff             = $edit_id ? Staff_Repository::find( $edit_id ) : null;
			$services          = Service_Repository::all();
			$assigned_services = $edit_id ? Staff_Repository::services_for_staff( $edit_id ) : [];
			include BOOKINGISTIC_DIR . 'templates/admin/staff-edit.php';
			return;
		}

		$staff = Staff_Repository::all();
		include BOOKINGISTIC_DIR . 'templates/admin/staff-list.php';
	}

	public static function maybe_save(): void {
		// Action dispatcher peek: save_staff and delete_staff verify their own
		// nonces below before any processing.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified below before any processing.
		$action = sanitize_key( wp_unslash( $_POST['bookingistic_admin_action'] ?? '' ) );

		if ( $action === 'save_staff' ) {
			\Bookingistic\Core\Capabilities::verify( \Bookingistic\Core\Capabilities::MANAGE_STAFF );
			check_admin_referer( 'bookingistic_admin_staff' );

			$working_hours = [];
			foreach ( self::DAYS as $dow => $_label ) {
				// Values are validated to HH:MM by Sanitizer::hhmm().
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$start = Sanitizer::hhmm( wp_unslash( $_POST['wh'][ $dow ]['start'] ?? '' ) );
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$end   = Sanitizer::hhmm( wp_unslash( $_POST['wh'][ $dow ]['end']   ?? '' ) );
				$on    = ! empty( $_POST['wh'][ $dow ]['on'] );
				if ( $on && $start && $end ) {
					$working_hours[ (string) $dow ] = [ 'start' => $start, 'end' => $end ];
				}
			}

			$days_off = [];
			// Per-entry dates are validated by Sanitizer::iso_date() below.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			foreach ( (array) ( $_POST['days_off'] ?? [] ) as $entry ) {
				$from = Sanitizer::iso_date( $entry['from'] ?? '' );
				$to   = Sanitizer::iso_date( $entry['to'] ?? $from );
				if ( $from ) {
					$days_off[] = [ 'from' => $from, 'to' => $to ?: $from ];
				}
			}

			$payload = [
				'name'                  => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				'email'                 => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
				// Validated by Sanitizer::phone().
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'phone'                 => Sanitizer::phone( wp_unslash( $_POST['phone'] ?? '' ) ),
				'photo_url'             => esc_url_raw( wp_unslash( $_POST['photo_url'] ?? '' ) ),
				'bio'                   => sanitize_textarea_field( wp_unslash( $_POST['bio'] ?? '' ) ),
				'booking_limit_per_day' => absint( wp_unslash( $_POST['booking_limit_per_day'] ?? 0 ) ),
				'status'                => sanitize_key( wp_unslash( $_POST['status'] ?? 'active' ) ),
				'working_hours'         => $working_hours,
				'days_off'              => $days_off,
				'service_ids'           => array_map( 'intval', (array) ( $_POST['service_ids'] ?? [] ) ),
			];

			$id = absint( wp_unslash( $_POST['staff_id'] ?? 0 ) );
			$saved = $id > 0
				? Staff_Repository::update( $id, $payload )
				: Staff_Repository::create( $payload );

			if ( is_wp_error( $saved ) ) {
				$redirect = admin_url( 'admin.php?page=bookingistic-staff&new=1&error=' . rawurlencode( $saved->get_error_message() ) );
			} else {
				$redirect = admin_url( 'admin.php?page=bookingistic-staff&edit=' . (int) $saved['id'] . '&saved=1' );
			}
			wp_safe_redirect( $redirect );
			exit;
		}

		if ( $action === 'delete_staff' ) {
			\Bookingistic\Core\Capabilities::verify( \Bookingistic\Core\Capabilities::MANAGE_STAFF );
			check_admin_referer( 'bookingistic_admin_staff_delete' );
			Staff_Repository::delete( absint( wp_unslash( $_POST['staff_id'] ?? 0 ) ) );
			wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-staff&deleted=1' ) );
			exit;
		}
	}

	/**
	 * Google Calendar connect / disconnect per staff member.
	 * Only renders an action link when OAuth client credentials are configured.
	 */
	public static function maybe_gcal(): void {
		// Dispatcher peek: bail on action mismatch BEFORE any nonce/capability
		// check — every handler runs on each admin POST.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified below before any processing.
		$action = sanitize_key( wp_unslash( $_POST['bookingistic_admin_action'] ?? '' ) );
		if ( ! in_array( $action, [ 'gcal_disconnect', 'gcal_sync_now' ], true ) ) {
			return;
		}
		if ( ! current_user_can( \Bookingistic\Core\Capabilities::MANAGE ) ) {
			return;
		}
		check_admin_referer( 'bookingistic_admin_gcal' );

		if ( $action === 'gcal_disconnect' ) {
			$staff_id = absint( wp_unslash( $_POST['staff_id'] ?? 0 ) );
			\Bookingistic\Integrations\Google_Calendar\GCal_Sync::disconnect( $staff_id );
			wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-staff&edit=' . $staff_id . '&gcal=disconnected' ) );
			exit;
		}

		if ( $action === 'gcal_sync_now' ) {
			$staff_id = absint( wp_unslash( $_POST['staff_id'] ?? 0 ) );
			\Bookingistic\Integrations\Google_Calendar\GCal_Sync::refresh_busy_for_staff( $staff_id );
			wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-staff&edit=' . $staff_id . '&gcal=synced' ) );
			exit;
		}
	}

	/**
	 * Connect URL for the staff editor (empty string when not configured).
	 */
	public static function gcal_connect_url( int $staff_id ): string {
		if ( ! class_exists( \Bookingistic\Integrations\Google_Calendar\GCal_Sync::class ) ) {
			return '';
		}
		if ( ! \Bookingistic\Integrations\Google_Calendar\GCal_Sync::is_configured() ) {
			return '';
		}
		return \Bookingistic\Integrations\Google_Calendar\GCal_Sync::connect_url( $staff_id );
	}
}
