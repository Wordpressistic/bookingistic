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
		$status = ! empty( $args['status'] ) ? (string) $args['status'] : '';
		$cache_key = Cache::key( 'service_all', [ $status ], [ Cache::SERVICES ] );
		$rows = wp_cache_get( $cache_key, Cache::SERVICES, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM %i WHERE ( %s = '' OR status = %s ) ORDER BY sort_order ASC, name ASC",
					self::table(),
					$status,
					$status
				),
				ARRAY_A
			);
			wp_cache_set( $cache_key, $rows, Cache::SERVICES );
		}
		return array_map( [ self::class, 'normalize' ], $rows ?: [] );
	}

	public static function find( int $id ): ?array {
		global $wpdb;
		$cache_key = Cache::key( 'service_find', [ $id ], [ Cache::SERVICES ] );
		$row = wp_cache_get( $cache_key, Cache::SERVICES, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$row = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', self::table(), $id ),
				ARRAY_A
			);
			wp_cache_set( $cache_key, $row, Cache::SERVICES );
		}
		return $row ? self::normalize( $row ) : null;
	}

	public static function find_by_slug( string $slug ): ?array {
		global $wpdb;
		$cache_key = Cache::key( 'service_find_by_slug', [ $slug ], [ Cache::SERVICES ] );
		$row = wp_cache_get( $cache_key, Cache::SERVICES, false, $cache_found );
		if ( ! $cache_found ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin's own custom table; the result is object-cached (wp_cache_set below) and invalidated on every write.
			$row = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM %i WHERE slug = %s', self::table(), $slug ),
				ARRAY_A
			);
			wp_cache_set( $cache_key, $row, Cache::SERVICES );
		}
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
			'deposit_percent'   => max( 0.0, min( 100.0, (float) ( $data['deposit_percent'] ?? 0 ) ) ),
			'category'          => sanitize_text_field( $data['category'] ?? '' ),
			'booking_type'      => sanitize_key( $data['booking_type'] ?? 'one_to_one' ),
			'confirmation_mode' => sanitize_key( $data['confirmation_mode'] ?? 'auto' ),
			'max_attendees'     => max( 1, (int) ( $data['max_attendees'] ?? 1 ) ),
			'status'            => sanitize_key( $data['status'] ?? 'active' ),
			'sort_order'        => (int) ( $data['sort_order'] ?? 0 ),
			'created_at'        => $now,
			'updated_at'        => $now,
		];
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write to the plugin's own custom table; its cache group is invalidated right after.
		$ok = $wpdb->insert( self::table(), $insert );
		wp_cache_delete( 'last_changed', Cache::SERVICES );
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
		if ( array_key_exists( 'deposit_percent', $data ) ) {
			$update['deposit_percent'] = max( 0.0, min( 100.0, (float) $data['deposit_percent'] ) );
		}
		if ( array_key_exists( 'slug', $data ) && $data['slug'] !== $existing['slug'] ) {
			$update['slug'] = self::unique_slug( sanitize_title( $data['slug'] ), $id );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write to the plugin's own custom table; its cache group is invalidated right after.
		$wpdb->update( self::table(), $update, [ 'id' => $id ] );
		wp_cache_delete( 'last_changed', Cache::SERVICES );
		return self::find( $id );
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write to the plugin's own custom table; its cache group is invalidated right after.
		$result = (bool) $wpdb->delete( self::table(), [ 'id' => $id ], [ '%d' ] );
		wp_cache_delete( 'last_changed', Cache::SERVICES );
		return $result;
	}

	private static function unique_slug( string $slug, ?int $ignore_id = null ): string {
		global $wpdb;
		$base   = $slug ?: 'service';
		$slug   = $base;
		$suffix = 1;
		$table  = self::table();
		while ( true ) {
			if ( $ignore_id ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Slug-uniqueness check made right before a write; must read live data so duplicate slugs cannot be created.
				$existing = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE slug = %s AND id <> %d', $table, $slug, $ignore_id ) );
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Slug-uniqueness check made right before a write; must read live data so duplicate slugs cannot be created.
				$existing = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE slug = %s', $table, $slug ) );
			}
			if ( ! $existing ) {
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
		$row['deposit_percent']  = isset( $row['deposit_percent'] ) ? (float) $row['deposit_percent'] : 0.0;
		$row['max_attendees']    = (int) $row['max_attendees'];
		$row['sort_order']       = (int) $row['sort_order'];
		return $row;
	}
}
