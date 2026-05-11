<?php
/**
 * Admin staff page — minimal stub for Phase 1.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Database\Staff_Repository;

defined( 'ABSPATH' ) || exit;

class Staff_Page {

	public static function render(): void {
		$staff = Staff_Repository::all();
		echo '<div class="wrap bookingistic-wrap"><h1>' . esc_html__( 'Staff', 'bookingistic' ) . '</h1>';
		if ( empty( $staff ) ) {
			echo '<div class="bookingistic-card bookingistic-card--muted"><p>'
				. esc_html__( 'No staff yet. Staff management ships in Phase 2 — for solo operators, bookings default to a single host using the business working hours from Settings.', 'bookingistic' )
				. '</p></div>';
		} else {
			echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Name', 'bookingistic' ) . '</th><th>' . esc_html__( 'Email', 'bookingistic' ) . '</th><th>' . esc_html__( 'Status', 'bookingistic' ) . '</th></tr></thead><tbody>';
			foreach ( $staff as $s ) {
				echo '<tr><td>' . esc_html( $s['name'] ) . '</td><td>' . esc_html( $s['email'] ) . '</td><td>' . esc_html( $s['status'] ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}
		echo '</div>';
	}
}
