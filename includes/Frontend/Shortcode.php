<?php
/**
 * `[bookingistic]` shortcode.
 *
 * Attributes:
 *   service    Service slug (preferred)
 *   service_id Numeric ID (fallback)
 *   type       Booking type filter (future)
 *   staff      Staff slug/email (future filter)
 *   category   Service category (future filter)
 *   reschedule_id   Internal — passed by Reschedule_Manager
 *   reschedule_token Internal — token tied to that booking
 *
 * @package Bookingistic
 */

namespace Bookingistic\Frontend;

use Bookingistic\Database\Service_Repository;
use Bookingistic\Database\Staff_Repository;

defined( 'ABSPATH' ) || exit;

class Shortcode {

	public static function register(): void {
		add_shortcode( 'bookingistic', [ self::class, 'render' ] );
	}

	public static function render( $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'service'           => '',
				'service_id'        => 0,
				'type'              => '',
				'staff'             => '',
				'category'          => '',
				'reschedule_id'     => 0,
				'reschedule_token'  => '',
			],
			(array) $atts,
			'bookingistic'
		);

		$service = null;
		if ( ! empty( $atts['service'] ) ) {
			$service = Service_Repository::find_by_slug( sanitize_title( $atts['service'] ) );
		}
		if ( ! $service && ! empty( $atts['service_id'] ) ) {
			$service = Service_Repository::find( (int) $atts['service_id'] );
		}
		if ( ! $service ) {
			$services = Service_Repository::all( [ 'status' => 'active' ] );
			$service  = $services[0] ?? null;
		}
		if ( ! $service || $service['status'] !== 'active' ) {
			return '<div class="bookingistic-error">' . esc_html__( 'No booking service is currently available.', 'bookingistic' ) . '</div>';
		}

		Assets::enqueue_frontend();

		$booking_form_id   = 'bookingistic-form-' . wp_unique_id();
		$reschedule_id     = (int) $atts['reschedule_id'];
		$reschedule_token  = sanitize_text_field( $atts['reschedule_token'] );

		// Staff that can fulfil this service. Only show the picker when more than one.
		$assignable_staff = Staff_Repository::for_service( (int) $service['id'] );

		ob_start();
		include BOOKINGISTIC_DIR . 'templates/frontend/booking-form.php';
		return (string) ob_get_clean();
	}
}
