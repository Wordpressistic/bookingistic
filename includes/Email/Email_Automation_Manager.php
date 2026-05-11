<?php
/**
 * Email automation manager.
 *
 * Listens for booking action hooks and dispatches any matching automation rows.
 * Reminders are dispatched on the `bookingistic_send_reminders` cron tick.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Email;

use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Customer_Repository;
use Bookingistic\Database\Email_Automation_Repository;
use Bookingistic\Database\Service_Repository;

defined( 'ABSPATH' ) || exit;

class Email_Automation_Manager {

	public static function register(): void {
		add_action( 'bookingistic_booking_submitted', [ self::class, 'on_event' ], 10, 3 );
		add_action( 'bookingistic_booking_confirmed', [ self::class, 'on_event' ], 10, 3 );
		add_action( 'bookingistic_booking_cancelled', [ self::class, 'on_event' ], 10, 3 );
		add_action( 'bookingistic_booking_rescheduled', [ self::class, 'on_event' ], 10, 3 );
		add_action( 'bookingistic_booking_completed', [ self::class, 'on_event' ], 10, 3 );

		add_action( 'bookingistic_send_reminders', [ self::class, 'dispatch_due_reminders' ] );
	}

	public static function on_event( $booking, $customer = null, $service = null ): void {
		if ( ! is_array( $booking ) ) {
			return;
		}
		$event = self::current_filter_to_event( current_action() );
		if ( ! $event ) {
			return;
		}
		$customer = $customer ?: Customer_Repository::find( (int) $booking['customer_id'] );
		$service  = $service ?: Service_Repository::find( (int) $booking['service_id'] );

		$rules = Email_Automation_Repository::for_trigger( $event, (int) $booking['service_id'] );
		foreach ( $rules as $rule ) {
			self::dispatch_rule( $rule, $booking, $customer, $service );
		}
	}

	public static function dispatch_due_reminders(): void {
		$settings = get_option( 'bookingistic_settings', [] );
		$hours    = 24; // default reminder window
		$rules    = Email_Automation_Repository::for_trigger( 'booking_reminder', 0 );
		if ( ! $rules ) {
			return;
		}
		// Use the first rule's delay as the lead-time window.
		$lead = (int) ( $rules[0]['delay_amount'] ?: $hours );
		if ( $rules[0]['delay_unit'] === 'minutes' ) {
			$lead = max( 1, (int) ceil( $lead / 60 ) );
		}
		$due = Booking_Repository::due_reminders( $lead, 50 );

		foreach ( $due as $booking ) {
			$customer = Customer_Repository::find( (int) $booking['customer_id'] );
			$service  = Service_Repository::find( (int) $booking['service_id'] );
			$service_rules = Email_Automation_Repository::for_trigger( 'booking_reminder', (int) $booking['service_id'] );
			foreach ( ( $service_rules ?: $rules ) as $rule ) {
				self::dispatch_rule( $rule, $booking, $customer, $service );
			}
			Booking_Repository::update( (int) $booking['id'], [ 'reminder_sent_at' => current_time( 'mysql', true ) ] );
		}
	}

	private static function dispatch_rule( array $rule, array $booking, ?array $customer, ?array $service ): void {
		$to = self::resolve_recipient( $rule, $customer );
		if ( ! $to ) {
			return;
		}
		$vars    = Email_Template_Renderer::variables_for_booking( $booking, $customer, $service );
		$subject = Email_Template_Renderer::render_subject( $rule['subject'], $vars );
		$body    = Email_Template_Renderer::render_body( $rule['body'], $vars );

		Email_Sender::send(
			$to,
			$subject,
			$body,
			[
				'automation_id' => (int) $rule['id'],
				'booking_id'    => (int) $booking['id'],
				'customer_id'   => (int) ( $customer['id'] ?? 0 ),
				'booking'       => $booking,
			]
		);
	}

	private static function resolve_recipient( array $rule, ?array $customer ): string {
		$settings = get_option( 'bookingistic_settings', [] );
		switch ( $rule['recipient_type'] ) {
			case 'customer':
				return $customer['email'] ?? '';
			case 'admin':
				return $settings['admin_notify'] ?? get_option( 'admin_email' );
			case 'staff':
				return $settings['staff_notify'] ?? ( $settings['admin_notify'] ?? get_option( 'admin_email' ) );
			case 'custom':
				return $rule['recipient_custom'] ?? '';
		}
		return '';
	}

	private static function current_filter_to_event( string $action ): string {
		return match ( $action ) {
			'bookingistic_booking_submitted'   => 'booking_submitted',
			'bookingistic_booking_confirmed'   => 'booking_confirmed',
			'bookingistic_booking_cancelled'   => 'booking_cancelled',
			'bookingistic_booking_rescheduled' => 'booking_rescheduled',
			'bookingistic_booking_completed'   => 'booking_completed',
			default                            => '',
		};
	}
}
