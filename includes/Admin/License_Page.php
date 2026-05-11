<?php
/**
 * Admin License page.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Core\License_Manager;

defined( 'ABSPATH' ) || exit;

class License_Page {

	public static function render(): void {
		$state    = License_Manager::state();
		$is_active = License_Manager::is_active();
		include BOOKINGISTIC_DIR . 'templates/admin/license.php';
	}

	public static function maybe_save(): void {
		$action = (string) ( $_POST['bookingistic_admin_action'] ?? '' );

		if ( $action === 'activate_license' ) {
			check_admin_referer( 'bookingistic_admin_license' );
			$key    = sanitize_text_field( wp_unslash( $_POST['license_key'] ?? '' ) );
			$result = License_Manager::activate( $key );
			$redir  = add_query_arg(
				[
					'page'    => 'bookingistic-license',
					'message' => rawurlencode( $result['message'] ?? '' ),
					'ok'      => $result['ok'] ? 1 : 0,
				],
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $redir );
			exit;
		}

		if ( $action === 'deactivate_license' ) {
			check_admin_referer( 'bookingistic_admin_license' );
			License_Manager::deactivate();
			wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-license&deactivated=1' ) );
			exit;
		}
	}
}
