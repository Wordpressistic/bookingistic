<?php
/**
 * Email automation repository.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Database;

defined( 'ABSPATH' ) || exit;

class Email_Automation_Repository {

	public static function table(): string {
		return Tables::email_automations_table();
	}

	public static function all(): array {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY trigger_event ASC, name ASC', ARRAY_A );
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

	public static function for_trigger( string $event, int $service_id = 0 ): array {
		global $wpdb;
		$sql = "SELECT * FROM " . self::table() . "
			WHERE status = 'active'
			AND trigger_event = %s
			AND (service_id = 0 OR service_id = %d)
			ORDER BY service_id DESC";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $event, $service_id ), ARRAY_A ); // phpcs:ignore
		return array_map( [ self::class, 'normalize' ], $rows ?: [] );
	}

	public static function update( int $id, array $data ) {
		global $wpdb;
		$now    = current_time( 'mysql', true );
		$update = [ 'updated_at' => $now ];

		$map = [
			'name'             => 'sanitize_text_field',
			'trigger_event'    => 'sanitize_key',
			'recipient_type'   => 'sanitize_key',
			'recipient_custom' => 'sanitize_email',
			'delay_unit'       => 'sanitize_key',
			'subject'          => 'sanitize_text_field',
			'status'           => 'sanitize_key',
		];
		foreach ( $map as $field => $cb ) {
			if ( array_key_exists( $field, $data ) ) {
				$update[ $field ] = call_user_func( $cb, $data[ $field ] );
			}
		}
		if ( array_key_exists( 'body', $data ) ) {
			$update['body'] = wp_kses_post( $data['body'] );
		}
		foreach ( [ 'delay_amount', 'service_id' ] as $int_field ) {
			if ( array_key_exists( $int_field, $data ) ) {
				$update[ $int_field ] = (int) $data[ $int_field ];
			}
		}
		$wpdb->update( self::table(), $update, [ 'id' => $id ] );
		return self::find( $id );
	}

	public static function normalize( array $row ): array {
		$row['id']           = (int) $row['id'];
		$row['delay_amount'] = (int) $row['delay_amount'];
		$row['service_id']   = (int) $row['service_id'];
		return $row;
	}
}
