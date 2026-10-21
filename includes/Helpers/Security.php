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
		// Direct client address is the only trustworthy default. Forwarding
		// headers (CF-Connecting-IP / X-Forwarded-For) are client-controllable
		// unless the site actually sits behind a trusted proxy, so they are
		// only honored when the site explicitly opts in.
		$trust_proxy = apply_filters(
			'bookingistic_trust_proxy_headers',
			(bool) ( defined( 'BOOKINGISTIC_TRUST_PROXY_HEADERS' ) && BOOKINGISTIC_TRUST_PROXY_HEADERS )
		);

		$candidates = $trust_proxy
			? [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ]
			: [ 'REMOTE_ADDR' ];

		foreach ( $candidates as $k ) {
			if ( ! empty( $_SERVER[ $k ] ) ) {
				$raw = sanitize_text_field( wp_unslash( (string) $_SERVER[ $k ] ) );
				$ip  = trim( explode( ',', $raw )[0] );
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
