<?php
/**
 * Customer repository.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Database;

defined( 'ABSPATH' ) || exit;

class Customer_Repository {

	public static function table(): string {
		return Tables::customers_table();
	}

	public static function find( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ),
			ARRAY_A
		);
		return $row ? self::normalize( $row ) : null;
	}

	public static function find_by_email( string $email ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE email = %s', $email ),
			ARRAY_A
		);
		return $row ? self::normalize( $row ) : null;
	}

	/**
	 * Upsert a customer by email. Returns the customer row (or WP_Error).
	 */
	public static function upsert( array $data ) {
		global $wpdb;
		$email = sanitize_email( $data['email'] ?? '' );
		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'invalid_email', __( 'Valid email required.', 'bookingistic' ) );
		}

		$first = sanitize_text_field( $data['first_name'] ?? '' );
		$last  = sanitize_text_field( $data['last_name'] ?? '' );
		$full  = trim( $first . ' ' . $last );
		if ( ! $full && ! empty( $data['full_name'] ) ) {
			$full  = sanitize_text_field( $data['full_name'] );
			$parts = explode( ' ', $full, 2 );
			$first = $first ?: $parts[0];
			$last  = $last ?: ( $parts[1] ?? '' );
		}

		$now      = current_time( 'mysql', true );
		$existing = self::find_by_email( $email );

		$payload = [
			'first_name' => $first,
			'last_name'  => $last,
			'full_name'  => $full,
			'email'      => $email,
			'phone'      => sanitize_text_field( $data['phone'] ?? '' ),
			'timezone'   => sanitize_text_field( $data['timezone'] ?? '' ),
			'company'    => sanitize_text_field( $data['company'] ?? '' ),
			'website'    => esc_url_raw( $data['website'] ?? '' ),
			'notes'      => sanitize_textarea_field( $data['notes'] ?? '' ),
			'tags'       => sanitize_text_field( $data['tags'] ?? '' ),
			'source'     => sanitize_text_field( $data['source'] ?? '' ),
			'updated_at' => $now,
		];

		if ( $existing ) {
			// Only fill empty fields on subsequent bookings, never overwrite filled values.
			$update = [ 'updated_at' => $now ];
			foreach ( $payload as $k => $v ) {
				if ( $v !== '' && empty( $existing[ $k ] ) ) {
					$update[ $k ] = $v;
				}
			}
			$wpdb->update( self::table(), $update, [ 'id' => $existing['id'] ] );
			return self::find( (int) $existing['id'] );
		}

		$payload['created_at'] = $now;
		$ok = $wpdb->insert( self::table(), $payload );
		if ( false === $ok ) {
			return new \WP_Error( 'customer_insert_failed', __( 'Could not save customer.', 'bookingistic' ) );
		}
		return self::find( (int) $wpdb->insert_id );
	}

	public static function paginate( int $page = 1, int $per_page = 25 ): array {
		global $wpdb;
		$page     = max( 1, $page );
		$per_page = max( 1, min( 100, $per_page ) );
		$offset   = ( $page - 1 ) * $per_page;
		$rows     = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' ORDER BY created_at DESC LIMIT %d OFFSET %d', $per_page, $offset ),
			ARRAY_A
		);
		$total    = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() );
		return [
			'items' => array_map( [ self::class, 'normalize' ], $rows ?: [] ),
			'total' => $total,
			'page'  => $page,
			'pages' => (int) ceil( $total / $per_page ),
		];
	}

	public static function normalize( array $row ): array {
		$row['id'] = (int) $row['id'];
		return $row;
	}
}
