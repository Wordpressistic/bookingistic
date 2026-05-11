<?php
/**
 * Outlook Calendar integration.
 *
 * Outlook Web reads `.ics` natively (same RFC-5545 file as Google) and also
 * supports a Compose-Event deep link. Real Microsoft Graph OAuth ships in
 * the premium tier alongside the licensed product.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Integrations\Outlook;

use Bookingistic\Database\Service_Repository;
use Bookingistic\Integrations\Integration_Base;

defined( 'ABSPATH' ) || exit;

class Outlook_Integration extends Integration_Base {

	public function slug(): string { return 'outlook'; }
	public function label(): string { return __( 'Outlook Calendar', 'bookingistic' ); }

	public function description(): string {
		return __( 'Surface an “Add to Outlook” deep link in customer emails. Outlook also imports the .ics attachment from the Google Calendar integration.', 'bookingistic' );
	}

	public function fields(): array {
		return [];
	}

	public function boot(): void {
		add_filter( 'bookingistic_booking_email_vars', [ $this, 'add_outlook_link' ], 10, 4 );
	}

	public function add_outlook_link( array $vars, array $booking, $customer, $service ): array {
		if ( empty( $booking['start_datetime'] ) || empty( $booking['end_datetime'] ) ) {
			return $vars;
		}
		$service = $service ?: Service_Repository::find( (int) $booking['service_id'] );
		$start   = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $booking['start_datetime'] . ' UTC' ) );
		$end     = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $booking['end_datetime'] . ' UTC' ) );

		$url = add_query_arg(
			[
				'path'       => '/calendar/action/compose',
				'rru'        => 'addevent',
				'subject'    => rawurlencode( $service['name'] ?? __( 'Booking', 'bookingistic' ) ),
				'startdt'    => $start,
				'enddt'      => $end,
				'body'       => rawurlencode( $booking['notes'] ?? '' ),
			],
			'https://outlook.live.com/calendar/0/deeplink/compose'
		);

		$vars['outlook_calendar_link'] = $url;
		return $vars;
	}
}
