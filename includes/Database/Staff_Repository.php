<?php
/**
 * Staff repository.
 *
 * `working_hours` is stored as JSON. Two shapes are accepted on read:
 *
 *   Legacy (Phase 1): { "start": "09:00", "end": "17:00", "days": [1,2,3,4,5] }
 *   Per-day  (Phase 4): { "1": { "start": "09:00", "end": "17:00" }, "2": {...}, ... }
 *
 * `days_off` is a JSON array where each entry is either a "YYYY-MM-DD" string
 * or { "from": "YYYY-MM-DD", "to": "YYYY-MM-DD" }.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Database;

defined( 'ABSPATH' ) || exit;

class Staff_Repository {

	public static function table(): string {
		return Tables::staff_table();
	}

	public static function all( array $args = [] ): array {
		global $wpdb;
		$status = ! empty( $args['status'] ) ? (string) $args['status'] : '';
		$cache_key = Cache::key( 'staff_all', [ $status ], [ Cache::STAFF ] );
		$rows = wp_cache_get( $cache_key, Cache::STAFF, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM %i WHERE ( %s = '' OR status = %s ) ORDER BY name ASC",
					self::table(),
					$status,
					$status
				),
				ARRAY_A
			);
			wp_cache_set( $cache_key, $rows, Cache::STAFF );
		}
		return array_map( [ self::class, 'normalize' ], $rows ?: [] );
	}

	public static function find( int $id ): ?array {
		global $wpdb;
		$cache_key = Cache::key( 'staff_find', [ $id ], [ Cache::STAFF ] );
		$row = wp_cache_get( $cache_key, Cache::STAFF, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$row = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', self::table(), $id ),
				ARRAY_A
			);
			wp_cache_set( $cache_key, $row, Cache::STAFF );
		}
		return $row ? self::normalize( $row ) : null;
	}

	public static function for_service( int $service_id ): array {
		global $wpdb;
		$cache_key = Cache::key( 'staff_for_service', [ $service_id ], [ Cache::STAFF, Cache::STAFF_SERVICES ] );
		$rows = wp_cache_get( $cache_key, Cache::STAFF, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT s.* FROM %i s
					INNER JOIN %i ss ON ss.staff_id = s.id
					WHERE ss.service_id = %d AND s.status = 'active'
					ORDER BY s.name ASC",
					self::table(),
					Tables::staff_services_table(),
					$service_id
				),
				ARRAY_A
			);
			wp_cache_set( $cache_key, $rows, Cache::STAFF );
		}
		return array_map( [ self::class, 'normalize' ], $rows ?: [] );
	}

	public static function services_for_staff( int $staff_id ): array {
		global $wpdb;
		$cache_key = Cache::key( 'staff_services_for_staff', [ $staff_id ], [ Cache::STAFF_SERVICES ] );
		$ids = wp_cache_get( $cache_key, Cache::STAFF_SERVICES, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$ids = $wpdb->get_col(
				$wpdb->prepare( 'SELECT service_id FROM %i WHERE staff_id = %d', Tables::staff_services_table(), $staff_id )
			);
			wp_cache_set( $cache_key, $ids, Cache::STAFF_SERVICES );
		}
		return array_map( 'intval', $ids ?: [] );
	}

	public static function create( array $data ) {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$row = self::prepare_payload( $data ) + [
			'created_at' => $now,
			'updated_at' => $now,
		];
		$row['email'] = sanitize_email( $data['email'] ?? '' );
		if ( ! is_email( $row['email'] ) ) {
			return new \WP_Error( 'invalid_email', __( 'Valid staff email required.', 'bookingistic' ) );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write to the plugin's own custom table; its cache group is invalidated right after.
		$ok = $wpdb->insert( self::table(), $row );
		wp_cache_delete( 'last_changed', Cache::STAFF );
		if ( false === $ok ) {
			return new \WP_Error( 'staff_insert_failed', __( 'Could not create staff.', 'bookingistic' ) );
		}
		$id = (int) $wpdb->insert_id;
		self::set_services( $id, $data['service_ids'] ?? [] );
		return self::find( $id );
	}

	public static function update( int $id, array $data ) {
		global $wpdb;
		$existing = self::find( $id );
		if ( ! $existing ) {
			return new \WP_Error( 'not_found', __( 'Staff not found.', 'bookingistic' ) );
		}

		$update = self::prepare_payload( $data ) + [ 'updated_at' => current_time( 'mysql', true ) ];
		if ( array_key_exists( 'email', $data ) ) {
			$update['email'] = sanitize_email( $data['email'] );
			if ( ! is_email( $update['email'] ) ) {
				return new \WP_Error( 'invalid_email', __( 'Valid staff email required.', 'bookingistic' ) );
			}
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write to the plugin's own custom table; its cache group is invalidated right after.
		$wpdb->update( self::table(), $update, [ 'id' => $id ] );
		wp_cache_delete( 'last_changed', Cache::STAFF );
		if ( array_key_exists( 'service_ids', $data ) ) {
			self::set_services( $id, $data['service_ids'] );
		}
		return self::find( $id );
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write to the plugin's own custom table; its cache group is invalidated right after.
		$wpdb->delete( Tables::staff_services_table(), [ 'staff_id' => $id ], [ '%d' ] );
		wp_cache_delete( 'last_changed', Cache::STAFF_SERVICES );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write to the plugin's own custom table; its cache group is invalidated right after.
		$result = (bool) $wpdb->delete( self::table(), [ 'id' => $id ], [ '%d' ] );
		wp_cache_delete( 'last_changed', Cache::STAFF );
		return $result;
	}

	public static function set_services( int $staff_id, array $service_ids ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write to the plugin's own custom table; its cache group is invalidated right after.
		$wpdb->delete( Tables::staff_services_table(), [ 'staff_id' => $staff_id ], [ '%d' ] );
		wp_cache_delete( 'last_changed', Cache::STAFF_SERVICES );
		foreach ( array_unique( array_map( 'intval', $service_ids ) ) as $sid ) {
			if ( $sid > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write to the plugin's own custom table; its cache group is invalidated right after.
				$wpdb->insert( Tables::staff_services_table(), [ 'staff_id' => $staff_id, 'service_id' => $sid ], [ '%d', '%d' ] );
				wp_cache_delete( 'last_changed', Cache::STAFF_SERVICES );
			}
		}
	}

	private static function prepare_payload( array $data ): array {
		$payload = [];
		$map = [
			'name'   => 'sanitize_text_field',
			'phone'  => 'sanitize_text_field',
			'bio'    => 'sanitize_textarea_field',
			'status' => 'sanitize_key',
		];
		foreach ( $map as $field => $cb ) {
			if ( array_key_exists( $field, $data ) ) {
				$payload[ $field ] = call_user_func( $cb, $data[ $field ] );
			}
		}
		if ( array_key_exists( 'photo_url', $data ) ) {
			$payload['photo_url'] = esc_url_raw( $data['photo_url'] );
		}
		if ( array_key_exists( 'user_id', $data ) ) {
			$payload['user_id'] = (int) $data['user_id'] ?: null;
		}
		if ( array_key_exists( 'booking_limit_per_day', $data ) ) {
			$payload['booking_limit_per_day'] = max( 0, (int) $data['booking_limit_per_day'] );
		}
		foreach ( [ 'working_hours', 'days_off', 'special_availability' ] as $json_field ) {
			if ( array_key_exists( $json_field, $data ) ) {
				$payload[ $json_field ] = is_array( $data[ $json_field ] )
					? wp_json_encode( $data[ $json_field ] )
					: (string) $data[ $json_field ];
			}
		}
		return $payload;
	}

	public static function normalize( array $row ): array {
		$row['id']                    = (int) $row['id'];
		$row['user_id']               = $row['user_id'] !== null ? (int) $row['user_id'] : null;
		$row['booking_limit_per_day'] = (int) $row['booking_limit_per_day'];
		foreach ( [ 'working_hours', 'days_off', 'special_availability' ] as $json_field ) {
			if ( ! empty( $row[ $json_field ] ) ) {
				$decoded            = json_decode( $row[ $json_field ], true );
				$row[ $json_field ] = is_array( $decoded ) ? $decoded : [];
			} else {
				$row[ $json_field ] = [];
			}
		}
		return $row;
	}
}
