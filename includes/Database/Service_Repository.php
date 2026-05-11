<?php
/**
 * Service repository.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Database;

defined( 'ABSPATH' ) || exit;

class Service_Repository {

	public static function table(): string {
		return Tables::services_table();
	}

	public static function all( array $args = [] ): array {
		global $wpdb;
		$status_clause = '';
		if ( ! empty( $args['status'] ) ) {
			$status_clause = $wpdb->prepare( 'WHERE status = %s', $args['status'] );
		}
		$rows = $wpdb->get_results( "SELECT * FROM " . self::table() . " {$status_clause} ORDER BY sort_order ASC, name ASC", ARRAY_A ); // phpcs:ignore
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

	public static function find_by_slug( string $slug ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE slug = %s', $slug ),
			ARRAY_A
		);
		return $row ? self::normalize( $row ) : null;
	}

	public static function create( array $data ) {
		global $wpdb;
		$now    = current_time( 'mysql', true );
		$slug   = $data['slug'] ?? sanitize_title( $data['name'] ?? '' );
		$insert = [
			'name'              => sanitize_text_field( $data['name'] ?? '' ),
			'slug'              => self::unique_slug( $slug ),
			'description'       => wp_kses_post( $data['description'] ?? '' ),
			'short_description' => sanitize_textarea_field( $data['short_description'] ?? '' ),
			'duration_minutes'  => max( 1, (int) ( $data['duration_minutes'] ?? 30 ) ),
			'buffer_before'     => max( 0, (int) ( $data['buffer_before'] ?? 0 ) ),
			'buffer_after'      => max( 0, (int) ( $data['buffer_after'] ?? 0 ) ),
			'price'             => (float) ( $data['price'] ?? 0 ),
			'category'          => sanitize_text_field( $data['category'] ?? '' ),
			'booking_type'      => sanitize_key( $data['booking_type'] ?? 'one_to_one' ),
			'confirmation_mode' => sanitize_key( $data['confirmation_mode'] ?? 'auto' ),
			'max_attendees'     => max( 1, (int) ( $data['max_attendees'] ?? 1 ) ),
			'status'            => sanitize_key( $data['status'] ?? 'active' ),
			'sort_order'        => (int) ( $data['sort_order'] ?? 0 ),
			'created_at'        => $now,
			'updated_at'        => $now,
		];
		$ok = $wpdb->insert( self::table(), $insert );
		if ( false === $ok ) {
			return new \WP_Error( 'service_insert_failed', __( 'Could not create service.', 'bookingistic' ) );
		}
		return self::find( (int) $wpdb->insert_id );
	}

	public static function update( int $id, array $data ) {
		global $wpdb;
		$existing = self::find( $id );
		if ( ! $existing ) {
			return new \WP_Error( 'not_found', __( 'Service not found.', 'bookingistic' ) );
		}
		$update = [ 'updated_at' => current_time( 'mysql', true ) ];

		$map = [
			'name'              => 'sanitize_text_field',
			'description'       => 'wp_kses_post',
			'short_description' => 'sanitize_textarea_field',
			'category'          => 'sanitize_text_field',
			'booking_type'      => 'sanitize_key',
			'confirmation_mode' => 'sanitize_key',
			'status'            => 'sanitize_key',
		];
		foreach ( $map as $field => $cb ) {
			if ( array_key_exists( $field, $data ) ) {
				$update[ $field ] = call_user_func( $cb, $data[ $field ] );
			}
		}
		foreach ( [ 'duration_minutes', 'buffer_before', 'buffer_after', 'max_attendees', 'sort_order' ] as $int_field ) {
			if ( array_key_exists( $int_field, $data ) ) {
				$update[ $int_field ] = (int) $data[ $int_field ];
			}
		}
		if ( array_key_exists( 'price', $data ) ) {
			$update['price'] = (float) $data['price'];
		}
		if ( array_key_exists( 'slug', $data ) && $data['slug'] !== $existing['slug'] ) {
			$update['slug'] = self::unique_slug( sanitize_title( $data['slug'] ), $id );
		}

		$wpdb->update( self::table(), $update, [ 'id' => $id ] );
		return self::find( $id );
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->delete( self::table(), [ 'id' => $id ], [ '%d' ] );
	}

	private static function unique_slug( string $slug, ?int $ignore_id = null ): string {
		global $wpdb;
		$base   = $slug ?: 'service';
		$slug   = $base;
		$suffix = 1;
		while ( true ) {
			$query = $ignore_id
				? $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE slug = %s AND id <> %d', $slug, $ignore_id )
				: $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE slug = %s', $slug );
			if ( ! $wpdb->get_var( $query ) ) {
				return $slug;
			}
			$suffix++;
			$slug = $base . '-' . $suffix;
		}
	}

	public static function normalize( array $row ): array {
		$row['id']               = (int) $row['id'];
		$row['duration_minutes'] = (int) $row['duration_minutes'];
		$row['buffer_before']    = (int) $row['buffer_before'];
		$row['buffer_after']     = (int) $row['buffer_after'];
		$row['price']            = (float) $row['price'];
		$row['max_attendees']    = (int) $row['max_attendees'];
		$row['sort_order']       = (int) $row['sort_order'];
		return $row;
	}
}
