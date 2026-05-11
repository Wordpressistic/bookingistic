<?php
/**
 * Admin email automations page.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Database\Email_Automation_Repository;

defined( 'ABSPATH' ) || exit;

class Email_Automations_Page {

	public static function render(): void {
		$rules = Email_Automation_Repository::all();
		echo '<div class="wrap bookingistic-wrap"><h1>' . esc_html__( 'Email Automations', 'bookingistic' ) . '</h1>';
		echo '<p>' . esc_html__( 'Default automations are seeded on activation. The full visual editor ships in Phase 3.', 'bookingistic' ) . '</p>';
		echo '<table class="widefat striped">
			<thead><tr>
				<th>' . esc_html__( 'Name', 'bookingistic' ) . '</th>
				<th>' . esc_html__( 'Trigger', 'bookingistic' ) . '</th>
				<th>' . esc_html__( 'Recipient', 'bookingistic' ) . '</th>
				<th>' . esc_html__( 'Delay', 'bookingistic' ) . '</th>
				<th>' . esc_html__( 'Status', 'bookingistic' ) . '</th>
			</tr></thead><tbody>';
		foreach ( $rules as $r ) {
			echo '<tr>
				<td>' . esc_html( $r['name'] ) . '</td>
				<td><code>' . esc_html( $r['trigger_event'] ) . '</code></td>
				<td>' . esc_html( $r['recipient_type'] ) . '</td>
				<td>' . esc_html( $r['delay_amount'] . ' ' . $r['delay_unit'] ) . '</td>
				<td>' . esc_html( $r['status'] ) . '</td>
			</tr>';
		}
		echo '</tbody></table></div>';
	}
}
