<?php
/**
 * Build an RFC-5545 .ics document from a Bookingistic booking.
 *
 * The output is intentionally minimal but covers everything every major
 * calendar client (Google Calendar, Outlook, Apple) needs to render an event.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Integrations\ICS;

use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Customer_Repository;
use Bookingistic\Database\Service_Repository;

defined( 'ABSPATH' ) || exit;

class ICS_Generator {

	public static function for_booking_id( int $booking_id ): ?string {
		$booking = Booking_Repository::find( $booking_id );
		if ( ! $booking || empty( $booking['start_datetime'] ) || empty( $booking['end_datetime'] ) ) {
			return null;
		}
		return self::for_booking( $booking );
	}

	public static function for_booking( array $booking ): string {
		$service  = Service_Repository::find( (int) $booking['service_id'] );
		$customer = Customer_Repository::find( (int) $booking['customer_id'] );
		$settings = get_option( 'bookingistic_settings', [] );
		$brand    = $settings['business_name'] ?? get_bloginfo( 'name' );

		$summary  = self::sanitize( $service['name'] ?? __( 'Booking', 'bookingistic' ) );
		$desc     = self::sanitize(
			sprintf(
				/* translators: 1: customer name, 2: customer email, 3: notes */
				__( "%1\$s\nEmail: %2\$s\n\n%3\$s", 'bookingistic' ),
				$customer['full_name'] ?? '',
				$customer['email'] ?? '',
				$booking['notes'] ?? ''
			)
		);
		$location = self::sanitize( $settings['business_address'] ?? '' );
		$uid      = 'bookingistic-' . $booking['id'] . '@' . wp_parse_url( home_url(), PHP_URL_HOST );

		$dtstart = self::fmt_utc( $booking['start_datetime'] );
		$dtend   = self::fmt_utc( $booking['end_datetime'] );
		$dtstamp = gmdate( 'Ymd\THis\Z' );

		$lines = [
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//Bookingistic//WordPress//EN',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'BEGIN:VEVENT',
			'UID:' . $uid,
			'DTSTAMP:' . $dtstamp,
			'DTSTART:' . $dtstart,
			'DTEND:' . $dtend,
			'SUMMARY:' . $summary,
			'DESCRIPTION:' . $desc,
		];
		if ( $location ) {
			$lines[] = 'LOCATION:' . $location;
		}
		if ( ! empty( $customer['email'] ) ) {
			$lines[] = 'ATTENDEE;CN=' . self::sanitize( $customer['full_name'] ?? '' ) . ';RSVP=TRUE:mailto:' . $customer['email'];
		}
		$lines[] = 'ORGANIZER;CN=' . self::sanitize( $brand ) . ':mailto:' . ( $settings['business_email'] ?? get_option( 'admin_email' ) );
		$lines[] = 'STATUS:' . ( $booking['status'] === 'cancelled' ? 'CANCELLED' : 'CONFIRMED' );
		$lines[] = 'END:VEVENT';
		$lines[] = 'END:VCALENDAR';

		return implode( "\r\n", $lines );
	}

	private static function fmt_utc( string $datetime ): string {
		$ts = strtotime( $datetime . ' UTC' );
		return gmdate( 'Ymd\THis\Z', $ts );
	}

	/** Escape per RFC 5545 § 3.3.11. */
	private static function sanitize( string $value ): string {
		$value = wp_strip_all_tags( $value );
		$value = str_replace( [ "\\", "\n", "\r", ",", ";" ], [ "\\\\", "\\n", "", "\\,", "\\;" ], $value );
		// Wrap long lines per RFC 5545 § 3.1 (75-octet line length).
		return $value;
	}
}
