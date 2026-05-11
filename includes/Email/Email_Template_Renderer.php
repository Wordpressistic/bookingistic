<?php
/**
 * Email template renderer.
 *
 * Variable substitution is safe-by-default: unknown variables become empty strings,
 * and every value is HTML-escaped before being substituted into the template body.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Email;

use Bookingistic\Database\Customer_Repository;
use Bookingistic\Database\Service_Repository;
use Bookingistic\Helpers\Date_Time;
use Bookingistic\Helpers\Token;

defined( 'ABSPATH' ) || exit;

class Email_Template_Renderer {

	/**
	 * Build the {var} replacement map for a booking.
	 */
	public static function variables_for_booking( array $booking, ?array $customer = null, ?array $service = null ): array {
		$customer = $customer ?: ( $booking['customer_id'] ? Customer_Repository::find( (int) $booking['customer_id'] ) : null );
		$service  = $service ?: ( $booking['service_id'] ? Service_Repository::find( (int) $booking['service_id'] ) : null );
		$settings = get_option( 'bookingistic_settings', [] );

		$tz_str = $booking['timezone'] ?? wp_timezone_string();
		try {
			$tz = new \DateTimeZone( $tz_str );
		} catch ( \Throwable $e ) {
			$tz = Date_Time::site_timezone();
		}

		$date_str = '';
		$time_str = '';
		if ( ! empty( $booking['start_datetime'] ) ) {
			$date_str = Date_Time::format_date( $booking['start_datetime'], $tz );
			$time_str = Date_Time::format_time( $booking['start_datetime'], $tz );
		}

		return [
			'customer_name'       => $customer['full_name'] ?? '',
			'customer_first_name' => $customer['first_name'] ?? '',
			'customer_email'      => $customer['email'] ?? '',
			'customer_phone'      => $customer['phone'] ?? '',
			'service_name'        => $service['name'] ?? '',
			'service_duration'    => $service ? $service['duration_minutes'] . ' ' . __( 'minutes', 'bookingistic' ) : '',
			'service_price'       => $service ? self::format_price( (float) $service['price'] ) : '',
			'staff_name'          => '',
			'staff_email'         => '',
			'booking_date'        => $date_str,
			'booking_time'        => $time_str,
			'booking_timezone'    => $tz_str,
			'booking_status'      => $booking['status'] ?? '',
			'booking_id'          => $booking['id'] ?? '',
			'business_name'       => $settings['business_name'] ?? get_bloginfo( 'name' ),
			'business_email'      => $settings['business_email'] ?? get_option( 'admin_email' ),
			'business_phone'      => $settings['business_phone'] ?? '',
			'business_address'    => $settings['business_address'] ?? '',
			'cancel_link'         => $booking['cancel_token'] ? Token::cancel_url( (int) $booking['id'], $booking['cancel_token'] ) : '',
			'reschedule_link'     => $booking['reschedule_token'] ? Token::reschedule_url( (int) $booking['id'], $booking['reschedule_token'] ) : '',
			'payment_link'        => '',
			'meeting_link'        => '',
			'website_url'         => home_url( '/' ),
		];
	}

	/**
	 * Replace {var} tokens in a subject string with plain text.
	 */
	public static function render_subject( string $template, array $vars ): string {
		return preg_replace_callback(
			'/\{([a-z_]+)\}/i',
			static function ( $m ) use ( $vars ) {
				return isset( $vars[ $m[1] ] ) ? wp_strip_all_tags( (string) $vars[ $m[1] ] ) : '';
			},
			$template
		);
	}

	/**
	 * Replace {var} tokens in an HTML body. Values are esc_html-encoded for safety;
	 * the rest of the body is treated as HTML.
	 */
	public static function render_body( string $template, array $vars ): string {
		$body = preg_replace_callback(
			'/\{([a-z_]+)\}/i',
			static function ( $m ) use ( $vars ) {
				$value = $vars[ $m[1] ] ?? '';
				if ( in_array( $m[1], [ 'cancel_link', 'reschedule_link', 'payment_link', 'meeting_link', 'website_url' ], true ) ) {
					return esc_url( (string) $value );
				}
				return esc_html( (string) $value );
			},
			wp_kses_post( $template )
		);

		return self::wrap_html( $body, $vars );
	}

	private static function wrap_html( string $body_html, array $vars ): string {
		$settings  = get_option( 'bookingistic_settings', [] );
		$brand     = $settings['business_name'] ?? get_bloginfo( 'name' );
		$logo      = $settings['logo_url'] ?? '';
		$logo_html = $logo ? '<img src="' . esc_url( $logo ) . '" alt="' . esc_attr( $brand ) . '" style="max-width:160px;height:auto;margin-bottom:24px">' : '';
		$footer    = Branding_Manager::footer_html();

		return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html( $brand ) . '</title></head>'
			. '<body style="margin:0;padding:0;background:#0B0E16;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif">'
			. '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#0B0E16;padding:32px 16px"><tr><td align="center">'
			. '<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;background:#121724;border-radius:16px;padding:32px;color:#F5F7FF;text-align:left">'
			. '<tr><td>' . $logo_html . $body_html . $footer . '</td></tr>'
			. '</table></td></tr></table></body></html>';
	}

	private static function format_price( float $price ): string {
		if ( $price <= 0 ) {
			return __( 'Free', 'bookingistic' );
		}
		return sprintf( '%0.2f', $price );
	}
}
