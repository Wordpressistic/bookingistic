<?php
/**
 * Admin settings page.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

defined( 'ABSPATH' ) || exit;

class Settings_Page {

	public static function render(): void {
		$settings = get_option( 'bookingistic_settings', [] );
		include BOOKINGISTIC_DIR . 'templates/admin/settings.php';
	}

	public static function maybe_save(): void {
		if ( ( $_POST['bookingistic_admin_action'] ?? '' ) !== 'save_settings' ) {
			return;
		}
		check_admin_referer( 'bookingistic_admin_settings' );

		$raw      = wp_unslash( $_POST );
		$settings = get_option( 'bookingistic_settings', [] );

		$settings['business_name']         = sanitize_text_field( $raw['business_name'] ?? '' );
		$settings['business_email']        = sanitize_email( $raw['business_email'] ?? '' );
		$settings['business_phone']        = sanitize_text_field( $raw['business_phone'] ?? '' );
		$settings['business_address']      = sanitize_textarea_field( $raw['business_address'] ?? '' );
		$settings['from_name']             = sanitize_text_field( $raw['from_name'] ?? '' );
		$settings['from_email']            = sanitize_email( $raw['from_email'] ?? '' );
		$settings['reply_to']              = sanitize_email( $raw['reply_to'] ?? '' );
		$settings['admin_notify']          = sanitize_email( $raw['admin_notify'] ?? '' );
		$settings['staff_notify']          = sanitize_email( $raw['staff_notify'] ?? '' );
		$settings['logo_url']              = esc_url_raw( $raw['logo_url'] ?? '' );
		$settings['footer_text']           = wp_kses_post( $raw['footer_text'] ?? '' );
		$settings['min_notice_hours']      = max( 0, (int) ( $raw['min_notice_hours'] ?? 4 ) );
		$settings['max_advance_days']      = max( 1, (int) ( $raw['max_advance_days'] ?? 60 ) );
		$settings['slot_interval']         = max( 5, (int) ( $raw['slot_interval'] ?? 15 ) );
		$settings['cancel_window_hrs']     = max( 0, (int) ( $raw['cancel_window_hrs'] ?? 12 ) );
		$settings['reschedule_window_hrs'] = max( 0, (int) ( $raw['reschedule_window_hrs'] ?? 24 ) );
		$settings['working_start']         = sanitize_text_field( $raw['working_start'] ?? '09:00' );
		$settings['working_end']           = sanitize_text_field( $raw['working_end'] ?? '17:00' );
		$settings['working_days']          = array_map( 'intval', (array) ( $raw['working_days'] ?? [] ) );
		$settings['timezone']              = sanitize_text_field( $raw['timezone'] ?? wp_timezone_string() );

		// Holidays.
		$holidays = [];
		foreach ( (array) ( $raw['holidays'] ?? [] ) as $entry ) {
			$from = \Bookingistic\Helpers\Sanitizer::iso_date( $entry['from'] ?? '' );
			$to   = \Bookingistic\Helpers\Sanitizer::iso_date( $entry['to'] ?? $from );
			if ( $from ) {
				$holidays[] = [ 'from' => $from, 'to' => $to ?: $from ];
			}
		}
		$settings['holidays'] = $holidays;

		// White-label.
		$settings['premium_license_active'] = ! empty( $raw['premium_license_active'] );
		$settings['remove_branding']        = ! empty( $raw['remove_branding'] );

		update_option( 'bookingistic_settings', $settings );

		wp_safe_redirect( admin_url( 'admin.php?page=bookingistic-settings&saved=1' ) );
		exit;
	}
}
