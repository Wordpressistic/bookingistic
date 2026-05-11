<?php
/**
 * Admin asset registration — scoped to Bookingistic pages only.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

defined( 'ABSPATH' ) || exit;

class Admin_Assets {

	public static function register(): void {
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue' ] );
	}

	public static function enqueue( string $hook ): void {
		if ( strpos( (string) $hook, 'bookingistic' ) === false ) {
			return;
		}
		wp_enqueue_style(
			'bookingistic-admin',
			BOOKINGISTIC_URL . 'assets/admin/css/admin.css',
			[],
			BOOKINGISTIC_VERSION
		);
		wp_enqueue_script(
			'bookingistic-admin',
			BOOKINGISTIC_URL . 'assets/admin/js/admin.js',
			[],
			BOOKINGISTIC_VERSION,
			true
		);
	}
}
