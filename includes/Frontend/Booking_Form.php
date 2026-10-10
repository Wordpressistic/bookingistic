<?php
/**
 * Booking form renderer — resolves template + appearance and includes the
 * right template file (theme-overridable).
 *
 * @package Bookingistic
 */

namespace Bookingistic\Frontend;

defined( 'ABSPATH' ) || exit;

class Booking_Form {

	/**
	 * Render a booking form for a service.
	 *
	 * @param array  $service           Service row.
	 * @param array  $assignable_staff  Staff rows.
	 * @param int    $reschedule_id     Booking id in reschedule mode.
	 * @param string $reschedule_token  Reschedule token.
	 * @param array  $overrides         Appearance overrides (template, calendar).
	 * @return string HTML.
	 */
	public static function render( array $service, array $assignable_staff = [], int $reschedule_id = 0, string $reschedule_token = '', array $overrides = [] ): string {
		$appearance = Template_Loader::appearance();

		$template     = sanitize_key( $overrides['template'] ?? '' ) ?: $appearance['template'];
		$calendar     = sanitize_key( $overrides['calendar'] ?? '' ) ?: $appearance['calendar'];
		$calendar     = array_key_exists( $calendar, Template_Loader::calendar_views() ) ? $calendar : 'month';

		Assets::enqueue_frontend();

		$booking_form_id = 'bookingistic-form-' . wp_unique_id();
		$calendar_view   = $calendar; // Template variable name contract.

		ob_start();
		include Template_Loader::form_template_path( $template );
		return (string) ob_get_clean();
	}
}
