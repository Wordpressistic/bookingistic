<?php
/**
 * Plugin capabilities.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Core;

defined( 'ABSPATH' ) || exit;

class Capabilities {

	public const MANAGE          = 'manage_bookingistic';
	public const VIEW            = 'view_bookingistic';
	public const CREATE_BOOKINGS = 'create_bookingistic_bookings';
	public const EDIT_BOOKINGS   = 'edit_bookingistic_bookings';
	public const DELETE_BOOKINGS = 'delete_bookingistic_bookings';
	public const MANAGE_SERVICES = 'manage_bookingistic_services';
	public const MANAGE_STAFF    = 'manage_bookingistic_staff';
	public const MANAGE_SETTINGS = 'manage_bookingistic_settings';
	public const MANAGE_EMAILS   = 'manage_bookingistic_email_automations';

	public static function all(): array {
		return [
			self::MANAGE,
			self::VIEW,
			self::CREATE_BOOKINGS,
			self::EDIT_BOOKINGS,
			self::DELETE_BOOKINGS,
			self::MANAGE_SERVICES,
			self::MANAGE_STAFF,
			self::MANAGE_SETTINGS,
			self::MANAGE_EMAILS,
		];
	}

	public static function add_for_admin(): void {
		$role = get_role( 'administrator' );
		if ( ! $role ) {
			return;
		}
		foreach ( self::all() as $cap ) {
			$role->add_cap( $cap );
		}
	}

	public static function remove_all(): void {
		$role = get_role( 'administrator' );
		if ( ! $role ) {
			return;
		}
		foreach ( self::all() as $cap ) {
			$role->remove_cap( $cap );
		}
	}

	public static function current_user_can_manage(): bool {
		return current_user_can( self::MANAGE ) || current_user_can( 'manage_options' );
	}
}
