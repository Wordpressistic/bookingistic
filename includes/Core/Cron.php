<?php
/**
 * Cron schedule helpers.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Core;

defined( 'ABSPATH' ) || exit;

class Cron {

	/**
	 * Register the plugin's custom recurrences on cron_schedules.
	 *
	 * Also invoked directly from Installer::install(): on a fresh activation
	 * the activation hook fires after plugins_loaded, so Plugin::boot() has
	 * not registered the interval yet and wp_schedule_event() would fail
	 * silently on the unregistered 'fifteen_minutes' recurrence.
	 */
	public static function register_schedules( array $schedules ): array {
		if ( ! isset( $schedules['fifteen_minutes'] ) ) {
			$schedules['fifteen_minutes'] = [
				'interval' => 15 * MINUTE_IN_SECONDS,
				'display'  => __( 'Every 15 minutes', 'bookingistic' ),
			];
		}
		return $schedules;
	}
}
