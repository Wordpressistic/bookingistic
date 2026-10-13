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
		$table = Tables::payment_records_table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore
		return $row ?: null;
	}

	public static function latest_for_booking( int $booking_id, string $provider = '' ): ?array {
		global $wpdb;
		$table = Tables::payment_records_table();
		if ( $provider ) {
			$row = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE booking_id = %d AND provider = %s ORDER BY id DESC LIMIT 1", $booking_id, $provider ), // phpcs:ignore
				ARRAY_A
			);
		} else {
			$row = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE booking_id = %d ORDER BY id DESC LIMIT 1", $booking_id ), // phpcs:ignore
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
		$table = Tables::payment_records_table();

		$where = [ '1=1' ];
		$prep  = [];
		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$prep[]  = (string) $args['status'];
		}
		if ( ! empty( $args['provider'] ) ) {
			$where[] = 'provider = %s';
			$prep[]  = (string) $args['provider'];
		}
		if ( ! empty( $args['booking_id'] ) ) {
			$where[] = 'booking_id = %d';
			$prep[]  = (int) $args['booking_id'];
		}
		$where_sql = implode( ' AND ', $where );

		$limit  = max( 1, min( 200, (int) ( $args['per_page'] ?? 50 ) ) );
		$offset = max( 0, (int) ( $args['offset'] ?? 0 ) );
		$prep[] = $limit;
		$prep[] = $offset;

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d", ...$prep ), // phpcs:ignore
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : [];
	}

	public static function count( array $args = [] ): int {
		global $wpdb;
		$table = Tables::payment_records_table();

		$where = [ '1=1' ];
		$prep  = [];
		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$prep[]  = (string) $args['status'];
		}
		if ( ! empty( $args['provider'] ) ) {
			$where[] = 'provider = %s';
			$prep[]  = (string) $args['provider'];
		}
		if ( ! empty( $args['booking_id'] ) ) {
			$where[] = 'booking_id = %d';
			$prep[]  = (int) $args['booking_id'];
		}
		$where_sql = implode( ' AND ', $where );

		return (int) ( $prep
			? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", ...$prep ) ) // phpcs:ignore
			: $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) ); // phpcs:ignore
	}
}
