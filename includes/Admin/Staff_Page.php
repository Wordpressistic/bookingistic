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
		$edit_id = (int) ( $_GET['edit'] ?? 0 );
		$new     = isset( $_GET['new'] );

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
		$action = (string) ( $_POST['bookingistic_admin_action'] ?? '' );

		if ( $action === 'save_staff' ) {
			check_admin_referer( 'bookingistic_admin_staff' );

			$working_hours = [];
			foreach ( self::DAYS as $dow => $_label ) {
				$start = Sanitizer::hhmm( wp_unslash( $_POST['wh'][ $dow ]['start'] ?? '' ) );
				$end   = Sanitizer::hhmm( wp_unslash( $_POST['wh'][ $dow ]['end']   ?? '' ) );
				$on    = ! empty( $_POST['wh'][ $dow ]['on'] );
				if ( $on && $start && $end ) {
					$working_hours[ (string) $dow ] = [ 'start' => $start, 'end' => $end ];
				}
			}

			$days_off = [];
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
				'phone'                 => Sanitizer::phone( wp_unslash( $_POST['phone'] ?? '' ) ),
				'photo_url'             => esc_url_raw( wp_unslash( $_POST['photo_url'] ?? '' ) ),
				'bio'                   => sanitize_textarea_field( wp_unslash( $_POST['bio'] ?? '' ) ),
				'booking_limit_per_day' => (int) ( $_POST['booking_limit_per_day'] ?? 0 ),
				'status'                => sanitize_key( $_POST['status'] ?? 'active' ),
				'working_hours'         => $working_hours,
				'days_off'              => $days_off,
				'service_ids'           => array_map( 'intval', (array) ( $_POST['service_ids'] ?? [] ) ),
			];

			$id = (int) ( $_POST['staff_id'] ?? 0 );
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
			check_admin_referer( 'bookingistic_admin_staff_delete' );
			Staff_Repository::delete( (int) ( $_POST['staff_id'] ?? 0 ) );
			wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-staff&deleted=1' ) );
			exit;
		}
	}

	/**
	 * Google Calendar connect / disconnect per staff member.
	 * Only renders an action link when OAuth client credentials are configured.
	 */
	public static function maybe_gcal(): void {
		$action = (string) ( $_POST['bookingistic_admin_action'] ?? '' );

		if ( $action === 'gcal_disconnect' ) {
			check_admin_referer( 'bookingistic_admin_gcal' );
			if ( ! current_user_can( \Bookingistic\Core\Capabilities::MANAGE ) ) {
				return;
			}
			\Bookingistic\Integrations\Google_Calendar\GCal_Sync::disconnect( (int) ( $_POST['staff_id'] ?? 0 ) );
			wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-staff&edit=' . (int) ( $_POST['staff_id'] ?? 0 ) . '&gcal=disconnected' ) );
			exit;
		}

		if ( $action === 'gcal_sync_now' ) {
			check_admin_referer( 'bookingistic_admin_gcal' );
			if ( ! current_user_can( \Bookingistic\Core\Capabilities::MANAGE ) ) {
				return;
			}
			$staff_id = (int) ( $_POST['staff_id'] ?? 0 );
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
