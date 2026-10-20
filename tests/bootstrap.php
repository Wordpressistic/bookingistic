<?php
/**
 * Minimal WordPress shim for the standalone test runner (tests/run-all.php).
 * Defines just enough surface for the helper classes under test.
 *
 * @package Bookingistic
 */

error_reporting( E_ALL );
define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'BOOKINGISTIC_VERSION', 'test' );

$GLOBALS['__test_failures'] = 0;
$GLOBALS['__test_asserts']  = 0;

// --- WP function shims -------------------------------------------------------
function wp_salt( $scheme = 'auth' ) {
	return 'test-salt-' . $scheme . '-0123456789abcdef';
}
function __( $text, $domain = 'default' ) {
	return $text;
}
function esc_html__( $text, $domain = 'default' ) {
	return $text;
}
function apply_filters( $tag, $value, ...$args ) {
	return $value;
}
function do_action( $tag, ...$args ) {
}
function add_action( ...$args ) {
}
function sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}
function sanitize_text_field( $str ) {
	return trim( preg_replace( '/[\r\n\t ]+/', ' ', (string) $str ) );
}
function absint( $maybe ) {
	return abs( (int) $maybe );
}

// --- tiny assertion helpers ---------------------------------------------------
function t_check( string $label, bool $cond ): void {
	$GLOBALS['__test_asserts']++;
	if ( $cond ) {
		echo "  PASS: {$label}\n";
	} else {
		$GLOBALS['__test_failures']++;
		echo "  FAIL: {$label}\n";
	}
}
function t_summary(): void {
	echo "\n" . ( $GLOBALS['__test_failures'] === 0 ? 'ALL TESTS PASSED' : 'FAILURES: ' . $GLOBALS['__test_failures'] )
		. " ({$GLOBALS['__test_asserts']} assertions)\n";
	exit( $GLOBALS['__test_failures'] === 0 ? 0 : 1 );
}
