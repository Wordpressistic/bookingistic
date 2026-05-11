<?php
/**
 * Email branding helper.
 *
 * Premium-ready: `can_remove_branding()` currently returns false (free tier),
 * but every email rendering path consults this helper, so removing the
 * branding footer is a one-line flip when premium gating goes live.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Email;

defined( 'ABSPATH' ) || exit;

class Branding_Manager {

	public static function can_remove_branding(): bool {
		$settings = get_option( 'bookingistic_settings', [] );
		$has_license = ! empty( $settings['premium_license_active'] );
		$opted_in    = ! empty( $settings['remove_branding'] );

		/**
		 * Premium plugins / license layers can return true here regardless of stored settings.
		 *
		 * Default: only allow removal when both a premium license is active AND
		 * the site owner has opted in via Settings → White label.
		 */
		return (bool) apply_filters(
			'bookingistic_can_remove_branding',
			$has_license && $opted_in
		);
	}

	public static function footer_html(): string {
		$settings    = get_option( 'bookingistic_settings', [] );
		$custom      = trim( (string) ( $settings['footer_text'] ?? '' ) );
		$brand_block = '';

		if ( ! self::can_remove_branding() ) {
			$brand_block = '<p style="margin:24px 0 0;font-size:12px;color:#8B92A9;text-align:center">'
				. esc_html__( 'Powered by Bookingistic by WordPressistic', 'bookingistic' ) . '<br>'
				. esc_html__( 'Build Your Business Automation with AI', 'bookingistic' ) . '<br>'
				. '<a href="https://www.wordpressistic.com" style="color:#5EA1FF">https://www.wordpressistic.com</a>'
				. '</p>';
		}

		$custom_block = $custom
			? '<p style="margin:16px 0 0;font-size:12px;color:#8B92A9;text-align:center">' . wp_kses_post( $custom ) . '</p>'
			: '';

		return $custom_block . $brand_block;
	}
}
