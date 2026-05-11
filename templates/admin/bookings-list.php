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

$current_status   = sanitize_key( $_GET['status'] ?? '' );
$current_service  = (int) ( $_GET['service_id'] ?? 0 );
$current_staff    = (int) ( $_GET['staff_id'] ?? 0 );
$current_payment  = sanitize_key( $_GET['payment_status'] ?? '' );
?>
<div class="wrap bookingistic-wrap">
	<h1>
		<?php esc_html_e( 'Bookings', 'bookingistic' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-bookings&new=1' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add new', 'bookingistic' ); ?></a>
	</h1>

	<ul class="subsubsub bookingistic-filters">
		<?php
		$statuses = [
			''           => __( 'All', 'bookingistic' ),
			'pending'    => __( 'Pending', 'bookingistic' ),
			'confirmed'  => __( 'Confirmed', 'bookingistic' ),
			'completed'  => __( 'Completed', 'bookingistic' ),
			'cancelled'  => __( 'Cancelled', 'bookingistic' ),
			'no_show'    => __( 'No show', 'bookingistic' ),
		];
		foreach ( $statuses as $k => $label ) :
			$url = add_query_arg(
				array_filter( [
					'page'           => 'bookingistic-bookings',
					'status'         => $k,
					'service_id'     => $current_service ?: null,
					'staff_id'       => $current_staff ?: null,
					'payment_status' => $current_payment ?: null,
				] ),
				admin_url( 'admin.php' )
			);
			$is  = $current_status === $k ? 'current' : '';
			?>
			<li><a class="<?php echo esc_attr( $is ); ?>" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></li>
		<?php endforeach; ?>
	</ul>

	<form method="get" class="bookingistic-calendar__filters" style="margin-top:6px">
		<input type="hidden" name="page" value="bookingistic-bookings">
		<input type="hidden" name="status" value="<?php echo esc_attr( $current_status ); ?>">
		<label>
			<span><?php esc_html_e( 'Service', 'bookingistic' ); ?></span>
			<select name="service_id">
				<option value=""><?php esc_html_e( 'All', 'bookingistic' ); ?></option>
				<?php foreach ( $services as $s ) : ?>
					<option value="<?php echo (int) $s['id']; ?>" <?php selected( $current_service, (int) $s['id'] ); ?>><?php echo esc_html( $s['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<span><?php esc_html_e( 'Staff', 'bookingistic' ); ?></span>
			<select name="staff_id">
				<option value=""><?php esc_html_e( 'All', 'bookingistic' ); ?></option>
				<?php foreach ( $staff as $s ) : ?>
					<option value="<?php echo (int) $s['id']; ?>" <?php selected( $current_staff, (int) $s['id'] ); ?>><?php echo esc_html( $s['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<span><?php esc_html_e( 'Payment', 'bookingistic' ); ?></span>
			<select name="payment_status">
				<option value=""><?php esc_html_e( 'All', 'bookingistic' ); ?></option>
				<?php foreach ( [ 'free', 'pending', 'paid', 'deposit_paid', 'failed', 'refunded', 'pay_later' ] as $ps ) : ?>
					<option value="<?php echo esc_attr( $ps ); ?>" <?php selected( $current_payment, $ps ); ?>><?php echo esc_html( $ps ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<button type="submit" class="button"><?php esc_html_e( 'Filter', 'bookingistic' ); ?></button>
	</form>

	<?php if ( empty( $rows ) ) : ?>
		<div class="bookingistic-card bookingistic-card--muted"><p><?php esc_html_e( 'No bookings yet.', 'bookingistic' ); ?></p></div>
		</div>
		<?php return; ?>
	<?php endif; ?>

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
			<?php foreach ( $rows as $b ) : ?>
				<tr>
					<td>#<?php echo (int) $b['id']; ?></td>
					<td><?php echo esc_html( $b['service']['name'] ?? '—' ); ?></td>
					<td>
						<strong><?php echo esc_html( $b['customer']['full_name'] ?? '—' ); ?></strong><br>
						<a href="mailto:<?php echo esc_attr( $b['customer']['email'] ?? '' ); ?>"><?php echo esc_html( $b['customer']['email'] ?? '' ); ?></a>
					</td>
					<td>
						<?php
						if ( $b['start_datetime'] ) {
							echo esc_html( $b['start_datetime'] . ' UTC' );
						} else {
							esc_html_e( 'Lead (no slot)', 'bookingistic' );
						}
						?>
					</td>
					<td><span class="bookingistic-pill bookingistic-pill--<?php echo esc_attr( $b['status'] ); ?>"><?php echo esc_html( $b['status'] ); ?></span></td>
					<td><?php echo esc_html( $b['payment_status'] ); ?></td>
					<td>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-bookings&view=' . (int) $b['id'] ) ); ?>"><?php esc_html_e( 'Open', 'bookingistic' ); ?></a>
						<form method="post" style="display:inline">
							<?php wp_nonce_field( 'bookingistic_admin_booking' ); ?>
							<input type="hidden" name="bookingistic_admin_action" value="booking_status">
							<input type="hidden" name="booking_id" value="<?php echo (int) $b['id']; ?>">
							<select name="status">
								<option value="pending"   <?php selected( $b['status'], 'pending' ); ?>><?php esc_html_e( 'Pending', 'bookingistic' ); ?></option>
								<option value="confirmed" <?php selected( $b['status'], 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'bookingistic' ); ?></option>
								<option value="completed" <?php selected( $b['status'], 'completed' ); ?>><?php esc_html_e( 'Completed', 'bookingistic' ); ?></option>
								<option value="cancelled" <?php selected( $b['status'], 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'bookingistic' ); ?></option>
								<option value="no_show"   <?php selected( $b['status'], 'no_show' ); ?>><?php esc_html_e( 'No show', 'bookingistic' ); ?></option>
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
					'current' => max( 1, (int) ( $_GET['paged'] ?? 1 ) ),
					'total'   => (int) $result['pages'],
				]
			);
			?>
		</div></div>
	<?php endif; ?>
</div>
