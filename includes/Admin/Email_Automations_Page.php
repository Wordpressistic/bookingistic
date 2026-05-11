<?php
/**
 * Admin email automations: list / new / edit.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Database\Email_Automation_Repository;
use Bookingistic\Database\Service_Repository;
use Bookingistic\Email\Email_Template_Renderer;

defined( 'ABSPATH' ) || exit;

class Email_Automations_Page {

	public const TRIGGERS = [
		'booking_submitted'   => 'Booking submitted',
		'booking_confirmed'   => 'Booking confirmed',
		'booking_cancelled'   => 'Booking cancelled',
		'booking_rescheduled' => 'Booking rescheduled',
		'booking_completed'   => 'Booking completed',
		'booking_reminder'    => 'Booking reminder',
		'no_show_marked'      => 'No-show marked',
		'review_request'      => 'Review request',
	];

	public const RECIPIENTS = [
		'customer' => 'Customer',
		'admin'    => 'Admin (from Settings)',
		'staff'    => 'Staff (from Settings)',
		'custom'   => 'Custom email',
	];

	public static function render(): void {
		$edit_id = (int) ( $_GET['edit'] ?? 0 );
		$new     = isset( $_GET['new'] );

		if ( $edit_id || $new ) {
			$rule     = $edit_id ? Email_Automation_Repository::find( $edit_id ) : null;
			$services = Service_Repository::all();
			$variables = Email_Template_Renderer::VARIABLE_KEYS;
			include BOOKINGISTIC_DIR . 'templates/admin/email-automation-edit.php';
			return;
		}

		$rules = Email_Automation_Repository::all();
		include BOOKINGISTIC_DIR . 'templates/admin/email-automations-list.php';
	}

	public static function maybe_save(): void {
		$action = (string) ( $_POST['bookingistic_admin_action'] ?? '' );

		if ( $action === 'save_email_automation' ) {
			check_admin_referer( 'bookingistic_admin_email_automation' );

			$payload = [
				'name'             => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				'trigger_event'    => sanitize_key( $_POST['trigger_event'] ?? '' ),
				'recipient_type'   => sanitize_key( $_POST['recipient_type'] ?? 'customer' ),
				'recipient_custom' => sanitize_email( wp_unslash( $_POST['recipient_custom'] ?? '' ) ),
				'delay_amount'     => (int) ( $_POST['delay_amount'] ?? 0 ),
				'delay_unit'       => sanitize_key( $_POST['delay_unit'] ?? 'minutes' ),
				'subject'          => sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) ),
				'body'             => wp_kses_post( wp_unslash( $_POST['body'] ?? '' ) ),
				'service_id'       => (int) ( $_POST['service_id'] ?? 0 ),
				'status'           => sanitize_key( $_POST['status'] ?? 'active' ),
			];

			$id = (int) ( $_POST['automation_id'] ?? 0 );
			$saved = $id > 0
				? Email_Automation_Repository::update( $id, $payload )
				: Email_Automation_Repository::create( $payload );

			$saved_id = is_array( $saved ) ? (int) $saved['id'] : 0;
			$redirect = $saved_id
				? admin_url( 'admin.php?page=bookingistic-emails&edit=' . $saved_id . '&saved=1' )
				: admin_url( 'admin.php?page=bookingistic-emails' );
			wp_safe_redirect( $redirect );
			exit;
		}

		if ( $action === 'delete_email_automation' ) {
			check_admin_referer( 'bookingistic_admin_email_automation_delete' );
			Email_Automation_Repository::delete( (int) ( $_POST['automation_id'] ?? 0 ) );
			wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-emails&deleted=1' ) );
			exit;
		}
	}
}
