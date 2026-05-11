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
		$where = '';
		if ( ! empty( $args['status'] ) ) {
			$where = $wpdb->prepare( 'WHERE status = %s', $args['status'] );
		}
		$rows = $wpdb->get_results( "SELECT * FROM " . self::table() . " {$where} ORDER BY name ASC", ARRAY_A ); // phpcs:ignore
		return array_map( [ self::class, 'normalize' ], $rows ?: [] );
	}

	public static function find( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ),
			ARRAY_A
		);
		return $row ? self::normalize( $row ) : null;
	}

	public static function for_service( int $service_id ): array {
		global $wpdb;
		$sql = "SELECT s.* FROM " . self::table() . " s
			INNER JOIN " . Tables::staff_services_table() . " ss ON ss.staff_id = s.id
			WHERE ss.service_id = %d AND s.status = 'active'
			ORDER BY s.name ASC";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $service_id ), ARRAY_A ); // phpcs:ignore
		return array_map( [ self::class, 'normalize' ], $rows ?: [] );
	}

	public static function services_for_staff( int $staff_id ): array {
		global $wpdb;
		$ids = $wpdb->get_col(
			$wpdb->prepare( 'SELECT service_id FROM ' . Tables::staff_services_table() . ' WHERE staff_id = %d', $staff_id )
		);
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
		$ok = $wpdb->insert( self::table(), $row );
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
		$wpdb->update( self::table(), $update, [ 'id' => $id ] );
		if ( array_key_exists( 'service_ids', $data ) ) {
			self::set_services( $id, $data['service_ids'] );
		}
		return self::find( $id );
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		$wpdb->delete( Tables::staff_services_table(), [ 'staff_id' => $id ], [ '%d' ] );
		return (bool) $wpdb->delete( self::table(), [ 'id' => $id ], [ '%d' ] );
	}

	public static function set_services( int $staff_id, array $service_ids ): void {
		global $wpdb;
		$wpdb->delete( Tables::staff_services_table(), [ 'staff_id' => $staff_id ], [ '%d' ] );
		foreach ( array_unique( array_map( 'intval', $service_ids ) ) as $sid ) {
			if ( $sid > 0 ) {
				$wpdb->insert( Tables::staff_services_table(), [ 'staff_id' => $staff_id, 'service_id' => $sid ], [ '%d', '%d' ] );
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
