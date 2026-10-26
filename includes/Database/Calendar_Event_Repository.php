<?php
/**
 * Calendar events repository — tracks provider (Google/Outlook) event sync state per booking.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Database;

defined( 'ABSPATH' ) || exit;

class Calendar_Event_Repository {

	public static function table(): string {
		return Tables::calendar_events_table();
	}

	public static function find_for_booking( int $booking_id, string $provider ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE booking_id = %d AND provider = %s LIMIT 1',
				self::table(),
				$booking_id,
				$provider
			),
			ARRAY_A
		);
		return $row ? self::normalize( $row ) : null;
	}

	public static function upsert( int $booking_id, string $provider, array $data ): ?array {
		global $wpdb;
		$now        = current_time( 'mysql', true );
		$existing   = self::find_for_booking( $booking_id, $provider );
		$base = [
			'external_event_id' => sanitize_text_field( $data['external_event_id'] ?? '' ),
			'status'            => sanitize_key( $data['status'] ?? 'synced' ),
			'payload'           => isset( $data['payload'] ) ? wp_json_encode( $data['payload'] ) : null,
		];

		if ( $existing ) {
			$wpdb->update(
				self::table(),
				array_merge( $base, [ 'updated_at' => $now ] ),
				[ 'id' => $existing['id'] ],
				[ '%s', '%s', '%s', '%s' ],
				[ '%d' ]
			);
			return array_merge( $existing, $base, [ 'updated_at' => $now ] );
		}

		$wpdb->insert(
			self::table(),
			array_merge( $base, [
				'booking_id' => $booking_id,
				'provider'   => $provider,
				'created_at' => $now,
				'updated_at' => $now,
			] ),
			[ '%s', '%s', '%s', '%d', '%s', '%s', '%s' ]
		);
		$id = (int) $wpdb->insert_id;
		return $id ? self::find( $id ) : null;
	}

	public static function find( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', self::table(), $id ), ARRAY_A );
		return $row ? self::normalize( $row ) : null;
	}

	public static function delete_for_booking( int $booking_id, string $provider ): bool {
		global $wpdb;
		return (bool) $wpdb->delete( self::table(), [ 'booking_id' => $booking_id, 'provider' => $provider ], [ '%d', '%s' ] );
	}

	private static function normalize( array $row ): array {
		if ( isset( $row['payload'] ) && is_string( $row['payload'] ) ) {
			$row['payload'] = json_decode( $row['payload'], true );
		}
		return $row;
	}
}
