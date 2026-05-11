<?php
/**
 * Admin settings template.
 *
 * @var array $settings
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;
$days = [
	1 => __( 'Mon', 'bookingistic' ),
	2 => __( 'Tue', 'bookingistic' ),
	3 => __( 'Wed', 'bookingistic' ),
	4 => __( 'Thu', 'bookingistic' ),
	5 => __( 'Fri', 'bookingistic' ),
	6 => __( 'Sat', 'bookingistic' ),
	7 => __( 'Sun', 'bookingistic' ),
];
$active_days = array_map( 'intval', (array) ( $settings['working_days'] ?? [] ) );
?>
<div class="wrap bookingistic-wrap">
	<h1><?php esc_html_e( 'Settings', 'bookingistic' ); ?></h1>

	<?php if ( ! empty( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Settings saved.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'bookingistic_admin_settings' ); ?>
		<input type="hidden" name="bookingistic_admin_action" value="save_settings">

		<h2><?php esc_html_e( 'Business', 'bookingistic' ); ?></h2>
		<table class="form-table">
			<tr><th><label><?php esc_html_e( 'Business name', 'bookingistic' ); ?></label></th>
				<td><input type="text" name="business_name" class="regular-text" value="<?php echo esc_attr( $settings['business_name'] ?? '' ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Business email', 'bookingistic' ); ?></label></th>
				<td><input type="email" name="business_email" class="regular-text" value="<?php echo esc_attr( $settings['business_email'] ?? '' ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Business phone', 'bookingistic' ); ?></label></th>
				<td><input type="text" name="business_phone" class="regular-text" value="<?php echo esc_attr( $settings['business_phone'] ?? '' ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Business address', 'bookingistic' ); ?></label></th>
				<td><textarea name="business_address" class="large-text" rows="2"><?php echo esc_textarea( $settings['business_address'] ?? '' ); ?></textarea></td></tr>
			<tr><th><label><?php esc_html_e( 'Business logo URL', 'bookingistic' ); ?></label></th>
				<td><input type="url" name="logo_url" class="regular-text" value="<?php echo esc_attr( $settings['logo_url'] ?? '' ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Timezone', 'bookingistic' ); ?></label></th>
				<td><input type="text" name="timezone" class="regular-text" value="<?php echo esc_attr( $settings['timezone'] ?? wp_timezone_string() ); ?>"></td></tr>
		</table>

		<h2><?php esc_html_e( 'Email sender', 'bookingistic' ); ?></h2>
		<table class="form-table">
			<tr><th><label><?php esc_html_e( 'From name', 'bookingistic' ); ?></label></th>
				<td><input type="text" name="from_name" class="regular-text" value="<?php echo esc_attr( $settings['from_name'] ?? '' ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'From email', 'bookingistic' ); ?></label></th>
				<td><input type="email" name="from_email" class="regular-text" value="<?php echo esc_attr( $settings['from_email'] ?? '' ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Reply-To', 'bookingistic' ); ?></label></th>
				<td><input type="email" name="reply_to" class="regular-text" value="<?php echo esc_attr( $settings['reply_to'] ?? '' ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Admin notification email', 'bookingistic' ); ?></label></th>
				<td><input type="email" name="admin_notify" class="regular-text" value="<?php echo esc_attr( $settings['admin_notify'] ?? '' ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Staff notification email', 'bookingistic' ); ?></label></th>
				<td><input type="email" name="staff_notify" class="regular-text" value="<?php echo esc_attr( $settings['staff_notify'] ?? '' ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Footer text', 'bookingistic' ); ?></label></th>
				<td><textarea name="footer_text" class="large-text" rows="2"><?php echo esc_textarea( $settings['footer_text'] ?? '' ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Free tier emails always include the WordPressistic branding footer. Premium can remove it.', 'bookingistic' ); ?></p>
				</td></tr>
		</table>

		<h2><?php esc_html_e( 'Availability', 'bookingistic' ); ?></h2>
		<table class="form-table">
			<tr><th><label><?php esc_html_e( 'Minimum notice (hours)', 'bookingistic' ); ?></label></th>
				<td><input type="number" min="0" name="min_notice_hours" value="<?php echo esc_attr( $settings['min_notice_hours'] ?? 4 ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Maximum advance booking (days)', 'bookingistic' ); ?></label></th>
				<td><input type="number" min="1" name="max_advance_days" value="<?php echo esc_attr( $settings['max_advance_days'] ?? 60 ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Slot interval (min)', 'bookingistic' ); ?></label></th>
				<td><input type="number" min="5" name="slot_interval" value="<?php echo esc_attr( $settings['slot_interval'] ?? 15 ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Cancellation window (hrs)', 'bookingistic' ); ?></label></th>
				<td><input type="number" min="0" name="cancel_window_hrs" value="<?php echo esc_attr( $settings['cancel_window_hrs'] ?? 12 ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Reschedule window (hrs)', 'bookingistic' ); ?></label></th>
				<td><input type="number" min="0" name="reschedule_window_hrs" value="<?php echo esc_attr( $settings['reschedule_window_hrs'] ?? 24 ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Working hours', 'bookingistic' ); ?></label></th>
				<td>
					<input type="time" name="working_start" value="<?php echo esc_attr( $settings['working_start'] ?? '09:00' ); ?>">
					→
					<input type="time" name="working_end" value="<?php echo esc_attr( $settings['working_end'] ?? '17:00' ); ?>">
				</td></tr>
			<tr><th><label><?php esc_html_e( 'Working days', 'bookingistic' ); ?></label></th>
				<td>
					<?php foreach ( $days as $k => $label ) : ?>
						<label style="margin-right:8px">
							<input type="checkbox" name="working_days[]" value="<?php echo (int) $k; ?>" <?php checked( in_array( $k, $active_days, true ) ); ?>>
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</td></tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'bookingistic' ); ?></button>
		</p>
	</form>
</div>
