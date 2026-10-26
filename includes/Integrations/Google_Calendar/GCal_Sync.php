<?php
/**
 * Google Calendar two-way sync engine (OAuth 2.0, per-staff).
 *
 * Direction 1 (bookings → Google): confirmed bookings are pushed to the
 * connected staff member's calendar as events.
 *
 * Direction 2 (Google → bookings): a 15-min cron reads busy blocks from the
 * connected calendars and hands them to the availability engine via the
 * `bookingistic_external_busy_intervals` filter, so external meetings block
 * booking slots without touching booking rows.
 *
 * Flow: staff member clicks "Connect Google Calendar" (staff editor) →
 * consent screen → Google redirects back to the REST callback → tokens stored
 * encrypted at rest (secretbox via Helpers\Crypto, keyed from the WP auth
 * salt) on the staff row.
 *
 * Setup: create an OAuth client (Web application) in Google Cloud Console and
 * paste its Client ID / Secret into Bookingistic → Integrations → Google
 * Calendar Sync. Redirect URI is shown in the integration panel.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Integrations\Google_Calendar;

use Bookingistic\Database\Cache;
use Bookingistic\Database\Calendar_Event_Repository;
use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Staff_Repository;
use Bookingistic\Helpers\Crypto;
use Bookingistic\Helpers\Date_Time;
use Bookingistic\Integrations\Integration_Base;

defined( 'ABSPATH' ) || exit;

class GCal_Sync {

	const PROVIDER      = 'google_calendar';
	const AUTH_STATE_KEY = 'bookingistic_gcal_state_';

	/* ================= Client credentials ================= */

	/** Read the google_calendar integration settings straight from the option (no registry boot needed). */
	private static function integration_settings(): array {
		$all = (array) get_option( Integration_Base::OPTION, [] );
		return (array) ( $all[ self::PROVIDER ] ?? [] );
	}

	public static function client_id(): string {
		return (string) ( self::integration_settings()['client_id'] ?? '' );
	}

	public static function client_secret(): string {
		return (string) ( self::integration_settings()['client_secret'] ?? '' );
	}

	public static function is_configured(): bool {
		return self::client_id() !== '' && self::client_secret() !== '';
	}

	public static function redirect_uri(): string {
		return rest_url( BOOKINGISTIC_REST_NAMESPACE . '/google/callback' );
	}

	/* ================= Connect (staff → Google) ================= */

	public static function connect_url( int $staff_id ): string {
		$state = wp_generate_password( 24, false );
		set_transient( self::AUTH_STATE_KEY . $state, $staff_id, 15 * MINUTE_IN_SECONDS );

		return add_query_arg(
			[
				'client_id'     => self::client_id(),
				'redirect_uri'  => self::redirect_uri(),
				'response_type' => 'code',
				'scope'         => rawurlencode( 'https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/calendar.readonly' ),
				'access_type'   => 'offline',
				'prompt'        => 'consent',
				'state'         => $state,
			],
			'https://accounts.google.com/o/oauth2/v2/auth'
		);
	}

	/**
	 * Handle the OAuth redirect. Returns [ok => bool, message => string].
	 */
	public static function handle_callback( string $code, string $state ): array {
		$staff_id = (int) get_transient( self::AUTH_STATE_KEY . $state );
		delete_transient( self::AUTH_STATE_KEY . $state );

		if ( ! $staff_id || ! self::is_configured() ) {
			return [ 'ok' => false, 'message' => __( 'Invalid or expired connection state. Please try again.', 'bookingistic' ) ];
		}

		$tokens = self::exchange_code( $code );
		if ( is_wp_error( $tokens ) ) {
			return [ 'ok' => false, 'message' => $tokens->get_error_message() ];
		}

		$calendar_id = self::fetch_primary_calendar_id( $tokens['refresh_token'] ?? '' );
		if ( is_wp_error( $calendar_id ) ) {
			return [ 'ok' => false, 'message' => $calendar_id->get_error_message() ];
		}

		self::store_tokens( $staff_id, $tokens, (string) $calendar_id );

		return [ 'ok' => true, 'message' => __( 'Google Calendar connected.', 'bookingistic' ) ];
	}

	public static function disconnect( int $staff_id ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write to the plugin's own custom table; its cache group is invalidated right after.
		$wpdb->update(
			Staff_Repository::table(),
			[
				'gcal_connected' => 0,
				'gcal_id'        => null,
				'gcal_token'     => null,
			],
			[ 'id' => $staff_id ],
			[ '%d', '%s', '%s' ],
			[ '%d' ]
		);
		wp_cache_delete( 'last_changed', Cache::STAFF );
	}

	public static function is_connected( int $staff_id ): bool {
		$staff = Staff_Repository::find( $staff_id );
		return $staff && ! empty( $staff['gcal_connected'] ) && ! empty( $staff['gcal_token'] );
	}

	/* ================= Tokens ================= */

	private static function store_tokens( int $staff_id, array $tokens, string $calendar_id ): void {
		global $wpdb;
		$payload = [
			'refresh_token' => $tokens['refresh_token'] ?? '',
			'access_token'  => $tokens['access_token'] ?? '',
			'expires_at'    => time() + (int) ( $tokens['expires_in'] ?? 3600 ),
		];
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write to the plugin's own custom table; its cache group is invalidated right after.
			$wpdb->update(
				Staff_Repository::table(),
				[
					'gcal_connected' => 1,
					'gcal_id'        => $calendar_id,
					'gcal_token'     => Crypto::encrypt( (string) wp_json_encode( $payload ) ),
				],
				[ 'id' => $staff_id ],
				[ '%d', '%s', '%s' ],
				[ '%d' ]
			);
			wp_cache_delete( 'last_changed', Cache::STAFF );
	}

	private static function load_tokens( int $staff_id ): ?array {
		$staff = Staff_Repository::find( $staff_id );
		if ( ! $staff || empty( $staff['gcal_token'] ) ) {
			return null;
		}
		$decoded = json_decode( (string) Crypto::decrypt( (string) $staff['gcal_token'] ), true );
		return is_array( $decoded ) ? $decoded : null;
	}

	private static function refresh_access_token( string $refresh_token ) {
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			[
				'timeout' => 20,
				'body'    => [
					'client_id'     => self::client_id(),
					'client_secret' => self::client_secret(),
					'refresh_token' => $refresh_token,
					'grant_type'    => 'refresh_token',
				],
			]
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['access_token'] ) ) {
			return new \WP_Error( 'gcal_token_refresh', __( 'Google token refresh failed.', 'bookingistic' ) );
		}
		return $body;
	}

	/**
	 * Get a valid access token for a staff member, refreshing if needed.
	 * Returns WP_Error or token string.
	 */
	public static function access_token_for( int $staff_id ) {
		$tokens = self::load_tokens( $staff_id );
		if ( ! $tokens ) {
			return new \WP_Error( 'gcal_not_connected', __( 'Google Calendar not connected.', 'bookingistic' ) );
		}
		if ( ! empty( $tokens['access_token'] ) && ( $tokens['expires_at'] ?? 0 ) > time() + 60 ) {
			return $tokens['access_token'];
		}
		if ( empty( $tokens['refresh_token'] ) ) {
			return new \WP_Error( 'gcal_no_refresh', __( 'Google Calendar connection is missing a refresh token. Reconnect.', 'bookingistic' ) );
		}
		$fresh = self::refresh_access_token( $tokens['refresh_token'] );
		if ( is_wp_error( $fresh ) ) {
			return $fresh;
		}
		self::store_tokens( $staff_id, array_merge( $tokens, [
			'access_token' => $fresh['access_token'],
			'expires_at'   => time() + (int) ( $fresh['expires_in'] ?? 3600 ),
		] ), (string) ( Staff_Repository::find( $staff_id )['gcal_id'] ?? 'primary' ) );

		return $fresh['access_token'];
	}

	/* ================= Bookings → Google ================= */

	/**
	 * Push one confirmed booking to the staff member's calendar (idempotent — updates existing event).
	 */
	public static function push_booking( array $booking ): bool {
		$staff_id = (int) ( $booking['staff_id'] ?? 0 );
		if ( $staff_id <= 0 || ! self::is_connected( $staff_id ) ) {
			return false;
		}
		if ( in_array( $booking['status'] ?? '', [ 'cancelled' ], true ) ) {
			return self::delete_event( $booking );
		}

		$token = self::access_token_for( $staff_id );
		if ( is_wp_error( $token ) ) {
			return false;
		}

		$body = self::event_body( $booking );
		$cal  = (string) ( Staff_Repository::find( $staff_id )['gcal_id'] ?: 'primary' );
		$meta = Calendar_Event_Repository::find_for_booking( (int) $booking['id'], self::PROVIDER );

		$endpoint = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode( $cal ) . '/events';
		if ( $meta && ! empty( $meta['external_event_id'] ) ) {
			$endpoint .= '/' . rawurlencode( $meta['external_event_id'] );
			$method    = 'PUT';
		} else {
			$method = 'POST';
		}

		$response = wp_remote_request(
			$endpoint . '?sendUpdates=none',
			[
				'method'  => $method,
				'timeout' => 20,
				'headers' => [
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode( $body ),
			]
		);
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['id'] ) ) {
			return false;
		}

		Calendar_Event_Repository::upsert(
			(int) $booking['id'],
			self::PROVIDER,
			[
				'external_event_id' => sanitize_text_field( $data['id'] ),
				'status'            => 'synced',
				'payload'           => [ 'htmlLink' => $data['htmlLink'] ?? '' ],
			]
		);
		return true;
	}

	private static function delete_event( array $booking ): bool {
		$staff_id = (int) ( $booking['staff_id'] ?? 0 );
		$meta     = Calendar_Event_Repository::find_for_booking( (int) $booking['id'], self::PROVIDER );
		if ( ! $meta || empty( $meta['external_event_id'] ) || $staff_id <= 0 ) {
			return false;
		}
		$token = self::access_token_for( $staff_id );
		if ( is_wp_error( $token ) ) {
			return false;
		}
		$cal = (string) ( Staff_Repository::find( $staff_id )['gcal_id'] ?: 'primary' );
		wp_remote_request(
			'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode( $cal ) . '/events/' . rawurlencode( $meta['external_event_id'] ),
			[
				'method'  => 'DELETE',
				'timeout' => 20,
				'headers' => [ 'Authorization' => 'Bearer ' . $token ],
			]
		);
		Calendar_Event_Repository::delete_for_booking( (int) $booking['id'], self::PROVIDER );
		return true;
	}

	private static function event_body( array $booking ): array {
		$service  = \Bookingistic\Database\Service_Repository::find( (int) $booking['service_id'] );
		$customer = \Bookingistic\Database\Customer_Repository::find( (int) $booking['customer_id'] );

		return [
			'summary'     => trim( ( $service['name'] ?? __( 'Booking', 'bookingistic' ) ) . ' — ' . ( $customer['full_name'] ?? '' ) ),
			'description' => sprintf(
				"%s\n%s\n%s",
				$customer['email'] ?? '',
				$customer['phone'] ?? '',
				$booking['notes'] ?? ''
			),
			'start'       => [ 'dateTime' => str_replace( ' ', 'T', $booking['start_datetime'] ) . 'Z', 'timeZone' => 'UTC' ],
			'end'         => [ 'dateTime' => str_replace( ' ', 'T', $booking['end_datetime'] ) . 'Z', 'timeZone' => 'UTC' ],
			'reminders'   => [ 'useDefault' => true ],
		];
	}

	/* ================= Google → availability (busy blocks) ================= */

	/**
	 * Cron: refresh busy intervals for every connected staff member.
	 */
	public static function sync_busy_intervals(): void {
		if ( ! self::is_configured() ) {
			return;
		}
		foreach ( Staff_Repository::all( [ 'status' => 'active' ] ) as $staff ) {
			if ( empty( $staff['gcal_connected'] ) ) {
				continue;
			}
			self::refresh_busy_for_staff( (int) $staff['id'] );
		}
	}

	/**
	 * Fetch freebusy for a staff member and cache it in a transient for 15 min.
	 */
	public static function refresh_busy_for_staff( int $staff_id ): bool {
		$token = self::access_token_for( $staff_id );
		if ( is_wp_error( $token ) ) {
			return false;
		}
		$now  = new \DateTimeImmutable( 'now', Date_Time::utc() );
		$from = $now->modify( '-1 day' );
		$to   = $now->modify( '+90 days' );

		$response = wp_remote_post(
			'https://www.googleapis.com/calendar/v3/freeBusy',
			[
				'timeout' => 20,
				'headers' => [
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'timeMin' => $from->format( 'Y-m-d\TH:i:s\Z' ),
						'timeMax' => $to->format( 'Y-m-d\TH:i:s\Z' ),
						'items'   => [ [ 'id' => (string) ( Staff_Repository::find( $staff_id )['gcal_id'] ?: 'primary' ) ] ],
					]
				),
			]
		);
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$data  = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$busy  = $data['calendars']['primary']['busy'] ?? ( $data['calendars'] ? reset( $data['calendars'] )['busy'] ?? [] : [] );
		if ( ! is_array( $busy ) ) {
			$busy = [];
		}

		$intervals = [];
		foreach ( $busy as $slot ) {
			if ( empty( $slot['start'] ) || empty( $slot['end'] ) ) {
				continue;
			}
			$intervals[] = [
				'start' => gmdate( 'Y-m-d H:i:s', strtotime( $slot['start'] ) ),
				'end'   => gmdate( 'Y-m-d H:i:s', strtotime( $slot['end'] ) ),
			];
		}

		set_transient( 'bookingistic_gcal_busy_' . $staff_id, $intervals, 15 * MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Filter callback: merge cached Google busy intervals into availability checks.
	 * Intervals are normalized to booking-row shape (start_datetime / end_datetime UTC).
	 *
	 * @param array $intervals
	 * @param int   $staff_id
	 * @return array
	 */
	public static function external_busy_intervals( array $intervals, int $staff_id ): array {
		if ( $staff_id <= 0 ) {
			return $intervals;
		}
		$cached = get_transient( 'bookingistic_gcal_busy_' . $staff_id );
		if ( is_array( $cached ) ) {
			foreach ( $cached as $slot ) {
				$intervals[] = [
					'start_datetime' => (string) ( $slot['start'] ?? '' ),
					'end_datetime'   => (string) ( $slot['end'] ?? '' ),
				];
			}
		}
		return $intervals;
	}

	/* ================= Scheduled sync ================= */

	/**
	 * Cron entry: refresh freebusy cache for connected staff, then retry any
	 * confirmed bookings that have no synced calendar event yet.
	 */
	public static function run_scheduled_sync(): void {
		if ( ! self::is_configured() ) {
			return;
		}
		self::sync_busy_intervals();

		foreach ( Staff_Repository::all( [ 'status' => 'active' ] ) as $staff ) {
			if ( empty( $staff['gcal_connected'] ) ) {
				continue;
			}
			$pending = Booking_Repository::query(
				[
					'staff_id' => (int) $staff['id'],
					'status'   => 'confirmed',
				]
			);
			foreach ( $pending as $booking ) {
				$meta = Calendar_Event_Repository::find_for_booking( (int) $booking['id'], self::PROVIDER );
				if ( ! $meta || empty( $meta['external_event_id'] ) ) {
					self::push_booking( $booking );
				}
			}
		}
	}

	/* ================= Low-level OAuth helpers ================= */

	private static function exchange_code( string $code ) {
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			[
				'timeout' => 20,
				'body'    => [
					'code'          => $code,
					'client_id'     => self::client_id(),
					'client_secret' => self::client_secret(),
					'redirect_uri'  => self::redirect_uri(),
					'grant_type'    => 'authorization_code',
				],
			]
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['access_token'] ) ) {
			return new \WP_Error( 'gcal_exchange', __( 'Google authorization failed. Check the Client ID / Secret and redirect URI.', 'bookingistic' ) );
		}
		return $body;
	}

	private static function fetch_primary_calendar_id( string $refresh_token ) {
		// With calendar.readonly scope we can use userinfo-free freeBusy on 'primary';
		// verify the refresh token works by requesting a fresh access token.
		$fresh = self::refresh_access_token( $refresh_token );
		if ( is_wp_error( $fresh ) ) {
			return $fresh;
		}
		return 'primary';
	}
}
