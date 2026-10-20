<?php
/**
 * Crypto helper: secretbox round-trip + legacy fallback.
 *
 * @package Bookingistic
 */

use Bookingistic\Helpers\Crypto;

require_once __DIR__ . '/../includes/Helpers/Crypto.php';

if ( ! function_exists( 'sodium_crypto_secretbox' ) ) {
	echo "  SKIP: sodium not available in this PHP build\n";
	return;
}

$secret = json_encode( [ 'refresh_token' => '1//0abc', 'access_token' => 'ya29.xyz', 'expires_at' => 123456 ] );

$enc = Crypto::encrypt( $secret );
t_check( 'ciphertext carries enc1: prefix', 0 === strpos( $enc, 'enc1:' ) );
t_check( 'ciphertext differs from plaintext', $enc !== $secret );
t_check( 'round-trip decrypts to original', Crypto::decrypt( $enc ) === $secret );
t_check( 'two encryptions differ (random nonce)', Crypto::encrypt( $secret ) !== $enc );

// Tamper: flip a byte in the ciphertext body — secretbox must reject.
$raw  = substr( $enc, strlen( 'enc1:' ) );
$pos  = (int) ( strlen( $raw ) / 2 );
$flip = $raw;
$c    = $raw[ $pos ];
$flip[ $pos ] = $c === 'A' ? 'B' : 'A';
t_check( 'tampered ciphertext rejected (null)', null === Crypto::decrypt( 'enc1:' . $flip ) );

// Legacy storage formats still readable.
t_check( 'legacy plain base64 decrypts', Crypto::decrypt( base64_encode( $secret ) ) === $secret );
t_check( 'undecodable garbage returns null', null === Crypto::decrypt( '!!!not-base64!!!' ) );
