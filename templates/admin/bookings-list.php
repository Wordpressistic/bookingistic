<?php
/**
 * Admin bookings list template.
 *
 * @var array $result   query result
 * @var array $rows     normalized rows with service+customer joined
 * @var array $services
 * @var array $staff
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;

// Read-only display filters; state changes are nonce+capability checked in Bookings_Page::maybe_act().
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$bookingistic_status     = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$bookingistic_service_id = isset( $_GET['service_id'] ) ? absint( wp_unslash( $_GET['service_id'] ) ) : 0;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$bookingistic_staff_id   = isset( $_GET['staff_id'] ) ? absint( wp_unslash( $_GET['staff_id'] ) ) : 0;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$bookingistic_payment    = isset( $_GET['payment_status'] ) ? sanitize_key( wp_unslash( $_GET['payment_status'] ) ) : '';
?>
<div class="wrap bookingistic-wrap">
	<header class="bookingistic-header">
		<h1>
			<?php esc_html_e( 'Bookings', 'bookingistic' ); ?>
			<span class="bookingistic-header__badges"><span class="bookingistic-header__badge"><span class="dashicons dashicons-awards"></span><?php echo esc_html( number_format_i18n( $result['total'] ?? 0 ) ); ?></span></span>
			<span class="bookingistic-header__actions"><a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-bookings&new=1' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add new', 'bookingistic' ); ?></a></span>
		</h1>
		<p class="bookingistic-header__tagline"><?php esc_html_e( 'Every booking across all services — filter, reschedule, and manage statuses.', 'bookingistic' ); ?></p>
	</header>

	<ul class="subsubsub bookingistic-filters">
		<?php
		$bookingistic_statuses = [
			''           => __( 'All', 'bookingistic' ),
			'pending'    => __( 'Pending', 'bookingistic' ),
			'confirmed'  => __( 'Confirmed', 'bookingistic' ),
			'completed'  => __( 'Completed', 'bookingistic' ),
			'cancelled'  => __( 'Cancelled', 'bookingistic' ),
			'no_show'    => __( 'No show', 'bookingistic' ),
		];
		foreach ( $bookingistic_statuses as $bookingistic_status_key => $bookingistic_status_label ) :
			$bookingistic_url = add_query_arg(
				array_filter( [
					'page'           => 'bookingistic-bookings',
					'status'         => $bookingistic_status_key,
					'service_id'     => $bookingistic_service_id ?: null,
					'staff_id'       => $bookingistic_staff_id ?: null,
					'payment_status' => $bookingistic_payment ?: null,
				] ),
				admin_url( 'admin.php' )
			);
			$bookingistic_current_class = $bookingistic_status === $bookingistic_status_key ? 'current' : '';
			?>
			<li><a class="<?php echo esc_attr( $bookingistic_current_class ); ?>" href="<?php echo esc_url( $bookingistic_url ); ?>"><?php echo esc_html( $bookingistic_status_label ); ?></a></li>
		<?php endforeach; ?>
	</ul>

	<form method="get" class="bookingistic-calendar__filters" style="margin-top:6px">
		<input type="hidden" name="page" value="bookingistic-bookings">
		<input type="hidden" name="status" value="<?php echo esc_attr( $bookingistic_status ); ?>">
		<label>
			<span><?php esc_html_e( 'Service', 'bookingistic' ); ?></span>
			<select name="service_id">
				<option value=""><?php esc_html_e( 'All', 'bookingistic' ); ?></option>
				<?php foreach ( $services as $bookingistic_service_option ) : ?>
					<option value="<?php echo (int) $bookingistic_service_option['id']; ?>" <?php selected( $bookingistic_service_id, (int) $bookingistic_service_option['id'] ); ?>><?php echo esc_html( $bookingistic_service_option['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<span><?php esc_html_e( 'Staff', 'bookingistic' ); ?></span>
			<select name="staff_id">
				<option value=""><?php esc_html_e( 'All', 'bookingistic' ); ?></option>
				<?php foreach ( $staff as $bookingistic_staff_option ) : ?>
					<option value="<?php echo (int) $bookingistic_staff_option['id']; ?>" <?php selected( $bookingistic_staff_id, (int) $bookingistic_staff_option['id'] ); ?>><?php echo esc_html( $bookingistic_staff_option['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<span><?php esc_html_e( 'Payment', 'bookingistic' ); ?></span>
			<select name="payment_status">
				<option value=""><?php esc_html_e( 'All', 'bookingistic' ); ?></option>
				<?php foreach ( [ 'free', 'pending', 'paid', 'deposit_paid', 'failed', 'refunded', 'pay_later' ] as $bookingistic_pay_status ) : ?>
					<option value="<?php echo esc_attr( $bookingistic_pay_status ); ?>" <?php selected( $bookingistic_payment, $bookingistic_pay_status ); ?>><?php echo esc_html( $bookingistic_pay_status ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<button type="submit" class="button"><?php esc_html_e( 'Filter', 'bookingistic' ); ?></button>
	</form>

	<?php if ( empty( $rows ) ) : ?>
		<div class="bookingistic-card">
			<div class="bookingistic-empty">
				<span class="dashicons dashicons-calendar-alt"></span>
				<p><?php esc_html_e( 'No bookings yet.', 'bookingistic' ); ?></p>
			</div>
		</div>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<div class="bookingistic-card bookingistic-card--flush">
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'Service', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'Customer', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'When', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'Status', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'Payment', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'bookingistic' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $rows as $bookingistic_booking ) : ?>
				<tr>
					<td>#<?php echo (int) $bookingistic_booking['id']; ?></td>
					<td><?php echo esc_html( $bookingistic_booking['service']['name'] ?? '—' ); ?></td>
					<td>
						<strong><?php echo esc_html( $bookingistic_booking['customer']['full_name'] ?? '—' ); ?></strong><br>
						<a href="mailto:<?php echo esc_attr( $bookingistic_booking['customer']['email'] ?? '' ); ?>"><?php echo esc_html( $bookingistic_booking['customer']['email'] ?? '' ); ?></a>
					</td>
					<td>
						<?php
						if ( $bookingistic_booking['start_datetime'] ) {
							echo esc_html( $bookingistic_booking['start_datetime'] . ' UTC' );
						} else {
							esc_html_e( 'Lead (no slot)', 'bookingistic' );
						}
						?>
					</td>
					<td><span class="bookingistic-pill bookingistic-pill--<?php echo esc_attr( $bookingistic_booking['status'] ); ?>"><?php echo esc_html( $bookingistic_booking['status'] ); ?></span></td>
					<td><?php echo esc_html( $bookingistic_booking['payment_status'] ); ?></td>
					<td>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-bookings&view=' . (int) $bookingistic_booking['id'] ) ); ?>"><?php esc_html_e( 'Open', 'bookingistic' ); ?></a>
						<form method="post" style="display:inline">
							<?php wp_nonce_field( 'bookingistic_admin_booking' ); ?>
							<input type="hidden" name="bookingistic_admin_action" value="booking_status">
							<input type="hidden" name="booking_id" value="<?php echo (int) $bookingistic_booking['id']; ?>">
							<select name="status">
								<option value="pending"   <?php selected( $bookingistic_booking['status'], 'pending' ); ?>><?php esc_html_e( 'Pending', 'bookingistic' ); ?></option>
								<option value="confirmed" <?php selected( $bookingistic_booking['status'], 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'bookingistic' ); ?></option>
								<option value="completed" <?php selected( $bookingistic_booking['status'], 'completed' ); ?>><?php esc_html_e( 'Completed', 'bookingistic' ); ?></option>
								<option value="cancelled" <?php selected( $bookingistic_booking['status'], 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'bookingistic' ); ?></option>
								<option value="no_show"   <?php selected( $bookingistic_booking['status'], 'no_show' ); ?>><?php esc_html_e( 'No show', 'bookingistic' ); ?></option>
							</select>
							<button type="submit" class="button"><?php esc_html_e( 'Update', 'bookingistic' ); ?></button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( ! empty( $result['pages'] ) && $result['pages'] > 1 ) : ?>
		<div class="tablenav"><div class="tablenav-pages">
			<?php
			echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				[
					'base'    => add_query_arg( 'paged', '%#%' ),
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pagination.
					'current' => isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1,
					'total'   => (int) $result['pages'],
				]
			);
				?>
			</div></div>
		<?php endif; ?>
	</div>
</div>
