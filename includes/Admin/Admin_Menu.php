<?php
/**
 * Admin menu registration.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Core\Capabilities;

defined( 'ABSPATH' ) || exit;

class Admin_Menu {

	public const SLUG = 'bookingistic';

	public static function register(): void {
		add_action( 'admin_menu', [ self::class, 'register_menu' ] );
		add_action( 'admin_init', [ self::class, 'handle_post_actions' ] );
	}

	public static function register_menu(): void {
		$cap = Capabilities::MANAGE;

		add_menu_page(
			__( 'Bookingistic', 'bookingistic' ),
			__( 'Bookingistic', 'bookingistic' ),
			$cap,
			self::SLUG,
			[ Dashboard_Page::class, 'render' ],
			'dashicons-calendar-alt',
			26
		);
		add_submenu_page( self::SLUG, __( 'Dashboard', 'bookingistic' ), __( 'Dashboard', 'bookingistic' ), $cap, self::SLUG, [ Dashboard_Page::class, 'render' ] );
		add_submenu_page( self::SLUG, __( 'Calendar', 'bookingistic' ), __( 'Calendar', 'bookingistic' ), $cap, self::SLUG . '-calendar', [ Calendar_Page::class, 'render' ] );
		add_submenu_page( self::SLUG, __( 'Bookings', 'bookingistic' ), __( 'Bookings', 'bookingistic' ), $cap, self::SLUG . '-bookings', [ Bookings_Page::class, 'render' ] );
		add_submenu_page( self::SLUG, __( 'Services', 'bookingistic' ), __( 'Services', 'bookingistic' ), $cap, self::SLUG . '-services', [ Services_Page::class, 'render' ] );
		add_submenu_page( self::SLUG, __( 'Staff', 'bookingistic' ), __( 'Staff', 'bookingistic' ), $cap, self::SLUG . '-staff', [ Staff_Page::class, 'render' ] );
		add_submenu_page( self::SLUG, __( 'Customers', 'bookingistic' ), __( 'Customers', 'bookingistic' ), $cap, self::SLUG . '-customers', [ Customers_Page::class, 'render' ] );
		add_submenu_page( self::SLUG, __( 'Email Automations', 'bookingistic' ), __( 'Email Automations', 'bookingistic' ), $cap, self::SLUG . '-emails', [ Email_Automations_Page::class, 'render' ] );
		add_submenu_page( self::SLUG, __( 'Email Logs', 'bookingistic' ), __( 'Email Logs', 'bookingistic' ), $cap, self::SLUG . '-email-logs', [ Email_Logs_Page::class, 'render' ] );
		add_submenu_page( self::SLUG, __( 'Reports', 'bookingistic' ), __( 'Reports', 'bookingistic' ), $cap, self::SLUG . '-reports', [ Reports_Page::class, 'render' ] );
		add_submenu_page( self::SLUG, __( 'Integrations', 'bookingistic' ), __( 'Integrations', 'bookingistic' ), $cap, self::SLUG . '-integrations', [ Integrations_Page::class, 'render' ] );
		add_submenu_page( self::SLUG, __( 'Settings', 'bookingistic' ), __( 'Settings', 'bookingistic' ), $cap, self::SLUG . '-settings', [ Settings_Page::class, 'render' ] );
	}

	public static function handle_post_actions(): void {
		if ( ! is_admin() || empty( $_POST['bookingistic_admin_action'] ) ) {
			return;
		}
		Services_Page::maybe_save();
		Settings_Page::maybe_save();
		Bookings_Page::maybe_act();
		Email_Automations_Page::maybe_save();
		Staff_Page::maybe_save();
		Integrations_Page::maybe_save();
	}
}
