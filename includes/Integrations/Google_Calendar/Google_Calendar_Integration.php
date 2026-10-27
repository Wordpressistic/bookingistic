<?php
/**
 * Google Calendar integration.
 *
 * Free tier (always): ICS attachments + "Add to Google Calendar" deep links.
 * Sync tier (when OAuth client credentials are set): real two-way sync via
 * GCal_Sync — confirmed bookings push to the staff member's calendar and
 * external events block availability through the freebusy API.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Integrations\Google_Calendar;

use Bookingistic\Database\Service_Repository;
use Bookingistic\Helpers\Token;
use Bookingistic\Integrations\Integration_Base;

defined( 'ABSPATH' ) || exit;

class Google_Calendar_Integration extends Integration_Base {

	public function slug(): string { return 'google_calendar'; }
	public function label(): string { return __( 'Google Calendar', 'bookingistic' ); }

	public function description(): string {
		return __( 'Free: .ics attachments + one-click "Add to Google Calendar" links. Sync mode: paste an OAuth Client ID + Secret and each staff member connects their own calendar — bookings push to Google, external events block booking slots.', 'bookingistic' );
	}

	public function fields(): array {
		return [
			'client_id' => [
				'label'       => __( 'OAuth Client ID', 'bookingistic' ),
				'type'        => 'text',
				'description' => sprintf(
					/* translators: %s: redirect URI */
					__( 'From Google Cloud Console → Credentials → OAuth client (Web application). Authorized redirect URI: %s', 'bookingistic' ),
					GCal_Sync::redirect_uri()
				),
			],
			'client_secret' => [
				'label' => __( 'OAuth Client Secret', 'bookingistic' ),
				'type'  => 'password',
			],
			'attach_ics' => [
				'type'        => 'checkbox',
				'label'       => __( 'Attach .ics file to customer emails', 'bookingistic' ),
				'description' => __( 'Works on every plan — no Google account needed.', 'bookingistic' ),
			],
		];
	}

	public function boot(): void {
		add_filter( 'bookingistic_booking_email_vars', [ $this, 'add_google_link' ], 10, 4 );
		if ( $this->setting( 'attach_ics' ) ) {
			add_filter( 'bookingistic_email_attachments', [ $this, 'attach_ics' ], 10, 2 );
		}

		// Two-way sync wiring (only when credentials exist — otherwise UI stays in free mode).
		if ( GCal_Sync::is_configured() ) {
			// Push/update/cancel events on booking lifecycle.
			add_action( 'bookingistic_booking_confirmed', [ GCal_Sync::class, 'push_booking' ], 10, 1 );
			add_action( 'bookingistic_booking_rescheduled', [ GCal_Sync::class, 'push_booking' ], 10, 1 );
			add_action( 'bookingistic_booking_cancelled', [ GCal_Sync::class, 'push_booking' ], 10, 1 );

			// External busy blocks feed the availability engine.
			add_filter( 'bookingistic_external_busy_intervals', [ GCal_Sync::class, 'external_busy_intervals' ], 10, 2 );

			// Cron: refresh freebusy + retry pending pushes.
			add_action( 'bookingistic_gcal_sync', [ GCal_Sync::class, 'run_scheduled_sync' ] );

			// OAuth callback (Google redirects the admin's browser here).
			add_action( 'rest_api_init', [ self::class, 'register_rest_routes' ] );
		}
	}

	public static function register_rest_routes(): void {
		register_rest_route(
			BOOKINGISTIC_REST_NAMESPACE,
			'/google/callback',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => '__return_true', // validated by signed state transient.
				'callback'            => [ self::class, 'handle_oauth_callback' ],
			]
		);
	}

	public static function handle_oauth_callback( \WP_REST_Request $request ) {
		$code  = (string) $request->get_param( 'code' );
		$state = (string) $request->get_param( 'state' );

		$result = GCal_Sync::handle_callback( $code, $state );

		$staff_page = admin_url( 'admin.php?page=bookingistic-staff' );
		$redirect   = add_query_arg(
			$result['ok'] ? [ 'gcal' => 'connected' ] : [ 'gcal' => 'error', 'gcal_msg' => rawurlencode( $result['message'] ) ],
			$staff_page
		);
		if ( wp_safe_redirect( $redirect ) ) {
			exit;
		}
		return new \WP_REST_Response( [ 'ok' => $result['ok'], 'message' => $result['message'] ] );
	}

	/**
	 * Inject {google_calendar_link} deep link into email variables.
	 */
	public function add_google_link( array $vars, array $booking, $customer, $service ): array {
		if ( empty( $booking['start_datetime'] ) || empty( $booking['end_datetime'] ) ) {
			return $vars;
		}
		$service = $service ?: Service_Repository::find( (int) $booking['service_id'] );
		$start   = gmdate( 'Ymd\THis\Z', strtotime( $booking['start_datetime'] . ' UTC' ) );
		$end     = gmdate( 'Ymd\THis\Z', strtotime( $booking['end_datetime'] . ' UTC' ) );

		$url = add_query_arg(
			[
				'action'  => 'TEMPLATE',
				'text'    => rawurlencode( $service['name'] ?? __( 'Booking', 'bookingistic' ) ),
				'dates'   => $start . '/' . $end,
				'details' => rawurlencode( $booking['notes'] ?? '' ),
			],
			'https://calendar.google.com/calendar/render'
		);

		if ( empty( $vars['meeting_link'] ) ) {
			$vars['meeting_link'] = $url;
		}
		$vars['google_calendar_link'] = $url;
		return $vars;
	}

	public function attach_ics( array $attachments, array $booking ): array {
		$path = $this->write_ics_temp( $booking );
		if ( $path ) {
			$attachments[] = $path;
		}
		return $attachments;
	}

	private function write_ics_temp( array $booking ): ?string {
		$ics = \Bookingistic\Integrations\ICS\ICS_Generator::for_booking( $booking );
		// System temp dir — outside the webroot, so booking data is never
		// written to a publicly served location. Random name: unguessable.
		$path = get_temp_dir() . uniqid( 'bookingistic-', true ) . '.ics';
		// WP_Filesystem_Direct: the file lives in the local temp dir and is
		// only attached to an outgoing wp_mail(), so FTP/SSH transports are
		// never appropriate here.
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		$filesystem = new \WP_Filesystem_Direct( null );
		if ( ! $filesystem->put_contents( $path, $ics ) ) {
			return null;
		}
		return $path;
	}

	/** Public helper: token-signed ICS URL. */
	public static function ics_url( array $booking ): string {
		return rest_url( BOOKINGISTIC_REST_NAMESPACE . '/calendar/booking.ics' ) .
			'?id=' . (int) $booking['id'] .
			'&t=' . rawurlencode( $booking['reschedule_token'] );
	}
}
