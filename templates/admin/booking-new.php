<?php
/**
 * Admin manual booking creation form.
 *
 * Picks a service, queries /availability for an admin-selected date, and creates
 * a real booking through Booking_Manager so all hooks (emails, automations) fire.
 *
 * @var array $services
 * @var array $staff
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap bookingistic-wrap">
	<h1><?php esc_html_e( 'New booking', 'bookingistic' ); ?></h1>

	<?php if ( ! empty( $_GET['error'] ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( wp_unslash( $_GET['error'] ) ); ?></p></div>
	<?php endif; ?>

	<form method="post" data-bookingistic-manual>
		<?php wp_nonce_field( 'bookingistic_admin_booking_new' ); ?>
		<input type="hidden" name="bookingistic_admin_action" value="booking_create">
		<input type="hidden" name="timezone" value="<?php echo esc_attr( wp_timezone_string() ); ?>">

		<h2><?php esc_html_e( 'Service & slot', 'bookingistic' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label><?php esc_html_e( 'Service', 'bookingistic' ); ?></label></th>
				<td>
					<select name="service_id" required>
						<option value=""><?php esc_html_e( '— Pick a service —', 'bookingistic' ); ?></option>
						<?php foreach ( $services as $s ) : ?>
							<option value="<?php echo (int) $s['id']; ?>"><?php echo esc_html( $s['name'] . ' · ' . $s['duration_minutes'] . ' min' ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<?php if ( ! empty( $staff ) ) : ?>
				<tr>
					<th><label><?php esc_html_e( 'Staff', 'bookingistic' ); ?></label></th>
					<td>
						<select name="staff_id">
							<option value="0"><?php esc_html_e( 'Any / default host', 'bookingistic' ); ?></option>
							<?php foreach ( $staff as $s ) : ?>
								<option value="<?php echo (int) $s['id']; ?>"><?php echo esc_html( $s['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			<?php endif; ?>
			<tr>
				<th><label><?php esc_html_e( 'Date', 'bookingistic' ); ?></label></th>
				<td><input type="date" name="booking_date" required></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Available slots', 'bookingistic' ); ?></label></th>
				<td>
					<select name="start_utc" required>
						<option value=""><?php esc_html_e( '— Pick service + date first —', 'bookingistic' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Times shown in your browser timezone; saved as UTC.', 'bookingistic' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Customer', 'bookingistic' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label><?php esc_html_e( 'First name', 'bookingistic' ); ?></label></th>
				<td><input type="text" name="first_name" class="regular-text" required></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Last name', 'bookingistic' ); ?></label></th>
				<td><input type="text" name="last_name" class="regular-text"></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Email', 'bookingistic' ); ?></label></th>
				<td><input type="email" name="email" class="regular-text" required></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Phone', 'bookingistic' ); ?></label></th>
				<td><input type="tel" name="phone" class="regular-text"></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Company', 'bookingistic' ); ?></label></th>
				<td><input type="text" name="company" class="regular-text"></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Notes', 'bookingistic' ); ?></label></th>
				<td><textarea name="notes" rows="3" class="large-text"></textarea></td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Create booking', 'bookingistic' ); ?></button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-bookings' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'bookingistic' ); ?></a>
		</p>
	</form>
</div>
