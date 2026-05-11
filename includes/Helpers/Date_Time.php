<?php
/**
 * Date/time helpers.
 *
 * Internally everything is stored as UTC. The display timezone comes from
 * either the customer (when provided) or the site / settings timezone.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Helpers;

defined( 'ABSPATH' ) || exit;

class Date_Time {

	public static function site_timezone(): \DateTimeZone {
		$settings = get_option( 'bookingistic_settings', [] );
		$tz       = $settings['timezone'] ?? wp_timezone_string();
		try {
			return new \DateTimeZone( $tz );
		} catch ( \Throwable $e ) {
			return new \DateTimeZone( 'UTC' );
		}
	}

	public static function utc(): \DateTimeZone {
		return new \DateTimeZone( 'UTC' );
	}

	public static function to_utc_string( string $local_datetime, string $timezone ): string {
		try {
			$tz = new \DateTimeZone( $timezone );
		} catch ( \Throwable $e ) {
			$tz = self::site_timezone();
		}
		$dt = new \DateTimeImmutable( $local_datetime, $tz );
		return $dt->setTimezone( self::utc() )->format( 'Y-m-d H:i:s' );
	}

	public static function from_utc( string $utc_datetime, ?\DateTimeZone $tz = null ): \DateTimeImmutable {
		$dt = new \DateTimeImmutable( $utc_datetime, self::utc() );
		return $dt->setTimezone( $tz ?: self::site_timezone() );
	}

	public static function format_date( string $utc_datetime, ?\DateTimeZone $tz = null ): string {
		return self::from_utc( $utc_datetime, $tz )->format( get_option( 'date_format', 'F j, Y' ) );
	}

	public static function format_time( string $utc_datetime, ?\DateTimeZone $tz = null ): string {
		return self::from_utc( $utc_datetime, $tz )->format( get_option( 'time_format', 'g:i a' ) );
	}

	public static function format_pretty( string $utc_datetime, ?\DateTimeZone $tz = null ): string {
		return self::format_date( $utc_datetime, $tz ) . ' · ' . self::format_time( $utc_datetime, $tz );
	}
}
