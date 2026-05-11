<?php
/**
 * Public ICS feed for a single booking.
 *
 * GET /calendar/booking.ics?id=N&t=TOKEN
 *
 * Token is the booking's reschedule_token (already shared with the customer
 * in their confirmation email), so anyone who has the URL can re-add the
 * event to their calendar without an account.
 *
 * @package Bookingistic
 */

namespace Bookingistic\REST;

use Bookingistic\Database\Booking_Repository;
use Bookingistic\Helpers\Token;
use Bookingistic\Integrations\ICS\ICS_Generator;

defined( 'ABSPATH' ) || exit;

class ICS_Controller {

	public static function register_routes(): void {
		register_rest_route(
			BOOKINGISTIC_REST_NAMESPACE,
			'/calendar/booking.ics',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'serve' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
					't'  => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
				],
			]
		);
	}

	public static function serve( \WP_REST_Request $req ) {
		$id      = (int) $req->get_param( 'id' );
		$token   = (string) $req->get_param( 't' );
		$booking = Booking_Repository::find( $id );
		if ( ! $booking || ! Token::consume_equals( $booking['reschedule_token'], $token ) ) {
			return new \WP_Error( 'invalid_token', __( 'Invalid calendar link.', 'bookingistic' ), [ 'status' => 410 ] );
		}

		$ics = ICS_Generator::for_booking( $booking );

		$response = new \WP_REST_Response( $ics );
		$response->header( 'Content-Type', 'text/calendar; charset=utf-8' );
		$response->header( 'Content-Disposition', 'attachment; filename="bookingistic-' . (int) $booking['id'] . '.ics"' );
		return $response;
	}
}
