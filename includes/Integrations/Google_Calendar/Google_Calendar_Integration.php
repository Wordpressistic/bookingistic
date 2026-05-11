<?php
/**
 * Google Calendar integration.
 *
 * Two paths are supported, both account-less:
 *
 *   1. An ICS attachment that Google Calendar (and every other RFC-5545
 *      client) imports natively.
 *   2. A "Add to Google Calendar" deep link that opens the new-event UI
 *      pre-filled with the booking details.
 *
 * Real two-way OAuth sync is intentionally not implemented in Phase 5
 * — it requires per-user OAuth keys and a tested refresh-token loop
 * which belongs in Phase 6 alongside the licensed premium product.
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
		return __( 'Attach an .ics file to customer confirmation emails and surface a one-click “Add to Google Calendar” link. Real two-way OAuth sync ships in the premium tier.', 'bookingistic' );
	}

	public function fields(): array {
		return [
			'attach_ics' => [
				'label'       => __( 'Attach .ics file to customer emails', 'bookingistic' ),
				'type'        => 'checkbox',
				'description' => __( 'When enabled, every customer confirmation email carries an .ics calendar attachment.', 'bookingistic' ),
			],
		];
	}

	public function boot(): void {
		add_filter( 'bookingistic_booking_email_vars', [ $this, 'add_google_link' ], 10, 4 );
		if ( $this->setting( 'attach_ics' ) ) {
			add_filter( 'bookingistic_email_attachments', [ $this, 'attach_ics' ], 10, 2 );
		}
	}

	/**
	 * Inject {meeting_link} = Google "Add to Calendar" deep link into the email variables.
	 *
	 * @param array      $vars
	 * @param array      $booking
	 * @param array|null $customer
	 * @param array|null $service
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

		// Don't override an existing meeting_link set elsewhere.
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
		$upload = wp_get_upload_dir();
		if ( empty( $upload['basedir'] ) ) {
			return null;
		}
		$dir = trailingslashit( $upload['basedir'] ) . 'bookingistic';
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$path = $dir . '/booking-' . (int) $booking['id'] . '.ics';
		file_put_contents( $path, $ics );
		return $path;
	}

	/** Public helper used by the booking-form / emails: token-signed ICS URL. */
	public static function ics_url( array $booking ): string {
		return rest_url( BOOKINGISTIC_REST_NAMESPACE . '/calendar/booking.ics' ) .
			'?id=' . (int) $booking['id'] .
			'&t=' . rawurlencode( $booking['reschedule_token'] );
	}
}
