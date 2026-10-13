<?php
/**
 * Signed-link token helpers for cancel / reschedule URLs.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Helpers;

defined( 'ABSPATH' ) || exit;

class Token {

	private const TTL_DAYS = 90;

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
	 * Deterministic HMAC token for payment links. Unlike cancel/reschedule
	 * tokens it is derived (not stored), so legacy bookings get valid pay
	 * links too and nothing new persists in the DB.
	 */
	public static function for_payment( int $booking_id ): string {
		return hash_hmac( 'sha256', 'bookingistic-pay-' . (int) $booking_id, wp_salt( 'auth' ) );
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
