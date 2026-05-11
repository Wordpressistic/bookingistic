<?php
/**
 * Admin reports page — Phase 2.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

defined( 'ABSPATH' ) || exit;

class Reports_Page {

	public static function render(): void {
		echo '<div class="wrap bookingistic-wrap"><h1>' . esc_html__( 'Reports', 'bookingistic' ) . '</h1>'
			. '<div class="bookingistic-card bookingistic-card--muted"><p>'
			. esc_html__( 'Bookings by service / staff, cancellation rate, no-show rate, and email-delivery logs ship in Phase 2.', 'bookingistic' )
			. '</p></div></div>';
	}
}
