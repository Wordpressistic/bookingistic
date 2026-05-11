<?php
/**
 * Security helpers (rate limiting, honeypot, signed tokens).
 *
 * @package Bookingistic
 */

namespace Bookingistic\Helpers;

defined( 'ABSPATH' ) || exit;

class Security {

	/**
	 * Per-IP rate limit. Returns true if request is allowed.
	 */
	public static function rate_limit_ok( string $bucket, int $max = 5, int $window = HOUR_IN_SECONDS ): bool {
		$ip  = self::client_ip();
		$key = 'bookingistic_rl_' . $bucket . '_' . md5( $ip );
		$cnt = (int) get_transient( $key );
		if ( $cnt >= $max ) {
			return false;
		}
		set_transient( $key, $cnt + 1, $window );
		return true;
	}

	public static function client_ip(): string {
		$candidates = [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ];
		foreach ( $candidates as $k ) {
			if ( ! empty( $_SERVER[ $k ] ) ) {
				$ip = explode( ',', (string) $_SERVER[ $k ] )[0];
				$ip = trim( $ip );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}
		return '0.0.0.0';
	}

	public static function honeypot_filled( array $params ): bool {
		return ! empty( $params['website2'] ) || ! empty( $params['_hp_field'] );
	}
}
