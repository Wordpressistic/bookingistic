<?php
/**
 * Staff repository.
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
