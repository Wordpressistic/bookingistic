<?php
/**
 * Object-cache helpers for the plugin's custom tables.
 *
 * Each custom table has its own cache group. Read queries store their result
 * under a key salted with the group's "last_changed" value (the same pattern
 * core uses via wp_cache_get_last_changed()). Every write to a table deletes
 * that group's "last_changed" entry, so all cached reads for the table are
 * invalidated at once and the next read goes back to the database.
 *
 * Reads that must be fresh (slot locks, overlap / availability / daily-limit
 * checks, slug uniqueness, reminder scans, installer seed checks) bypass the
 * cache entirely.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Database;

defined( 'ABSPATH' ) || exit;

final class Cache {

	public const SERVICES          = 'bookingistic_services';
	public const STAFF             = 'bookingistic_staff';
	public const STAFF_SERVICES    = 'bookingistic_staff_services';
	public const CUSTOMERS         = 'bookingistic_customers';
	public const BOOKINGS          = 'bookingistic_bookings';
	public const BOOKING_META      = 'bookingistic_booking_meta';
	public const EMAIL_AUTOMATIONS = 'bookingistic_email_automations';
	public const EMAIL_LOGS        = 'bookingistic_email_logs';
	public const CALENDAR_EVENTS   = 'bookingistic_calendar_events';
	public const PAYMENTS          = 'bookingistic_payment_records';

	/**
	 * Every cache group used by the plugin.
	 *
	 * @return string[]
	 */
	public static function groups(): array {
		return [
			self::SERVICES,
			self::STAFF,
			self::STAFF_SERVICES,
			self::CUSTOMERS,
			self::BOOKINGS,
			self::BOOKING_META,
			self::EMAIL_AUTOMATIONS,
			self::EMAIL_LOGS,
			self::CALENDAR_EVENTS,
			self::PAYMENTS,
		];
	}

	/**
	 * Build a cache key for a read query.
	 *
	 * The key is salted with the last_changed value of every table the query
	 * reads, so a write to any of them invalidates it.
	 *
	 * @param string   $name   Query name.
	 * @param array    $args   Query arguments (bound values).
	 * @param string[] $groups Cache groups (tables) the query reads.
	 */
	public static function key( string $name, array $args, array $groups ): string {
		$salt = '';
		foreach ( $groups as $group ) {
			$salt .= $group . '=' . wp_cache_get_last_changed( $group ) . ';';
		}
		return $name . ':' . md5( (string) wp_json_encode( $args ) ) . ':' . md5( $salt );
	}

	/**
	 * Invalidate every plugin cache group (schema install/upgrade, uninstall).
	 */
	public static function invalidate_all(): void {
		foreach ( self::groups() as $group ) {
			wp_cache_delete( 'last_changed', $group );
		}
	}
}
