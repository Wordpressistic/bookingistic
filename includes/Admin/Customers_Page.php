<?php
/**
 * Admin customers page.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Database\Customer_Repository;

defined( 'ABSPATH' ) || exit;

class Customers_Page {

	public static function render(): void {
		$page   = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
		$result = Customer_Repository::paginate( $page, 25 );

		echo '<div class="wrap bookingistic-wrap"><h1>' . esc_html__( 'Customers', 'bookingistic' ) . '</h1>';

		if ( empty( $result['items'] ) ) {
			echo '<div class="bookingistic-card bookingistic-card--muted"><p>' . esc_html__( 'No customers yet.', 'bookingistic' ) . '</p></div></div>';
			return;
		}

		echo '<table class="widefat striped">
			<thead><tr>
				<th>' . esc_html__( 'Name', 'bookingistic' ) . '</th>
				<th>' . esc_html__( 'Email', 'bookingistic' ) . '</th>
				<th>' . esc_html__( 'Phone', 'bookingistic' ) . '</th>
				<th>' . esc_html__( 'Source', 'bookingistic' ) . '</th>
				<th>' . esc_html__( 'Created', 'bookingistic' ) . '</th>
			</tr></thead><tbody>';
		foreach ( $result['items'] as $c ) {
			echo '<tr>
				<td>' . esc_html( $c['full_name'] ?: trim( $c['first_name'] . ' ' . $c['last_name'] ) ) . '</td>
				<td>' . esc_html( $c['email'] ) . '</td>
				<td>' . esc_html( $c['phone'] ) . '</td>
				<td>' . esc_html( $c['source'] ) . '</td>
				<td>' . esc_html( $c['created_at'] ) . '</td>
			</tr>';
		}
		echo '</tbody></table></div>';
	}
}
