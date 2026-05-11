<?php
/**
 * Input sanitization helpers.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Helpers;

defined( 'ABSPATH' ) || exit;

class Sanitizer {

	public static function phone( string $value ): string {
		$value = wp_strip_all_tags( $value );
		$value = preg_replace( '/[^0-9 +()\-]/', '', $value );
		return trim( (string) $value );
	}

	public static function timezone( string $value ): string {
		$value = sanitize_text_field( $value );
		if ( ! $value ) {
			return '';
		}
		try {
			new \DateTimeZone( $value );
			return $value;
		} catch ( \Throwable $e ) {
			return '';
		}
	}

	public static function iso_date( string $value ): string {
		$value = sanitize_text_field( $value );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return '';
		}
		return $value;
	}

	public static function hhmm( string $value ): string {
		$value = sanitize_text_field( $value );
		if ( ! preg_match( '/^([0-1]\d|2[0-3]):[0-5]\d$/', $value ) ) {
			return '';
		}
		return $value;
	}
}
