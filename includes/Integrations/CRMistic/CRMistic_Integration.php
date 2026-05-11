<?php
/**
 * CRMistic customer-sync integration.
 *
 * Pushes signed customer + booking payloads to your CRMistic webhook so
 * leads and bookings appear automatically inside CRMistic.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Integrations\CRMistic;

use Bookingistic\Database\Customer_Repository;
use Bookingistic\Database\Service_Repository;
use Bookingistic\Helpers\Webhook;
use Bookingistic\Integrations\Integration_Base;

defined( 'ABSPATH' ) || exit;

class CRMistic_Integration extends Integration_Base {

	public function slug(): string { return 'crmistic'; }
	public function label(): string { return __( 'CRMistic', 'bookingistic' ); }

	public function description(): string {
		return __( 'Sync customers and booking events into CRMistic for unified contact + pipeline management.', 'bookingistic' );
	}

	public function fields(): array {
		return [
			'webhook_url' => [
				'label'       => __( 'CRMistic webhook URL', 'bookingistic' ),
				'type'        => 'url',
				'placeholder' => 'https://crmistic.example.com/webhook/bookingistic',
			],
			'webhook_secret' => [
				'label' => __( 'Webhook signing secret', 'bookingistic' ),
				'type'  => 'password',
			],
			'sync_on_submit'  => [ 'label' => __( 'Push on booking submitted', 'bookingistic' ), 'type' => 'checkbox' ],
			'sync_on_confirm' => [ 'label' => __( 'Push on booking confirmed', 'bookingistic' ), 'type' => 'checkbox' ],
			'sync_on_cancel'  => [ 'label' => __( 'Push on booking cancelled', 'bookingistic' ), 'type' => 'checkbox' ],
			'default_source'  => [
				'label'       => __( 'Default source tag', 'bookingistic' ),
				'type'        => 'text',
				'description' => __( 'Sent as customer.source when missing.', 'bookingistic' ),
			],
		];
	}

	public function boot(): void {
		add_action( 'bookingistic_booking_submitted',  [ $this, 'on_event' ], 10, 3 );
		add_action( 'bookingistic_booking_confirmed',  [ $this, 'on_event' ], 10, 3 );
		add_action( 'bookingistic_booking_cancelled',  [ $this, 'on_event' ], 10, 3 );
	}

	public function on_event( $booking, $customer = null, $service = null ): void {
		if ( ! is_array( $booking ) ) {
			return;
		}
		$event = self::current_filter_to_event( current_action() );
		if ( ! $event ) {
			return;
		}
		$flag_map = [
			'booking_submitted' => 'sync_on_submit',
			'booking_confirmed' => 'sync_on_confirm',
			'booking_cancelled' => 'sync_on_cancel',
		];
		if ( empty( $this->setting( $flag_map[ $event ] ?? '' ) ) ) {
			return;
		}

		$url = (string) $this->setting( 'webhook_url' );
		if ( ! $url ) {
			return;
		}

		$customer = $customer ?: Customer_Repository::find( (int) $booking['customer_id'] );
		$service  = $service ?: Service_Repository::find( (int) $booking['service_id'] );
		$default_source = (string) $this->setting( 'default_source', '' );

		Webhook::post(
			$url,
			[
				'event'    => $event,
				'sent_at'  => current_time( 'mysql', true ),
				'customer' => [
					'id'         => (int) ( $customer['id'] ?? 0 ),
					'first_name' => $customer['first_name'] ?? '',
					'last_name'  => $customer['last_name'] ?? '',
					'full_name'  => $customer['full_name'] ?? '',
					'email'      => $customer['email'] ?? '',
					'phone'      => $customer['phone'] ?? '',
					'company'    => $customer['company'] ?? '',
					'website'    => $customer['website'] ?? '',
					'timezone'   => $customer['timezone'] ?? '',
					'source'     => $customer['source'] ?: $default_source,
					'tags'       => $customer['tags'] ?? '',
				],
				'booking'  => [
					'id'             => (int) $booking['id'],
					'service_id'     => (int) $booking['service_id'],
					'service_name'   => $service['name'] ?? '',
					'staff_id'       => (int) $booking['staff_id'],
					'start_datetime' => $booking['start_datetime'],
					'end_datetime'   => $booking['end_datetime'],
					'timezone'       => $booking['timezone'],
					'status'         => $booking['status'],
					'payment_status' => $booking['payment_status'],
					'payment_amount' => (float) $booking['payment_amount'],
					'source'         => $booking['source'] ?? '',
					'notes'          => $booking['notes'] ?? '',
				],
				'site'     => home_url( '/' ),
			],
			(string) $this->setting( 'webhook_secret' )
		);
	}

	private static function current_filter_to_event( string $action ): string {
		return match ( $action ) {
			'bookingistic_booking_submitted' => 'booking_submitted',
			'bookingistic_booking_confirmed' => 'booking_confirmed',
			'bookingistic_booking_cancelled' => 'booking_cancelled',
			default                          => '',
		};
	}
}
