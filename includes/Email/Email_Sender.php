<?php
/**
 * Email sender.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Email;

use Bookingistic\Database\Email_Log_Repository;

defined( 'ABSPATH' ) || exit;

class Email_Sender {

	public static function send( string $to, string $subject, string $body, array $context = [] ): bool {
		$settings = get_option( 'bookingistic_settings', [] );
		$from_n   = $settings['from_name'] ?? get_bloginfo( 'name' );
		$from_e   = $settings['from_email'] ?? get_option( 'admin_email' );
		$reply    = $settings['reply_to'] ?? $from_e;

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', $from_n, $from_e ),
			sprintf( 'Reply-To: %s', $reply ),
		];

		$attachments = [];
		if ( ! empty( $context['booking'] ) && is_array( $context['booking'] ) ) {
			/**
			 * Filter the attachments for a booking email. Integrations may add
			 * .ics files or other booking-related documents.
			 *
			 * @param string[] $attachments Absolute paths.
			 * @param array    $booking
			 */
			$attachments = apply_filters( 'bookingistic_email_attachments', $attachments, $context['booking'] );
		}

		$sent = wp_mail( $to, $subject, $body, $headers, $attachments );

		Email_Log_Repository::record(
			[
				'automation_id'   => (int) ( $context['automation_id'] ?? 0 ),
				'booking_id'      => (int) ( $context['booking_id'] ?? 0 ),
				'customer_id'     => (int) ( $context['customer_id'] ?? 0 ),
				'recipient_email' => $to,
				'subject'         => $subject,
				'status'          => $sent ? 'sent' : 'failed',
				'error_message'   => $sent ? '' : 'wp_mail() returned false',
				'sent_at'         => current_time( 'mysql', true ),
			]
		);

		return (bool) $sent;
	}
}
