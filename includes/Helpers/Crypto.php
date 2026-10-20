<?php
/**
 * At-rest encryption helper for sensitive stored values (OAuth tokens).
 *
 * Authenticated secretbox encryption keyed from the WP auth salt. Values are
 * versioned with an `enc1:` prefix; legacy plain-base64 values still decrypt
 * (transparent migration — they re-encrypt on the next save).
 *
 * @package Bookingistic
 */

namespace Bookingistic\Helpers;

defined( 'ABSPATH' ) || exit;

class Crypto {

	private const PREFIX = 'enc1:';

	public static function encrypt( string $plain ): string {
		if ( ! function_exists( 'sodium_crypto_secretbox' ) ) {
			// Sodium is bundled with PHP 7.2+, but never fail hard on a
			// stripped build — fall back to the legacy encoding.
			return base64_encode( $plain ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}
		$nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$cipher = sodium_crypto_secretbox( $plain, $nonce, self::key() );
		return self::PREFIX . base64_encode( $nonce . $cipher ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt an `enc1:` value, or pass through legacy plain-base64 content.
	 * Returns null when the value cannot be decoded at all.
	 */
	public static function decrypt( string $stored ): ?string {
		if ( 0 === strpos( $stored, self::PREFIX ) ) {
			if ( ! function_exists( 'sodium_crypto_secretbox' ) ) {
				return null;
			}
			$raw = base64_decode( substr( $stored, strlen( self::PREFIX ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			if ( false === $raw || strlen( $raw ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
				return null;
			}
			$nonce  = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$cipher = substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$plain  = sodium_crypto_secretbox_open( $cipher, $nonce, self::key() );
			return false === $plain ? null : (string) $plain;
		}

		// Legacy value: plain base64 (may or may not be JSON when decoded).
		$decoded = base64_decode( $stored, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		return false === $decoded ? null : $decoded;
	}

	private static function key(): string {
		return substr(
			hash( 'sha256', wp_salt( 'auth' ) . '|bookingistic-crypto', true ),
			0,
			SODIUM_CRYPTO_SECRETBOX_KEYBYTES
		);
	}
}
