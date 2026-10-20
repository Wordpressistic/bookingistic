<?php
/**
 * Payment link token: expiry, tamper, and format checks.
 *
 * @package Bookingistic
 */

use Bookingistic\Helpers\Token;

require_once __DIR__ . '/../includes/Helpers/Token.php';

// Fake the clock by validating against generated tokens directly.
$id = 42;
$tok = Token::for_payment( $id );
t_check( 'token has expiry-hex + 64-hex mac format', (bool) preg_match( '/^[a-f0-9]+-[a-f0-9]{64}$/', $tok ) );

t_check( 'valid token verifies', Token::payment_token_valid( $id, $tok ) );
t_check( 'same id + same token verifies again (derived, no DB)', Token::payment_token_valid( $id, $tok ) );
t_check( 'token is bound to booking id', ! Token::payment_token_valid( $id + 1, $tok ) );
t_check( 'empty token rejected', ! Token::payment_token_valid( $id, '' ) );
t_check( 'garbage token rejected', ! Token::payment_token_valid( $id, 'deadbeef' ) );
t_check( 'legacy plain-HMAC token (pre-2.5) rejected', ! Token::payment_token_valid( $id, str_repeat( 'a', 64 ) ) );

// Tamper: flip one hex char of the mac.
$bad = $tok;
$last = substr( $bad, -1 );
$last = $last === '0' ? '1' : '0';
t_check( 'tampered mac rejected', ! Token::payment_token_valid( $id, substr( $bad, 0, -1 ) . $last ) );

// Tamper: move the expiry forward without re-signing.
[ $exp_hex, $mac ] = explode( '-', $tok, 2 );
$forged = dechex( hexdec( $exp_hex ) + 100000 ) . '-' . $mac;
t_check( 'expiry moved without re-signing rejected', ! Token::payment_token_valid( $id, $forged ) );

// An already-expired but correctly signed token must fail: sign with a past
// expiry through the same HMAC construction and check validation logic.
$ref  = new ReflectionClass( Token::class );
$sign = $ref->getMethod( 'sign_payment' );
$sign->setAccessible( true );
$past = $sign->invoke( null, $id, time() - 3600 );
t_check( 'correctly-signed but expired token rejected', ! Token::payment_token_valid( $id, $past ) );
$future = $sign->invoke( null, $id, time() + 3600 );
t_check( 'short-dated future token accepted', Token::payment_token_valid( $id, $future ) );
