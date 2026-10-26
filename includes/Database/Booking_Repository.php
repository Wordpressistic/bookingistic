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

	/**
	 * Serialize concurrent slot submissions per staff (or per service when
	 * no staff is assigned): opens a transaction and takes a row lock on the
	 * staff (or service) row, so two requests cannot both pass the overlap
	 * check before either inserts. InnoDB required — on MyISAM or when a
	 * transaction is already open the lock silently degrades to the
	 * previous check-then-insert behavior.
	 *
	 * Pair with self::commit_slot_lock() / self::rollback_slot_lock().
	 */
	public static function begin_slot_lock( int $staff_id, int $service_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Slot-lock transaction control (START TRANSACTION / SELECT ... FOR UPDATE / COMMIT / ROLLBACK); must hit the database live, never cacheable.
		if ( $wpdb->query( 'START TRANSACTION' ) === false ) {
			return false;
		}

		if ( $staff_id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Slot-lock transaction control (START TRANSACTION / SELECT ... FOR UPDATE / COMMIT / ROLLBACK); must hit the database live, never cacheable.
			$locked = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE id = %d FOR UPDATE', Tables::staff_table(), $staff_id ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Slot-lock transaction control (START TRANSACTION / SELECT ... FOR UPDATE / COMMIT / ROLLBACK); must hit the database live, never cacheable.
			$locked = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE id = %d FOR UPDATE', Tables::services_table(), $service_id ) );
		}

		if ( null === $locked ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Slot-lock transaction control (START TRANSACTION / SELECT ... FOR UPDATE / COMMIT / ROLLBACK); must hit the database live, never cacheable.
			$wpdb->query( 'ROLLBACK' );
			return false;
		}

		return true;
	}

	public static function commit_slot_lock(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Transaction control for the slot lock; plugin cache groups are invalidated right after.
		$wpdb->query( 'COMMIT' );
		// Rows written inside the transaction (and reads other requests cached
		// before it ended) must not outlive it: invalidate after it ends.
		foreach ( Cache::groups() as $cache_group ) {
			wp_cache_delete( 'last_changed', $cache_group );
		}
	}

	public static function rollback_slot_lock(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Transaction control for the slot lock; plugin cache groups are invalidated right after.
		$wpdb->query( 'ROLLBACK' );
		// Rows written inside the transaction (and reads other requests cached
		// before it ended) must not outlive it: invalidate after it ends.
		foreach ( Cache::groups() as $cache_group ) {
			wp_cache_delete( 'last_changed', $cache_group );
		}
	}


	public static function find( int $id ): ?array {
		global $wpdb;
		$cache_key = Cache::key( 'booking_find', [ $id ], [ Cache::BOOKINGS ] );
		$row = wp_cache_get( $cache_key, Cache::BOOKINGS, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$row = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', self::table(), $id ),
				ARRAY_A
			);
			wp_cache_set( $cache_key, $row, Cache::BOOKINGS );
		}
		return $row ? self::normalize( $row ) : null;
	}

	public static function find_by_token( string $field, string $token ): ?array {
		global $wpdb;
		if ( ! in_array( $field, [ 'cancel_token', 'reschedule_token' ], true ) ) {
			return null;
		}
		// $field is whitelisted above and bound as an identifier via %i.
		$cache_key = Cache::key( 'booking_find_by_token', [ $field, $token ], [ Cache::BOOKINGS ] );
		$row = wp_cache_get( $cache_key, Cache::BOOKINGS, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$row = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM %i WHERE %i = %s', self::table(), $field, $token ),
				ARRAY_A
			);
			wp_cache_set( $cache_key, $row, Cache::BOOKINGS );
		}
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write to the plugin's own custom table; its cache group is invalidated right after.
		$ok = $wpdb->insert( self::table(), $row );
		wp_cache_delete( 'last_changed', Cache::BOOKINGS );
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write to the plugin's own custom table; its cache group is invalidated right after.
		$wpdb->update( self::table(), $update, [ 'id' => $id ] );
		wp_cache_delete( 'last_changed', Cache::BOOKINGS );
		return self::find( $id );
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write to the plugin's own custom table; its cache group is invalidated right after.
		$result = (bool) $wpdb->delete( self::table(), [ 'id' => $id ], [ '%d' ] );
		wp_cache_delete( 'last_changed', Cache::BOOKINGS );
		return $result;
	}

	/**
	 * Detect overlapping confirmed bookings for staff (or all staff if staff_id=0)
	 * within [start, end). Excludes the given booking id (for reschedules).
	 */
	public static function has_overlap( int $service_id, int $staff_id, string $start_utc, string $end_utc, int $exclude_id = 0 ): bool {
		global $wpdb;
		// Scope by staff when one is assigned, otherwise by service (whitelisted column).
		$scope_column = $staff_id > 0 ? 'staff_id' : 'service_id';
		$scope_value  = $staff_id > 0 ? $staff_id : $service_id;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Double-booking overlap check; must read live data inside the slot lock, never a cached result.
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i
				WHERE %i = %d
				AND id <> %d
				AND status IN ('pending','confirmed','rescheduled')
				AND start_datetime < %s
				AND end_datetime > %s",
				self::table(),
				$scope_column,
				$scope_value,
				$exclude_id,
				$end_utc,
				$start_utc
			)
		);
		return $count > 0;
	}

	public static function query( array $args = [] ): array {
		global $wpdb;

		// Every filter is always present in the SQL and bound as a value; an
		// empty value ('' / 0) disables that filter. No SQL is built from strings.
		$status         = ! empty( $args['status'] ) ? (string) $args['status'] : '';
		$payment_status = ! empty( $args['payment_status'] ) ? (string) $args['payment_status'] : '';
		$service_id     = ! empty( $args['service_id'] ) ? (int) $args['service_id'] : 0;
		$staff_id       = ! empty( $args['staff_id'] ) ? (int) $args['staff_id'] : 0;
		$customer_id    = ! empty( $args['customer_id'] ) ? (int) $args['customer_id'] : 0;
		$from           = ! empty( $args['from'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $args['from'] ) ) : '';
		$to             = ! empty( $args['to'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $args['to'] ) ) : '';

		$per_page = max( 1, min( 200, (int) ( $args['per_page'] ?? 25 ) ) );
		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$offset   = ( $page - 1 ) * $per_page;
		// ORDER BY column: strict whitelist, bound with %i. Direction: fixed SQL per branch.
		$order_by = in_array( $args['orderby'] ?? '', [ 'start_datetime', 'created_at', 'status' ], true ) ? $args['orderby'] : 'created_at';
		$asc      = strtoupper( $args['order'] ?? 'DESC' ) === 'ASC';
		$table    = self::table();

		if ( $asc ) {
			$cache_key = Cache::key( 'booking_query_asc', [ $status, $payment_status, $service_id, $staff_id, $customer_id, $from, $to, $order_by, $per_page, $offset ], [ Cache::BOOKINGS ] );
			$rows = wp_cache_get( $cache_key, Cache::BOOKINGS, false, $cache_found );
			if ( ! $cache_found ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM %i
						WHERE ( %s = '' OR status = %s )
						AND ( %s = '' OR payment_status = %s )
						AND ( %d = 0 OR service_id = %d )
						AND ( %d = 0 OR staff_id = %d )
						AND ( %d = 0 OR customer_id = %d )
						AND ( %s = '' OR start_datetime >= %s )
						AND ( %s = '' OR start_datetime <= %s )
						ORDER BY %i ASC LIMIT %d OFFSET %d",
						$table,
						$status,
						$status,
						$payment_status,
						$payment_status,
						$service_id,
						$service_id,
						$staff_id,
						$staff_id,
						$customer_id,
						$customer_id,
						$from,
						$from,
						$to,
						$to,
						$order_by,
						$per_page,
						$offset
					),
					ARRAY_A
				);
				wp_cache_set( $cache_key, $rows, Cache::BOOKINGS );
			}
		} else {
			$cache_key = Cache::key( 'booking_query_desc', [ $status, $payment_status, $service_id, $staff_id, $customer_id, $from, $to, $order_by, $per_page, $offset ], [ Cache::BOOKINGS ] );
			$rows = wp_cache_get( $cache_key, Cache::BOOKINGS, false, $cache_found );
			if ( ! $cache_found ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM %i
						WHERE ( %s = '' OR status = %s )
						AND ( %s = '' OR payment_status = %s )
						AND ( %d = 0 OR service_id = %d )
						AND ( %d = 0 OR staff_id = %d )
						AND ( %d = 0 OR customer_id = %d )
						AND ( %s = '' OR start_datetime >= %s )
						AND ( %s = '' OR start_datetime <= %s )
						ORDER BY %i DESC LIMIT %d OFFSET %d",
						$table,
						$status,
						$status,
						$payment_status,
						$payment_status,
						$service_id,
						$service_id,
						$staff_id,
						$staff_id,
						$customer_id,
						$customer_id,
						$from,
						$from,
						$to,
						$to,
						$order_by,
						$per_page,
						$offset
					),
					ARRAY_A
				);
				wp_cache_set( $cache_key, $rows, Cache::BOOKINGS );
			}
		}

		$cache_key = Cache::key( 'booking_query_count', [ $status, $payment_status, $service_id, $staff_id, $customer_id, $from, $to ], [ Cache::BOOKINGS ] );
		$total = wp_cache_get( $cache_key, Cache::BOOKINGS, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM %i
					WHERE ( %s = '' OR status = %s )
					AND ( %s = '' OR payment_status = %s )
					AND ( %d = 0 OR service_id = %d )
					AND ( %d = 0 OR staff_id = %d )
					AND ( %d = 0 OR customer_id = %d )
					AND ( %s = '' OR start_datetime >= %s )
					AND ( %s = '' OR start_datetime <= %s )",
					$table,
					$status,
					$status,
					$payment_status,
					$payment_status,
					$service_id,
					$service_id,
					$staff_id,
					$staff_id,
					$customer_id,
					$customer_id,
					$from,
					$from,
					$to,
					$to
				)
			);
			wp_cache_set( $cache_key, $total, Cache::BOOKINGS );
		}

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
		// staff_id = 0 disables the staff filter (value-bound, no SQL concatenation).
		$staff_id = max( 0, $staff_id );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Availability (busy-slot) lookup used to offer and validate slots; must read live data so no slot is offered or accepted from a stale cache.
		$rows     = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, start_datetime, end_datetime, staff_id FROM %i
				WHERE service_id = %d
				AND status IN ('pending','confirmed','rescheduled')
				AND start_datetime < %s AND end_datetime > %s
				AND ( %d = 0 OR staff_id = %d )",
				self::table(),
				$service_id,
				$end_utc,
				$start_utc,
				$staff_id,
				$staff_id
			),
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
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Per-staff daily booking limit check made while booking; must read live data.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i
				WHERE staff_id = %d
				AND status IN ('pending','confirmed','rescheduled')
				AND start_datetime >= %s
				AND start_datetime < %s",
				self::table(),
				$staff_id,
				$start_utc,
				$end_utc
			)
		);
	}

	public static function due_reminders( int $hours_before = 24, int $limit = 50 ): array {
		global $wpdb;
		$now    = current_time( 'mysql', true );
		$window = gmdate( 'Y-m-d H:i:s', strtotime( $now . ' +' . (int) $hours_before . ' hours' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cron reminder scan; must read live data so a reminder is never sent twice from a stale cache.
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %i
				WHERE status IN ('pending','confirmed')
				AND reminder_sent_at IS NULL
				AND start_datetime <= %s
				AND start_datetime > %s
				ORDER BY start_datetime ASC LIMIT %d",
				self::table(),
				$window,
				$now,
				$limit
			),
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
