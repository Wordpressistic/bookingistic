<?php
/**
 * Email log repository.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Database;

defined( 'ABSPATH' ) || exit;

class Email_Log_Repository {

	public static function table(): string {
		return Tables::email_logs_table();
	}

	public static function record( array $data ): int {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$wpdb->insert(
			self::table(),
			[
				'automation_id'   => (int) ( $data['automation_id'] ?? 0 ),
				'booking_id'      => (int) ( $data['booking_id'] ?? 0 ),
				'customer_id'     => (int) ( $data['customer_id'] ?? 0 ),
				'recipient_email' => sanitize_email( $data['recipient_email'] ?? '' ),
				'subject'         => sanitize_text_field( $data['subject'] ?? '' ),
				'status'          => sanitize_key( $data['status'] ?? 'sent' ),
				'error_message'   => sanitize_textarea_field( $data['error_message'] ?? '' ),
				'sent_at'         => $data['sent_at'] ?? $now,
				'created_at'      => $now,
			]
		);
		return (int) $wpdb->insert_id;
	}

	public static function recent( int $limit = 50 ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' ORDER BY created_at DESC LIMIT %d', $limit ),
			ARRAY_A
		);
		return $rows ?: [];
	}
}
