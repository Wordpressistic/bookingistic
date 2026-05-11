<?php
/**
 * Customer-facing reschedule handler.
 *
 * GET /?bookingistic_action=reschedule&id=N&t=TOKEN shows a simple page that
 * loads the booking form pre-bound to the reschedule context. POST submits a new slot.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Booking;

use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Service_Repository;
use Bookingistic\Helpers\Token;

defined( 'ABSPATH' ) || exit;

class Reschedule_Manager {

	public static function register(): void {
		add_action( 'template_redirect', [ self::class, 'maybe_handle' ] );
	}

	public static function maybe_handle(): void {
		if ( ( $_GET['bookingistic_action'] ?? '' ) !== 'reschedule' ) {
			return;
		}
		$id    = (int) ( $_GET['id'] ?? 0 );
		$token = sanitize_text_field( wp_unslash( $_GET['t'] ?? '' ) );

		$booking = Booking_Repository::find( $id );
		if ( ! $booking || ! Token::consume_equals( $booking['reschedule_token'], $token ) ) {
			wp_die( esc_html__( 'Invalid or expired reschedule link.', 'bookingistic' ), '', 410 );
		}
		if ( Token::is_expired( $booking['created_at'] ) ) {
			wp_die( esc_html__( 'This reschedule link has expired.', 'bookingistic' ), '', 410 );
		}

		$settings   = get_option( 'bookingistic_settings', [] );
		$window_hrs = (int) ( $settings['reschedule_window_hrs'] ?? 24 );
		if ( $booking['start_datetime'] ) {
			$start_ts = strtotime( $booking['start_datetime'] . ' UTC' );
			if ( $start_ts && ( $start_ts - time() ) < ( $window_hrs * HOUR_IN_SECONDS ) ) {
				wp_die(
					sprintf(
						/* translators: %d hours window. */
						esc_html__( 'Reschedule is only allowed up to %d hours before the booking. Please contact us instead.', 'bookingistic' ),
						$window_hrs
					),
					'',
					400
				);
			}
		}

		$service = Service_Repository::find( (int) $booking['service_id'] );
		if ( ! $service ) {
			wp_die( esc_html__( 'Service unavailable.', 'bookingistic' ), '', 404 );
		}

		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );

		$title         = esc_html__( 'Reschedule your booking', 'bookingistic' );
		$service_slug  = $service['slug'];
		$reschedule_id = (int) $booking['id'];
		$rest_url      = esc_url( rest_url( BOOKINGISTIC_REST_NAMESPACE . '/' ) );
		?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $title; ?></title>
	<?php wp_head(); ?>
</head>
<body class="bookingistic-page">
	<main style="max-width:980px;margin:48px auto;padding:0 20px">
		<h1><?php echo $title; ?></h1>
		<p><?php esc_html_e( 'Pick a new time. Your current booking will be replaced once confirmed.', 'bookingistic' ); ?></p>
		<?php
		echo do_shortcode(
			sprintf(
				'[bookingistic service="%s" reschedule_id="%d" reschedule_token="%s"]',
				esc_attr( $service_slug ),
				$reschedule_id,
				esc_attr( $token )
			)
		);
		?>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
		<?php
		exit;
	}

	/**
	 * Apply a reschedule submitted via REST — invoked from Bookings_Controller.
	 */
	public static function apply( int $booking_id, string $token, string $new_start_utc, int $new_staff_id = 0 ) {
		$booking = Booking_Repository::find( $booking_id );
		if ( ! $booking || ! Token::consume_equals( $booking['reschedule_token'], $token ) ) {
			return new \WP_Error( 'invalid_token', __( 'Invalid reschedule token.', 'bookingistic' ), [ 'status' => 403 ] );
		}

		$validation = Booking_Validator::validate_slot(
			(int) $booking['service_id'],
			$new_start_utc,
			$new_staff_id ?: (int) $booking['staff_id'],
			$booking_id
		);
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$updated = Booking_Repository::update(
			$booking_id,
			[
				'start_datetime' => $new_start_utc,
				'end_datetime'   => $validation['end_utc'],
				'staff_id'       => $new_staff_id ?: (int) $booking['staff_id'],
				'status'         => 'rescheduled',
			]
		);

		do_action( 'bookingistic_booking_rescheduled', $updated, null, null );

		return $updated;
	}
}
