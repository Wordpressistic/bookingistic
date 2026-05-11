<?php
/**
 * Messageistic SMS integration.
 *
 * Posts a signed webhook to your Messageistic endpoint for each booking
 * event you opt in to. Messageistic handles the actual SMS delivery.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Integrations\Messageistic;

use Bookingistic\Database\Customer_Repository;
use Bookingistic\Database\Service_Repository;
use Bookingistic\Helpers\Webhook;
use Bookingistic\Integrations\Integration_Base;

defined( 'ABSPATH' ) || exit;

class Messageistic_Integration extends Integration_Base {

	public function slug(): string { return 'messageistic'; }
	public function label(): string { return __( 'Messageistic SMS', 'bookingistic' ); }

	public function description(): string {
		return __( 'Send signed booking events to your Messageistic webhook for SMS delivery. Supports confirmation, reminder, cancellation and reschedule events.', 'bookingistic' );
	}

	public function fields(): array {
		return [
			'webhook_url'  => [
				'label'       => __( 'Messageistic webhook URL', 'bookingistic' ),
				'type'        => 'url',
				'placeholder' => 'https://messageistic.example.com/webhook/bookingistic',
			],
			'webhook_secret' => [
				'label'       => __( 'Webhook signing secret', 'bookingistic' ),
				'type'        => 'password',
				'description' => __( 'Used to sign payloads as HMAC-SHA256 in X-Bookingistic-Signature.', 'bookingistic' ),
			],
			'send_confirmation' => [ 'label' => __( 'Send on booking confirmed', 'bookingistic' ), 'type' => 'checkbox' ],
			'send_reminder'     => [ 'label' => __( 'Send on booking reminder', 'bookingistic' ), 'type' => 'checkbox' ],
			'send_cancellation' => [ 'label' => __( 'Send on booking cancelled', 'bookingistic' ), 'type' => 'checkbox' ],
			'send_reschedule'   => [ 'label' => __( 'Send on booking rescheduled', 'bookingistic' ), 'type' => 'checkbox' ],
		];
	}

	public function boot(): void {
		add_action( 'bookingistic_booking_confirmed',   [ $this, 'on_confirmed' ], 10, 3 );
		add_action( 'bookingistic_booking_cancelled',   [ $this, 'on_cancelled' ], 10, 3 );
		add_action( 'bookingistic_booking_rescheduled', [ $this, 'on_rescheduled' ], 10, 3 );
		// Reminder dispatch is also routed via this hook.
	}

	public function on_confirmed( $booking, $customer = null, $service = null ): void {
		if ( $this->setting( 'send_confirmation' ) ) {
			$this->dispatch( 'booking_confirmed', $booking, $customer, $service );
		}
	}

	public function on_cancelled( $booking, $customer = null, $service = null ): void {
		if ( $this->setting( 'send_cancellation' ) ) {
			$this->dispatch( 'booking_cancelled', $booking, $customer, $service );
		}
	}

	public function on_rescheduled( $booking, $customer = null, $service = null ): void {
		if ( $this->setting( 'send_reschedule' ) ) {
			$this->dispatch( 'booking_rescheduled', $booking, $customer, $service );
		}
	}

	private function dispatch( string $event, $booking, $customer, $service ): void {
		$url = (string) $this->setting( 'webhook_url' );
		if ( ! $url || ! is_array( $booking ) ) {
			return;
		}
		$customer = $customer ?: Customer_Repository::find( (int) $booking['customer_id'] );
		$service  = $service ?: Service_Repository::find( (int) $booking['service_id'] );

		if ( empty( $customer['phone'] ) ) {
			return; // SMS needs a phone.
		}

		Webhook::post(
			$url,
			[
				'event'    => $event,
				'sent_at'  => current_time( 'mysql', true ),
				'booking'  => [
					'id'             => (int) $booking['id'],
					'start_datetime' => $booking['start_datetime'],
					'end_datetime'   => $booking['end_datetime'],
					'timezone'       => $booking['timezone'],
					'status'         => $booking['status'],
				],
				'service'  => [ 'id' => (int) ( $service['id'] ?? 0 ), 'name' => $service['name'] ?? '' ],
				'customer' => [
					'id'    => (int) ( $customer['id'] ?? 0 ),
					'name'  => $customer['full_name'] ?? '',
					'phone' => $customer['phone'] ?? '',
					'email' => $customer['email'] ?? '',
				],
				'site'     => home_url( '/' ),
			],
			(string) $this->setting( 'webhook_secret' )
		);
	}
}
