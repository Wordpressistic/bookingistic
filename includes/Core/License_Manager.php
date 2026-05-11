<?php
/**
 * License manager for the premium tier.
 *
 * Site owners create an account at https://www.wordpressistic.com/account
 * and paste the license key into Bookingistic → License. We POST the key
 * to the activation endpoint, store the returned token + expiry, and
 * re-validate daily via cron.
 *
 * The activation endpoint URL can be overridden with the
 * `bookingistic_license_endpoint` filter for self-hosted or staging
 * deployments; the default points at wordpressistic.com.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Core;

defined( 'ABSPATH' ) || exit;

class License_Manager {

	public const OPTION             = 'bookingistic_license';
	public const ACCOUNT_URL        = 'https://www.wordpressistic.com/account/';
	public const DEFAULT_ENDPOINT   = 'https://www.wordpressistic.com/wp-json/wpistic/v1/license';
	public const CRON_HOOK          = 'bookingistic_license_validate';

	public static function register(): void {
		add_action( self::CRON_HOOK, [ self::class, 'validate_remote' ] );
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	public static function is_active(): bool {
		$state = self::state();
		if ( empty( $state['status'] ) || $state['status'] !== 'active' ) {
			return false;
		}
		if ( ! empty( $state['expires_at'] ) && strtotime( $state['expires_at'] . ' UTC' ) < time() ) {
			return false;
		}
		/** Premium add-ons may force-disable. */
		return (bool) apply_filters( 'bookingistic_license_active', true, $state );
	}

	/**
	 * @return array{ key:string, status:string, plan:string, expires_at:string, last_checked:string, message:string }
	 */
	public static function state(): array {
		$default = [
			'key'          => '',
			'status'       => 'inactive',
			'plan'         => '',
			'expires_at'   => '',
			'last_checked' => '',
			'message'      => '',
		];
		$current = get_option( self::OPTION, [] );
		return array_merge( $default, is_array( $current ) ? $current : [] );
	}

	public static function activate( string $license_key ): array {
		$license_key = trim( $license_key );
		if ( ! $license_key ) {
			return [ 'ok' => false, 'message' => __( 'License key is required.', 'bookingistic' ) ];
		}

		$endpoint = (string) apply_filters( 'bookingistic_license_endpoint', self::DEFAULT_ENDPOINT );
		$res = wp_remote_post(
			trailingslashit( $endpoint ) . 'activate',
			[
				'timeout' => 12,
				'headers' => [
					'Content-Type' => 'application/json',
					'User-Agent'   => 'Bookingistic/' . BOOKINGISTIC_VERSION,
				],
				'body'    => wp_json_encode(
					[
						'key'      => $license_key,
						'site_url' => home_url( '/' ),
						'product'  => 'bookingistic',
						'version'  => BOOKINGISTIC_VERSION,
					]
				),
			]
		);

		if ( is_wp_error( $res ) ) {
			return self::store_failure( $license_key, $res->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$data = json_decode( (string) wp_remote_retrieve_body( $res ), true );

		if ( $code !== 200 || ! is_array( $data ) || empty( $data['status'] ) ) {
			$msg = is_array( $data ) && ! empty( $data['message'] )
				? (string) $data['message']
				: sprintf( __( 'Activation failed (HTTP %d).', 'bookingistic' ), $code );
			return self::store_failure( $license_key, $msg );
		}

		update_option(
			self::OPTION,
			[
				'key'          => $license_key,
				'status'       => sanitize_key( $data['status'] ),
				'plan'         => sanitize_text_field( (string) ( $data['plan'] ?? '' ) ),
				'expires_at'   => sanitize_text_field( (string) ( $data['expires_at'] ?? '' ) ),
				'last_checked' => current_time( 'mysql', true ),
				'message'      => sanitize_text_field( (string) ( $data['message'] ?? '' ) ),
			]
		);

		return [
			'ok'      => self::is_active(),
			'message' => $data['message'] ?? __( 'License activated.', 'bookingistic' ),
		];
	}

	public static function deactivate(): array {
		$state = self::state();
		if ( $state['key'] ) {
			$endpoint = (string) apply_filters( 'bookingistic_license_endpoint', self::DEFAULT_ENDPOINT );
			wp_remote_post(
				trailingslashit( $endpoint ) . 'deactivate',
				[
					'timeout' => 8,
					'body'    => wp_json_encode( [ 'key' => $state['key'], 'site_url' => home_url( '/' ) ] ),
					'headers' => [ 'Content-Type' => 'application/json' ],
				]
			);
		}
		delete_option( self::OPTION );
		return [ 'ok' => true, 'message' => __( 'License removed from this site.', 'bookingistic' ) ];
	}

	public static function validate_remote(): void {
		$state = self::state();
		if ( ! $state['key'] ) {
			return;
		}
		self::activate( $state['key'] );
	}

	private static function store_failure( string $key, string $message ): array {
		update_option(
			self::OPTION,
			[
				'key'          => $key,
				'status'       => 'invalid',
				'plan'         => '',
				'expires_at'   => '',
				'last_checked' => current_time( 'mysql', true ),
				'message'      => $message,
			]
		);
		return [ 'ok' => false, 'message' => $message ];
	}
}
