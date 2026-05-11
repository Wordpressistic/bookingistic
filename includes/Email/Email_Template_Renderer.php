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

	public const VARIABLE_KEYS = [
		'customer_name', 'customer_first_name', 'customer_email', 'customer_phone',
		'service_name', 'service_duration', 'service_price',
		'staff_name', 'staff_email',
		'booking_date', 'booking_time', 'booking_timezone', 'booking_status', 'booking_id',
		'business_name', 'business_email', 'business_phone', 'business_address',
		'cancel_link', 'reschedule_link', 'payment_link', 'meeting_link', 'website_url',
	];

	/**
	 * Build a fake variable map (useful for test sends / preview).
	 */
	public static function sample_variables(): array {
		$settings = get_option( 'bookingistic_settings', [] );
		return [
			'customer_name'       => 'Jane Sample',
			'customer_first_name' => 'Jane',
			'customer_email'      => 'jane@example.com',
			'customer_phone'      => '+1 555 0100',
			'service_name'        => __( 'Strategy Call', 'bookingistic' ),
			'service_duration'    => '30 ' . __( 'minutes', 'bookingistic' ),
			'service_price'       => __( 'Free', 'bookingistic' ),
			'staff_name'          => '',
			'staff_email'         => '',
			'booking_date'        => date_i18n( get_option( 'date_format', 'F j, Y' ), strtotime( '+2 days' ) ),
			'booking_time'        => '14:00',
			'booking_timezone'    => $settings['timezone'] ?? wp_timezone_string(),
			'booking_status'      => 'confirmed',
			'booking_id'          => '12345',
			'business_name'       => $settings['business_name'] ?? get_bloginfo( 'name' ),
			'business_email'      => $settings['business_email'] ?? get_option( 'admin_email' ),
			'business_phone'      => $settings['business_phone'] ?? '',
			'business_address'    => $settings['business_address'] ?? '',
			'cancel_link'         => home_url( '/?bookingistic_action=cancel&id=0&t=sample' ),
			'reschedule_link'     => home_url( '/?bookingistic_action=reschedule&id=0&t=sample' ),
			'payment_link'        => '',
			'meeting_link'        => '',
			'website_url'         => home_url( '/' ),
		];
	}

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

		$vars = [
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

		/**
		 * Filter the template-variable map for a booking.
		 *
		 * @param array       $vars     The variable map.
		 * @param array       $booking  The booking row.
		 * @param array|null  $customer The customer row.
		 * @param array|null  $service  The service row.
		 */
		return apply_filters( 'bookingistic_booking_email_vars', $vars, $booking, $customer, $service );
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
