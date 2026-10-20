<?php
/**
 * Signed-link token helpers for cancel / reschedule URLs.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Helpers;

defined( 'ABSPATH' ) || exit;

class Token {

	private const TTL_DAYS   = 90;
	private const PAY_TTL_DAYS = 90;

	public static function cancel_url( int $booking_id, string $token ): string {
		return add_query_arg(
			[
				'bookingistic_action' => 'cancel',
				'id'                  => $booking_id,
				't'                   => $token,
			],
			home_url( '/' )
		);
	}

	/**
	 * Expiring HMAC token for payment links. Format:
	 *   <expiry-hex>-<hmac_sha256( 'bookingistic-pay-<id>|<expiry>', salt )>
	 * Derived (not stored), so nothing persists in the DB, but a leaked link
	 * stops working after PAY_TTL_DAYS. Fresh links are issued on every
	 * payment_url() call, so live bookings always get a valid one.
	 */
	public static function for_payment( int $booking_id ): string {
		$expires = time() + self::PAY_TTL_DAYS * DAY_IN_SECONDS;
		return self::sign_payment( $booking_id, $expires );
	}

	/**
	 * Validate a payment link token: recomputes the HMAC over the embedded
	 * expiry and rejects expired or malformed tokens. Pre-2.5 links (plain
	 * HMACs without an expiry) no longer verify — any newly generated
	 * email/shortcode link works immediately.
	 */
	public static function payment_token_valid( int $booking_id, string $given ): bool {
		$given = trim( $given );
		if ( '' === $given || strlen( $given ) > 100 || ! preg_match( '/^[a-f0-9]+-[a-f0-9]{64}$/', $given ) ) {
			return false;
		}
		[ $expires_hex, $mac ] = explode( '-', $given, 2 );
		$expires = (int) hexdec( $expires_hex );
		if ( $expires < time() ) {
			return false;
		}
		return hash_equals( self::sign_payment( $booking_id, $expires ), $given );
	}

	private static function sign_payment( int $booking_id, int $expires ): string {
		return dechex( $expires ) . '-' . hash_hmac( 'sha256', 'bookingistic-pay-' . (int) $booking_id . '|' . $expires, wp_salt( 'auth' ) );
	}

	public static function payment_url( int $booking_id ): string {
		return add_query_arg(
			[
				'bookingistic_action' => 'pay',
				'id'                  => $booking_id,
				't'                   => self::for_payment( $booking_id ),
			],
			home_url( '/' )
		);
	}

	public static function reschedule_url( int $booking_id, string $token ): string {
		return add_query_arg(
			[
				'bookingistic_action' => 'reschedule',
				'id'                  => $booking_id,
				't'                   => $token,
			],
			home_url( '/' )
		);
	}

	public static function is_expired( string $created_at ): bool {
		$created = strtotime( $created_at . ' UTC' );
		if ( ! $created ) {
			return true;
		}
		return ( time() - $created ) > ( self::TTL_DAYS * DAY_IN_SECONDS );
	}

	public static function consume_equals( string $expected, string $given ): bool {
		if ( ! $expected || ! $given ) {
			return false;
		}
		return hash_equals( $expected, $given );
	}
}
