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

		<h2><?php esc_html_e( 'Holidays', 'bookingistic' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Dates the whole business is closed. Availability returns zero slots on any date in these ranges, regardless of staff.', 'bookingistic' ); ?></p>
		<table class="widefat" style="max-width:620px" data-bookingistic-daterange="holidays">
			<thead><tr>
				<th><?php esc_html_e( 'From', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'To', 'bookingistic' ); ?></th>
				<th></th>
			</tr></thead>
			<tbody data-bookingistic-daterange-rows>
				<?php
				$holiday_rows = $settings['holidays'] ?? [];
				if ( empty( $holiday_rows ) ) {
					$holiday_rows[] = [ 'from' => '', 'to' => '' ];
				}
				foreach ( $holiday_rows as $i => $entry ) :
					$from = is_array( $entry ) ? ( $entry['from'] ?? '' ) : $entry;
					$to   = is_array( $entry ) ? ( $entry['to'] ?? '' ) : $entry;
					?>
					<tr>
						<td><input type="date" name="holidays[<?php echo (int) $i; ?>][from]" value="<?php echo esc_attr( $from ); ?>"></td>
						<td><input type="date" name="holidays[<?php echo (int) $i; ?>][to]"   value="<?php echo esc_attr( $to ); ?>"></td>
						<td><button type="button" class="button-link-delete" data-bookingistic-daterange-remove>×</button></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot><tr><td colspan="3"><button type="button" class="button" data-bookingistic-daterange-add><?php esc_html_e( 'Add date range', 'bookingistic' ); ?></button></td></tr></tfoot>
		</table>

		<h2><?php esc_html_e( 'White label & branding', 'bookingistic' ); ?></h2>
		<?php
		$has_license = ! empty( $settings['premium_license_active'] );
		$opted_in    = ! empty( $settings['remove_branding'] );
		$can_remove  = \Bookingistic\Email\Branding_Manager::can_remove_branding();
		?>
		<table class="form-table">
			<tr>
				<th><label><?php esc_html_e( 'Premium license active', 'bookingistic' ); ?></label></th>
				<td>
					<label>
						<input type="checkbox" name="premium_license_active" value="1" <?php checked( $has_license ); ?>>
						<?php esc_html_e( 'Mark this site as premium-licensed.', 'bookingistic' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Wire your real license check via the bookingistic_can_remove_branding filter for production. This checkbox is the manual fallback.', 'bookingistic' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Remove WordPressistic branding footer', 'bookingistic' ); ?></label></th>
				<td>
					<label>
						<input type="checkbox" name="remove_branding" value="1" <?php checked( $opted_in ); ?> <?php disabled( ! $has_license ); ?>>
						<?php esc_html_e( 'Hide the "Powered by Bookingistic by WordPressistic" footer on every automated email.', 'bookingistic' ); ?>
					</label>
					<p class="description">
						<?php if ( $can_remove ) : ?>
							<span style="color:#065f46"><?php esc_html_e( 'Currently: WordPressistic branding is removed from automated emails.', 'bookingistic' ); ?></span>
						<?php else : ?>
							<span style="color:#92400e"><?php esc_html_e( 'Currently: WordPressistic branding is included on every automated email.', 'bookingistic' ); ?></span>
						<?php endif; ?>
					</p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'bookingistic' ); ?></button>
		</p>
	</form>
</div>
