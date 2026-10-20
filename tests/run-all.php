<?php
/**
 * Standalone test runner — no WordPress install required.
 * Usage: php tests/run-all.php
 *
 * @package Bookingistic
 */

require __DIR__ . '/bootstrap.php';

foreach ( glob( __DIR__ . '/test-*.php' ) as $test_file ) {
	echo '== ' . basename( $test_file ) . " ==\n";
	require $test_file;
}

t_summary();
