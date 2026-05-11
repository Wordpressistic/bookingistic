<?php
/**
 * Calendar REST controller — returns bookings within [from, to) as compact events.
 *
 * GET /calendar/events?from=YYYY-MM-DD&to=YYYY-MM-DD[&service_id=&staff_id=&status=]
 *
 * Admin-only. Response shape is FullCalendar-compatible plus extras (status,
 * customer_name, payment_status) for the inline detail modal.
 *
 * @package Bookingistic
 */

namespace Bookingistic\REST;

use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Customer_Repository;
use Bookingistic\Database\Service_Repository;
use Bookingistic\Database\Staff_Repository;

defined( 'ABSPATH' ) || exit;

class Calendar_Controller {

	public static function register_routes(): void {
		register_rest_route(
			BOOKINGISTIC_REST_NAMESPACE,
			'/calendar/events',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'events' ],
				'permission_callback' => [ REST_Controller::class, 'require_manage' ],
				'args'                => [
					'from'           => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
					'to'             => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
					'service_id'     => [ 'sanitize_callback' => 'absint' ],
					'staff_id'       => [ 'sanitize_callback' => 'absint' ],
					'status'         => [ 'sanitize_callback' => 'sanitize_key' ],
					'payment_status' => [ 'sanitize_callback' => 'sanitize_key' ],
				],
			]
		);
	}

	public static function events( \WP_REST_Request $req ) {
		$from = (string) $req->get_param( 'from' );
		$to   = (string) $req->get_param( 'to' );

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) {
			return new \WP_Error( 'invalid_range', __( 'Invalid date range.', 'bookingistic' ), [ 'status' => 422 ] );
		}

		$args = [
			'from'           => $from . ' 00:00:00',
			'to'             => $to . ' 23:59:59',
			'per_page'       => 500,
			'orderby'        => 'start_datetime',
			'order'          => 'ASC',
			'service_id'     => (int) $req->get_param( 'service_id' ),
			'staff_id'       => (int) $req->get_param( 'staff_id' ),
			'status'         => (string) $req->get_param( 'status' ),
			'payment_status' => (string) $req->get_param( 'payment_status' ),
		];
		$result = Booking_Repository::query( array_filter( $args, static fn( $v ) => $v !== '' && $v !== 0 ) );

		$service_cache  = [];
		$customer_cache = [];
		$staff_cache    = [];
		$events         = [];

		foreach ( $result['items'] as $b ) {
			if ( ! $b['start_datetime'] ) {
				continue;
			}
			$service_cache[ $b['service_id'] ]   ??= Service_Repository::find( (int) $b['service_id'] );
			$customer_cache[ $b['customer_id'] ] ??= Customer_Repository::find( (int) $b['customer_id'] );
			if ( $b['staff_id'] ) {
				$staff_cache[ $b['staff_id'] ] ??= Staff_Repository::find( (int) $b['staff_id'] );
			}

			$service  = $service_cache[ $b['service_id'] ];
			$customer = $customer_cache[ $b['customer_id'] ];
			$staff    = $b['staff_id'] ? $staff_cache[ $b['staff_id'] ] : null;

			$events[] = [
				'id'             => (int) $b['id'],
				'title'          => trim( ( $customer['full_name'] ?? __( 'Unknown', 'bookingistic' ) ) . ' · ' . ( $service['name'] ?? '' ), ' ·' ),
				'start'          => str_replace( ' ', 'T', $b['start_datetime'] ) . 'Z',
				'end'            => $b['end_datetime'] ? str_replace( ' ', 'T', $b['end_datetime'] ) . 'Z' : null,
				'status'         => $b['status'],
				'payment_status' => $b['payment_status'],
				'service_name'   => $service['name'] ?? '',
				'service_id'     => (int) $b['service_id'],
				'staff_name'     => $staff['name'] ?? '',
				'staff_id'       => (int) $b['staff_id'],
				'customer_name'  => $customer['full_name'] ?? '',
				'customer_email' => $customer['email'] ?? '',
				'customer_id'    => (int) $b['customer_id'],
				'admin_url'      => admin_url( 'admin.php?page=bookingistic-bookings&view=' . (int) $b['id'] ),
			];
		}

		return rest_ensure_response(
			[
				'from'   => $from,
				'to'     => $to,
				'total'  => count( $events ),
				'events' => $events,
			]
		);
	}
}
