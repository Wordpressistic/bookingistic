<?php
/**
 * Email branding helper.
 *
 * All shipped functionality is free and fully enabled (WordPress.org
 * guideline: no locked features). The footer shows only the site owner's
 * custom footer text, which they configure themselves under Settings.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Email;

defined( 'ABSPATH' ) || exit;

class Branding_Manager {

	/**
	 * Whether the site owner may suppress the custom footer text. Always true:
	 * nothing in the free plugin is gated behind a license. The filter remains
	 * for backward compatibility with sites that used it.
	 */
	public static function can_remove_branding(): bool {
		/**
		 * Legacy filter — kept so existing code keeps working. The value has
		 * no effect on shipped functionality.
		 *
		 * @param bool $remove
		 */
		return (bool) apply_filters( 'bookingistic_can_remove_branding', true );
	}

	public static function footer_html(): string {
		$settings = get_option( 'bookingistic_settings', [] );
		$custom   = trim( (string) ( $settings['footer_text'] ?? '' ) );

		return $custom
			? '<p style="margin:16px 0 0;font-size:12px;color:#8B92A9;text-align:center">' . wp_kses_post( $custom ) . '</p>'
			: '';
	}
}
