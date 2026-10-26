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
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write to the plugin's own custom table; its cache group is invalidated right after.
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
		wp_cache_delete( 'last_changed', Cache::EMAIL_LOGS );
		return (int) $wpdb->insert_id;
	}

	public static function recent( int $limit = 50 ): array {
		global $wpdb;
		$cache_key = Cache::key( 'email_log_recent', [ $limit ], [ Cache::EMAIL_LOGS ] );
		$rows = wp_cache_get( $cache_key, Cache::EMAIL_LOGS, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$rows = $wpdb->get_results(
				$wpdb->prepare( 'SELECT * FROM %i ORDER BY created_at DESC LIMIT %d', self::table(), $limit ),
				ARRAY_A
			);
			wp_cache_set( $cache_key, $rows, Cache::EMAIL_LOGS );
		}
		return $rows ?: [];
	}
}
