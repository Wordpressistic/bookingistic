<?php
/**
 * Backward compatibility with Bookingistic v1.
 *
 * v1 stored leads as `wpistic_booking` CPT, exposed `POST /wpistic/v1/bookingistic/request`,
 * and had a non-JS admin_post fallback (action=wpistic_bookingistic). All three remain
 * functional here so existing themes/forms keep working while the new booking engine takes over.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Core;

use Bookingistic\Booking\Booking_Manager;
use Bookingistic\Database\Service_Repository;
use Bookingistic\Helpers\Security;

defined( 'ABSPATH' ) || exit;

class Backward_Compatibility {

	public const LEGACY_CPT = 'wpistic_booking';

	public static function register_cpt(): void {
		register_post_type(
			self::LEGACY_CPT,
			[
				'labels'          => [
					'name'          => __( 'Legacy Bookings (v1)', 'bookingistic' ),
					'singular_name' => __( 'Legacy Booking', 'bookingistic' ),
				],
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'bookingistic',
				'show_in_rest'    => false,
				'capability_type' => 'post',
				'capabilities'    => [ 'create_posts' => 'do_not_allow' ],
				'map_meta_cap'    => true,
				'supports'        => [ 'title', 'custom-fields' ],
				'menu_icon'       => 'dashicons-calendar-alt',
			]
		);
	}

	public static function register_legacy_route(): void {
		register_rest_route(
			'wpistic/v1',
			'/bookingistic/request',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ self::class, 'handle_legacy_rest' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'name'    => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
					'email'   => [ 'required' => true, 'sanitize_callback' => 'sanitize_email' ],
					'website' => [ 'required' => false, 'sanitize_callback' => 'esc_url_raw' ],
				],
			]
		);
	}

	public static function handle_legacy_rest( \WP_REST_Request $request ) {
		if ( ! empty( $request->get_param( 'website2' ) ) ) {
			return rest_ensure_response( [ 'ok' => true, 'silent' => true ] );
		}
		if ( ! Security::rate_limit_ok( 'legacy_rest' ) ) {
			return new \WP_Error( 'rate_limited', __( 'Too many requests. Try again in an hour.', 'bookingistic' ), [ 'status' => 429 ] );
		}

		$payload = self::collect_legacy_payload( $request->get_params() );
		if ( ! is_email( $payload['email'] ) ) {
			return new \WP_Error( 'bad_email', __( 'A valid email is required.', 'bookingistic' ), [ 'status' => 422 ] );
		}

		$result = self::create_legacy_lead( $payload, 'rest' );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_ensure_response(
			[
				'ok'      => true,
				'id'      => $result['booking_id'],
				'message' => __( 'Got it. We will reply within 1 business day with available slots.', 'bookingistic' ),
			]
		);
	}

	public static function handle_legacy_form(): void {
		if ( ! isset( $_POST['wpistic_bookingistic_nonce'] ) ||
			! wp_verify_nonce( wp_unslash( $_POST['wpistic_bookingistic_nonce'] ), 'wpistic_bookingistic' ) ) {
			self::redirect_back( 'error' );
		}
		if ( ! empty( $_POST['website2'] ) ) {
			self::redirect_back( 'ok' );
		}
		if ( ! Security::rate_limit_ok( 'legacy_form' ) ) {
			self::redirect_back( 'error' );
		}

		$payload = self::collect_legacy_payload( wp_unslash( $_POST ) );
		if ( ! is_email( $payload['email'] ) || ! $payload['name'] ) {
			self::redirect_back( 'invalid' );
		}

		$result = self::create_legacy_lead( $payload, 'form' );
		self::redirect_back( is_wp_error( $result ) ? 'error' : 'ok' );
	}

	private static function collect_legacy_payload( array $raw ): array {
		return [
			'name'     => sanitize_text_field( $raw['name'] ?? '' ),
			'email'    => sanitize_email( $raw['email'] ?? '' ),
			'website'  => esc_url_raw( $raw['website'] ?? '' ),
			'industry' => sanitize_text_field( $raw['industry'] ?? '' ),
			'tz'       => sanitize_text_field( $raw['tz'] ?? '' ),
			'goal'     => sanitize_textarea_field( $raw['goal'] ?? '' ),
		];
	}

	/**
	 * Convert a legacy strategy-call lead into a booking on the default service.
	 * Falls back to a pending lead-only booking with no specific slot.
	 */
	private static function create_legacy_lead( array $payload, string $source ) {
		$service = Service_Repository::find_by_slug( 'strategy-call' );
		if ( ! $service ) {
			return new \WP_Error( 'no_default_service', __( 'Default service missing.', 'bookingistic' ), [ 'status' => 500 ] );
		}

		$result = Booking_Manager::create_lead_booking(
			[
				'service_id' => (int) $service['id'],
				'customer'   => [
					'first_name' => $payload['name'],
					'email'      => $payload['email'],
					'phone'      => '',
					'company'    => $payload['website'],
					'timezone'   => $payload['tz'],
					'notes'      => $payload['goal'],
				],
				'notes'      => trim(
					( $payload['industry'] ? 'Industry: ' . $payload['industry'] . "\n" : '' ) .
					( $payload['goal'] ? 'Goal: ' . $payload['goal'] : '' )
				),
				'source'     => 'legacy_' . $source,
			]
		);

		return $result;
	}

	private static function redirect_back( string $state ): void {
		$target = wp_get_referer() ?: home_url( '/' );
		wp_safe_redirect( add_query_arg( 'booking', $state, $target ) );
		exit;
	}
}
