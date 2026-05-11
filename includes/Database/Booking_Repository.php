<?php
/**
 * Booking repository.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Database;

defined( 'ABSPATH' ) || exit;

class Booking_Repository {

	public const STATUSES         = [ 'pending', 'confirmed', 'cancelled', 'rescheduled', 'completed', 'no_show' ];
	public const PAYMENT_STATUSES = [ 'free', 'pending', 'paid', 'deposit_paid', 'failed', 'refunded', 'pay_later' ];

	public static function table(): string {
		return Tables::bookings_table();
	}

	public static function find( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ),
			ARRAY_A
		);
		return $row ? self::normalize( $row ) : null;
	}

	public static function find_by_token( string $field, string $token ): ?array {
		global $wpdb;
		if ( ! in_array( $field, [ 'cancel_token', 'reschedule_token' ], true ) ) {
			return null;
		}
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE {$field} = %s", $token ), // phpcs:ignore
			ARRAY_A
		);
		return $row ? self::normalize( $row ) : null;
	}

	public static function create( array $data ) {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$row = [
			'service_id'       => (int) ( $data['service_id'] ?? 0 ),
			'staff_id'         => (int) ( $data['staff_id'] ?? 0 ),
			'customer_id'      => (int) ( $data['customer_id'] ?? 0 ),
			'start_datetime'   => ! empty( $data['start_datetime'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $data['start_datetime'] ) ) : null,
			'end_datetime'     => ! empty( $data['end_datetime'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $data['end_datetime'] ) ) : null,
			'timezone'         => sanitize_text_field( $data['timezone'] ?? wp_timezone_string() ),
			'status'           => in_array( $data['status'] ?? '', self::STATUSES, true ) ? $data['status'] : 'pending',
			'payment_status'   => in_array( $data['payment_status'] ?? '', self::PAYMENT_STATUSES, true ) ? $data['payment_status'] : 'free',
			'payment_amount'   => (float) ( $data['payment_amount'] ?? 0 ),
			'source'           => sanitize_text_field( $data['source'] ?? '' ),
			'notes'            => sanitize_textarea_field( $data['notes'] ?? '' ),
			'internal_notes'   => sanitize_textarea_field( $data['internal_notes'] ?? '' ),
			'cancel_token'     => wp_generate_password( 40, false, false ),
			'reschedule_token' => wp_generate_password( 40, false, false ),
			'legacy_post_id'   => (int) ( $data['legacy_post_id'] ?? 0 ) ?: null,
			'created_at'       => $now,
			'updated_at'       => $now,
		];

		$ok = $wpdb->insert( self::table(), $row );
		if ( false === $ok ) {
			return new \WP_Error( 'booking_insert_failed', __( 'Could not save booking.', 'bookingistic' ) );
		}
		return self::find( (int) $wpdb->insert_id );
	}

	public static function update( int $id, array $data ) {
		global $wpdb;
		$update = [ 'updated_at' => current_time( 'mysql', true ) ];

		if ( array_key_exists( 'start_datetime', $data ) ) {
			$update['start_datetime'] = $data['start_datetime'] ? gmdate( 'Y-m-d H:i:s', strtotime( $data['start_datetime'] ) ) : null;
		}
		if ( array_key_exists( 'end_datetime', $data ) ) {
			$update['end_datetime'] = $data['end_datetime'] ? gmdate( 'Y-m-d H:i:s', strtotime( $data['end_datetime'] ) ) : null;
		}
		foreach ( [ 'staff_id', 'customer_id' ] as $int_field ) {
			if ( array_key_exists( $int_field, $data ) ) {
				$update[ $int_field ] = (int) $data[ $int_field ];
			}
		}
		if ( array_key_exists( 'timezone', $data ) ) {
			$update['timezone'] = sanitize_text_field( $data['timezone'] );
		}
		if ( ! empty( $data['status'] ) && in_array( $data['status'], self::STATUSES, true ) ) {
			$update['status'] = $data['status'];
		}
		if ( ! empty( $data['payment_status'] ) && in_array( $data['payment_status'], self::PAYMENT_STATUSES, true ) ) {
			$update['payment_status'] = $data['payment_status'];
		}
		if ( array_key_exists( 'internal_notes', $data ) ) {
			$update['internal_notes'] = sanitize_textarea_field( $data['internal_notes'] );
		}
		if ( array_key_exists( 'reminder_sent_at', $data ) ) {
			$update['reminder_sent_at'] = $data['reminder_sent_at'];
		}

		$wpdb->update( self::table(), $update, [ 'id' => $id ] );
		return self::find( $id );
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->delete( self::table(), [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Detect overlapping confirmed bookings for staff (or all staff if staff_id=0)
	 * within [start, end). Excludes the given booking id (for reschedules).
	 */
	public static function has_overlap( int $service_id, int $staff_id, string $start_utc, string $end_utc, int $exclude_id = 0 ): bool {
		global $wpdb;
		$staff_clause = $staff_id > 0
			? $wpdb->prepare( 'staff_id = %d', $staff_id )
			: $wpdb->prepare( 'service_id = %d', $service_id );

		$sql = "SELECT COUNT(*) FROM " . self::table() . "
			WHERE {$staff_clause}
			AND id <> %d
			AND status IN ('pending','confirmed','rescheduled')
			AND start_datetime < %s
			AND end_datetime > %s";
		$count = (int) $wpdb->get_var(
			$wpdb->prepare( $sql, $exclude_id, $end_utc, $start_utc ) // phpcs:ignore
		);
		return $count > 0;
	}

	public static function query( array $args = [] ): array {
		global $wpdb;
		$where = [ '1=1' ];
		$prep  = [];

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$prep[]  = $args['status'];
		}
		if ( ! empty( $args['payment_status'] ) ) {
			$where[] = 'payment_status = %s';
			$prep[]  = $args['payment_status'];
		}
		if ( ! empty( $args['service_id'] ) ) {
			$where[] = 'service_id = %d';
			$prep[]  = (int) $args['service_id'];
		}
		if ( ! empty( $args['staff_id'] ) ) {
			$where[] = 'staff_id = %d';
			$prep[]  = (int) $args['staff_id'];
		}
		if ( ! empty( $args['customer_id'] ) ) {
			$where[] = 'customer_id = %d';
			$prep[]  = (int) $args['customer_id'];
		}
		if ( ! empty( $args['from'] ) ) {
			$where[] = 'start_datetime >= %s';
			$prep[]  = gmdate( 'Y-m-d H:i:s', strtotime( $args['from'] ) );
		}
		if ( ! empty( $args['to'] ) ) {
			$where[] = 'start_datetime <= %s';
			$prep[]  = gmdate( 'Y-m-d H:i:s', strtotime( $args['to'] ) );
		}

		$where_sql = implode( ' AND ', $where );
		$per_page  = max( 1, min( 200, (int) ( $args['per_page'] ?? 25 ) ) );
		$page      = max( 1, (int) ( $args['page'] ?? 1 ) );
		$offset    = ( $page - 1 ) * $per_page;
		$order_by  = in_array( $args['orderby'] ?? '', [ 'start_datetime', 'created_at', 'status' ], true ) ? $args['orderby'] : 'created_at';
		$order     = strtoupper( $args['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';

		$prep[] = $per_page;
		$prep[] = $offset;

		$sql   = "SELECT * FROM " . self::table() . " WHERE {$where_sql} ORDER BY {$order_by} {$order} LIMIT %d OFFSET %d";
		$rows  = $wpdb->get_results( $wpdb->prepare( $sql, ...$prep ), ARRAY_A ); // phpcs:ignore

		$count_sql   = "SELECT COUNT(*) FROM " . self::table() . " WHERE {$where_sql}";
		$count_args  = array_slice( $prep, 0, count( $prep ) - 2 );
		$total       = (int) ( $count_args ? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$count_args ) ) : $wpdb->get_var( $count_sql ) ); // phpcs:ignore

		return [
			'items' => array_map( [ self::class, 'normalize' ], $rows ?: [] ),
			'total' => $total,
			'page'  => $page,
			'pages' => (int) ceil( $total / $per_page ),
		];
	}

	/**
	 * Fetch bookings (start times) within [start, end) for a service or staff
	 * — used by availability engine to subtract booked slots.
	 */
	public static function in_range( int $service_id, int $staff_id, string $start_utc, string $end_utc ): array {
		global $wpdb;
		$staff_clause = $staff_id > 0
			? $wpdb->prepare( 'AND staff_id = %d', $staff_id )
			: '';
		$sql = "SELECT id, start_datetime, end_datetime, staff_id FROM " . self::table() . "
			WHERE service_id = %d
			AND status IN ('pending','confirmed','rescheduled')
			AND start_datetime < %s AND end_datetime > %s
			{$staff_clause}";
		$rows = $wpdb->get_results(
			$wpdb->prepare( $sql, $service_id, $end_utc, $start_utc ), // phpcs:ignore
			ARRAY_A
		);
		return $rows ?: [];
	}

	/**
	 * Count active bookings for a staff member on a given UTC date range (used for
	 * the daily booking limit). Counts pending, confirmed and rescheduled bookings.
	 */
	public static function count_for_staff_day( int $staff_id, string $start_utc, string $end_utc ): int {
		global $wpdb;
		$sql = "SELECT COUNT(*) FROM " . self::table() . "
			WHERE staff_id = %d
			AND status IN ('pending','confirmed','rescheduled')
			AND start_datetime >= %s
			AND start_datetime < %s";
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $staff_id, $start_utc, $end_utc ) ); // phpcs:ignore
	}

	public static function due_reminders( int $hours_before = 24, int $limit = 50 ): array {
		global $wpdb;
		$now      = current_time( 'mysql', true );
		$window   = gmdate( 'Y-m-d H:i:s', strtotime( $now . ' +' . (int) $hours_before . ' hours' ) );
		$sql      = "SELECT * FROM " . self::table() . "
			WHERE status IN ('pending','confirmed')
			AND reminder_sent_at IS NULL
			AND start_datetime <= %s
			AND start_datetime > %s
			ORDER BY start_datetime ASC LIMIT %d";
		$rows = $wpdb->get_results(
			$wpdb->prepare( $sql, $window, $now, $limit ), // phpcs:ignore
			ARRAY_A
		);
		return array_map( [ self::class, 'normalize' ], $rows ?: [] );
	}

	public static function normalize( array $row ): array {
		$row['id']             = (int) $row['id'];
		$row['service_id']     = (int) $row['service_id'];
		$row['staff_id']       = (int) $row['staff_id'];
		$row['customer_id']    = (int) $row['customer_id'];
		$row['payment_amount'] = (float) $row['payment_amount'];
		$row['legacy_post_id'] = $row['legacy_post_id'] !== null ? (int) $row['legacy_post_id'] : null;
		return $row;
	}
}
