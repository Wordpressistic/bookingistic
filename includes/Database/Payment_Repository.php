<?php
/**
 * Payment records repository — ledger of every payment attempt on a booking.
 *
 * One row per provider interaction attempt (order created, payment received,
 * refund, failure). Bookings keep their denormalized `payment_status` column;
 * this table is the audit trail behind it.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Database;

defined( 'ABSPATH' ) || exit;

class Payment_Repository {

	public const STATUSES = [ 'pending', 'paid', 'failed', 'refunded', 'cancelled' ];

	/**
	 * @param array<string,mixed> $data booking_id, provider, external_id, amount, currency, status, payload
	 * @return int Inserted record ID (0 on failure).
	 */
	public static function create( array $data ): int {
		global $wpdb;

		$status = in_array( $data['status'] ?? '', self::STATUSES, true ) ? $data['status'] : 'pending';

		$ok = $wpdb->insert(
			Tables::payment_records_table(),
			[
				'booking_id'  => (int) ( $data['booking_id'] ?? 0 ),
				'provider'    => sanitize_key( (string) ( $data['provider'] ?? '' ) ),
				'external_id' => (string) ( $data['external_id'] ?? '' ),
				'amount'      => (float) ( $data['amount'] ?? 0 ),
				'currency'    => substr( sanitize_text_field( (string) ( $data['currency'] ?? 'USD' ) ), 0, 10 ),
				'status'      => $status,
				'payload'     => is_string( $data['payload'] ?? null ) ? $data['payload'] : wp_json_encode( $data['payload'] ?? [] ),
				'created_at'  => current_time( 'mysql', true ),
				'updated_at'  => current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s' ]
		);

		return $ok ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Append a status transition to an existing record's payload log and set status.
	 */
	public static function mark( int $record_id, string $status, string $note = '' ): bool {
		global $wpdb;

		$status = in_array( $status, self::STATUSES, true ) ? $status : 'pending';
		$record = self::find( $record_id );
		if ( ! $record ) {
			return false;
		}

		$payload = json_decode( (string) $record['payload'], true );
		$payload = is_array( $payload ) ? $payload : [];
		$payload['log'][] = [
			'status' => $status,
			'note'   => $note,
			'at'     => current_time( 'mysql', true ),
		];

		return (bool) $wpdb->update(
			Tables::payment_records_table(),
			[
				'status'     => $status,
				'payload'    => wp_json_encode( $payload ),
				'updated_at' => current_time( 'mysql', true ),
			],
			[ 'id' => $record_id ],
			[ '%s', '%s', '%s' ],
			[ '%d' ]
		);
	}

	public static function find( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', Tables::payment_records_table(), $id ), ARRAY_A );
		return $row ?: null;
	}

	public static function latest_for_booking( int $booking_id, string $provider = '' ): ?array {
		global $wpdb;
		$table = Tables::payment_records_table();
		if ( $provider ) {
			$row = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM %i WHERE booking_id = %d AND provider = %s ORDER BY id DESC LIMIT 1', $table, $booking_id, $provider ),
				ARRAY_A
			);
		} else {
			$row = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM %i WHERE booking_id = %d ORDER BY id DESC LIMIT 1', $table, $booking_id ),
				ARRAY_A
			);
		}
		return $row ?: null;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function query( array $args = [] ): array {
		global $wpdb;
		$f      = self::filter_values( $args );
		$limit  = max( 1, min( 200, (int) ( $args['per_page'] ?? 50 ) ) );
		$offset = max( 0, (int) ( $args['offset'] ?? 0 ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %i
				WHERE ( %s = '' OR status = %s )
				AND ( %s = '' OR provider = %s )
				AND ( %d = 0 OR booking_id = %d )
				ORDER BY id DESC LIMIT %d OFFSET %d",
				Tables::payment_records_table(),
				$f['status'],
				$f['status'],
				$f['provider'],
				$f['provider'],
				$f['booking_id'],
				$f['booking_id'],
				$limit,
				$offset
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : [];
	}

	public static function count( array $args = [] ): int {
		global $wpdb;
		$f = self::filter_values( $args );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i
				WHERE ( %s = '' OR status = %s )
				AND ( %s = '' OR provider = %s )
				AND ( %d = 0 OR booking_id = %d )",
				Tables::payment_records_table(),
				$f['status'],
				$f['status'],
				$f['provider'],
				$f['provider'],
				$f['booking_id'],
				$f['booking_id']
			)
		);
	}

	/**
	 * Normalize optional filters. An empty value ('' / 0) disables that filter
	 * in the fixed, fully-prepared WHERE clause.
	 *
	 * @return array{status:string,provider:string,booking_id:int}
	 */
	private static function filter_values( array $args ): array {
		return [
			'status'     => ! empty( $args['status'] ) ? (string) $args['status'] : '',
			'provider'   => ! empty( $args['provider'] ) ? (string) $args['provider'] : '',
			'booking_id' => ! empty( $args['booking_id'] ) ? (int) $args['booking_id'] : 0,
		];
	}
}
