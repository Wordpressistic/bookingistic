<?php
/**
 * Admin booking detail.
 *
 * @var array       $booking
 * @var array|null  $service
 * @var array|null  $customer
 * @var array|null  $staff
 * @var array       $services
 * @var array       $staff_list
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;

$has_slot = ! empty( $booking['start_datetime'] );
?>
<div class="wrap bookingistic-wrap">
	<h1>
		<?php
		/* translators: %d booking id */
		echo esc_html( sprintf( __( 'Booking #%d', 'bookingistic' ), (int) $booking['id'] ) );
		?>
		<span class="bookingistic-pill bookingistic-pill--<?php echo esc_attr( $booking['status'] ); ?>"><?php echo esc_html( $booking['status'] ); ?></span>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-bookings' ) ); ?>" class="page-title-action"><?php esc_html_e( '← Back', 'bookingistic' ); ?></a>
	</h1>

	<?php if ( ! empty( $_GET['rescheduled'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Booking rescheduled.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['error'] ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( wp_unslash( $_GET['error'] ) ); ?></p></div>
	<?php endif; ?>

	<div class="bookingistic-detail-grid">
		<div>
			<div class="bookingistic-card">
				<h2><?php esc_html_e( 'Summary', 'bookingistic' ); ?></h2>
				<dl class="bookingistic-detail">
					<dt><?php esc_html_e( 'Service', 'bookingistic' ); ?></dt>
					<dd><?php echo esc_html( $service['name'] ?? '—' ); ?></dd>
					<dt><?php esc_html_e( 'When', 'bookingistic' ); ?></dt>
					<dd><?php echo $has_slot ? esc_html( $booking['start_datetime'] . ' → ' . $booking['end_datetime'] . ' UTC' ) : esc_html__( 'Lead (no slot)', 'bookingistic' ); ?></dd>
					<dt><?php esc_html_e( 'Timezone', 'bookingistic' ); ?></dt>
					<dd><?php echo esc_html( $booking['timezone'] ?? '—' ); ?></dd>
					<dt><?php esc_html_e( 'Staff', 'bookingistic' ); ?></dt>
					<dd><?php echo esc_html( $staff['name'] ?? __( 'Any / default host', 'bookingistic' ) ); ?></dd>
					<dt><?php esc_html_e( 'Payment', 'bookingistic' ); ?></dt>
					<dd><?php echo esc_html( $booking['payment_status'] . ' · ' . number_format_i18n( (float) $booking['payment_amount'], 2 ) ); ?></dd>
					<dt><?php esc_html_e( 'Source', 'bookingistic' ); ?></dt>
					<dd><?php echo esc_html( $booking['source'] ?? '—' ); ?></dd>
					<dt><?php esc_html_e( 'Customer notes', 'bookingistic' ); ?></dt>
					<dd><?php echo esc_html( $booking['notes'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Created', 'bookingistic' ); ?></dt>
					<dd><?php echo esc_html( $booking['created_at'] ); ?></dd>
				</dl>
			</div>

			<div class="bookingistic-card" style="margin-top:16px">
				<h2><?php esc_html_e( 'Internal notes', 'bookingistic' ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'bookingistic_admin_booking_notes' ); ?>
					<input type="hidden" name="bookingistic_admin_action" value="booking_notes">
					<input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
					<textarea name="internal_notes" rows="4" class="large-text"><?php echo esc_textarea( $booking['internal_notes'] ?? '' ); ?></textarea>
					<p><button class="button button-primary" type="submit"><?php esc_html_e( 'Save notes', 'bookingistic' ); ?></button></p>
				</form>
			</div>

			<?php if ( $has_slot ) : ?>
				<div class="bookingistic-card" style="margin-top:16px">
					<h2><?php esc_html_e( 'Reschedule', 'bookingistic' ); ?></h2>
					<form method="post">
						<?php wp_nonce_field( 'bookingistic_admin_booking_reschedule' ); ?>
						<input type="hidden" name="bookingistic_admin_action" value="booking_reschedule">
						<input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
						<p>
							<label><?php esc_html_e( 'New start (UTC, format Y-m-d H:i:s)', 'bookingistic' ); ?><br>
								<input type="text" name="start_utc" class="regular-text" placeholder="2026-05-15 14:00:00" required>
							</label>
						</p>
						<?php if ( ! empty( $staff_list ) ) : ?>
							<p>
								<label><?php esc_html_e( 'Staff', 'bookingistic' ); ?><br>
									<select name="staff_id">
										<option value="0"><?php esc_html_e( 'Any / default host', 'bookingistic' ); ?></option>
										<?php foreach ( $staff_list as $s ) : ?>
											<option value="<?php echo (int) $s['id']; ?>" <?php selected( (int) $booking['staff_id'], (int) $s['id'] ); ?>><?php echo esc_html( $s['name'] ); ?></option>
										<?php endforeach; ?>
									</select>
								</label>
							</p>
						<?php endif; ?>
						<p><button class="button button-primary" type="submit"><?php esc_html_e( 'Reschedule', 'bookingistic' ); ?></button></p>
					</form>
				</div>
			<?php endif; ?>
		</div>

		<aside>
			<div class="bookingistic-card">
				<h2><?php esc_html_e( 'Customer', 'bookingistic' ); ?></h2>
				<?php if ( $customer ) : ?>
					<p>
						<strong><?php echo esc_html( $customer['full_name'] ); ?></strong><br>
						<a href="mailto:<?php echo esc_attr( $customer['email'] ); ?>"><?php echo esc_html( $customer['email'] ); ?></a>
						<?php if ( ! empty( $customer['phone'] ) ) : ?>
							<br><?php echo esc_html( $customer['phone'] ); ?>
						<?php endif; ?>
					</p>
					<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-customers&view=' . (int) $customer['id'] ) ); ?>"><?php esc_html_e( 'Open customer profile', 'bookingistic' ); ?></a></p>
				<?php else : ?>
					<p class="bookingistic-muted"><?php esc_html_e( 'No customer linked.', 'bookingistic' ); ?></p>
				<?php endif; ?>
			</div>

			<div class="bookingistic-card" style="margin-top:16px">
				<h2><?php esc_html_e( 'Quick actions', 'bookingistic' ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'bookingistic_admin_booking' ); ?>
					<input type="hidden" name="bookingistic_admin_action" value="booking_status">
					<input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
					<p>
						<select name="status">
							<option value="pending"   <?php selected( $booking['status'], 'pending' ); ?>><?php esc_html_e( 'Pending', 'bookingistic' ); ?></option>
							<option value="confirmed" <?php selected( $booking['status'], 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'bookingistic' ); ?></option>
							<option value="completed" <?php selected( $booking['status'], 'completed' ); ?>><?php esc_html_e( 'Completed', 'bookingistic' ); ?></option>
							<option value="cancelled" <?php selected( $booking['status'], 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'bookingistic' ); ?></option>
							<option value="no_show"   <?php selected( $booking['status'], 'no_show' ); ?>><?php esc_html_e( 'No show', 'bookingistic' ); ?></option>
						</select>
						<button class="button button-primary" type="submit"><?php esc_html_e( 'Update status', 'bookingistic' ); ?></button>
					</p>
				</form>

				<?php if ( $booking['status'] !== 'cancelled' ) : ?>
					<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Cancel this booking?', 'bookingistic' ) ); ?>')">
						<?php wp_nonce_field( 'bookingistic_admin_booking' ); ?>
						<input type="hidden" name="bookingistic_admin_action" value="booking_cancel">
						<input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
						<p><button class="button" type="submit"><?php esc_html_e( 'Cancel booking', 'bookingistic' ); ?></button></p>
					</form>
				<?php endif; ?>
			</div>
		</aside>
	</div>
</div>
