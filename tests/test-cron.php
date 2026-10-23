<?php
/**
 * Cron recurrences: the fifteen_minutes interval must resolve before
 * scheduling (fresh-activation order — see Installer::install()).
 *
 * @package Bookingistic
 */

use Bookingistic\Core\Cron;

require_once __DIR__ . '/../includes/Core/Cron.php';

// An empty schedule registry (as wp_get_schedules() presents it before the
// plugin's filter runs) gains the custom interval.
$got = Cron::register_schedules( [] );
t_check( 'fifteen_minutes interval registered on empty registry', isset( $got['fifteen_minutes'] ) );
t_check( 'fifteen_minutes interval is 900 seconds', ( $got['fifteen_minutes']['interval'] ?? 0 ) === 15 * MINUTE_IN_SECONDS );
t_check( 'fifteen_minutes has a display label', ! empty( $got['fifteen_minutes']['display'] ) );

// Core schedules are preserved untouched.
$core = [ 'hourly' => [ 'interval' => HOUR_IN_SECONDS, 'display' => 'Once Hourly' ] ];
$got  = Cron::register_schedules( $core );
t_check( 'hourly schedule preserved', ( $got['hourly']['interval'] ?? 0 ) === HOUR_IN_SECONDS );
t_check( 'fifteen_minutes added alongside core schedules', isset( $got['fifteen_minutes'], $got['hourly'] ) );

// Idempotent: an existing fifteen_minutes entry (e.g. another plugin's) wins.
$existing = [ 'fifteen_minutes' => [ 'interval' => 12345, 'display' => 'Foreign' ] ];
$got      = Cron::register_schedules( $existing );
t_check( 'pre-existing fifteen_minutes not clobbered', ( $got['fifteen_minutes']['interval'] ?? 0 ) === 12345 );
