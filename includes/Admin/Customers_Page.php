<?php
/**
 * Admin customers: list + detail.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Database\Booking_Repository;
use Bookingistic\Database\Customer_Repository;
use Bookingistic\Database\Service_Repository;

defined( 'ABSPATH' ) || exit;

class Customers_Page {

	public static function render(): void {
		$view_id = (int) ( $_GET['view'] ?? 0 );
		if ( $view_id ) {
			self::render_detail( $view_id );
			return;
		}
		self::render_list();
	}

	private static function render_list(): void {
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
				<th></th>
			</tr></thead><tbody>';
		foreach ( $result['items'] as $c ) {
			$view_url = admin_url( 'admin.php?page=bookingistic-customers&view=' . (int) $c['id'] );
			echo '<tr>
				<td><a href="' . esc_url( $view_url ) . '"><strong>' . esc_html( $c['full_name'] ?: trim( $c['first_name'] . ' ' . $c['last_name'] ) ) . '</strong></a></td>
				<td>' . esc_html( $c['email'] ) . '</td>
				<td>' . esc_html( $c['phone'] ) . '</td>
				<td>' . esc_html( $c['source'] ) . '</td>
				<td>' . esc_html( $c['created_at'] ) . '</td>
				<td><a class="button" href="' . esc_url( $view_url ) . '">' . esc_html__( 'Open', 'bookingistic' ) . '</a></td>
			</tr>';
		}
		echo '</tbody></table></div>';
	}

	private static function render_detail( int $id ): void {
		$customer = Customer_Repository::find( $id );
		if ( ! $customer ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Customer not found.', 'bookingistic' ) . '</h1></div>';
			return;
		}

		$result   = Booking_Repository::query( [ 'customer_id' => $id, 'per_page' => 100, 'order' => 'DESC', 'orderby' => 'created_at' ] );
		$bookings = $result['items'];

		$stats = [
			'total'     => count( $bookings ),
			'pending'   => 0,
			'confirmed' => 0,
			'completed' => 0,
			'cancelled' => 0,
			'revenue'   => 0.0,
		];
		foreach ( $bookings as $b ) {
			if ( isset( $stats[ $b['status'] ] ) ) {
				$stats[ $b['status'] ]++;
			}
			if ( in_array( $b['payment_status'], [ 'paid', 'deposit_paid' ], true ) ) {
				$stats['revenue'] += (float) $b['payment_amount'];
			}
		}

		include BOOKINGISTIC_DIR . 'templates/admin/customer-detail.php';
	}
}
