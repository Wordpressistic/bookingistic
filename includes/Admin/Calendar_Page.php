<?php
/**
 * Admin calendar page — Phase 2.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

defined( 'ABSPATH' ) || exit;

class Calendar_Page {

	public static function render(): void {
		echo '<div class="wrap bookingistic-wrap"><h1>' . esc_html__( 'Calendar', 'bookingistic' ) . '</h1>'
			. '<div class="bookingistic-card bookingistic-card--muted"><p>'
			. esc_html__( 'The month / week / day / list calendar view ships in Phase 2. For now, view scheduled bookings under Bookings.', 'bookingistic' )
			. '</p></div></div>';
	}
}
