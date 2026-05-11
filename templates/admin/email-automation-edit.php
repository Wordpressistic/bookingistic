<?php
/**
 * Email automation editor.
 *
 * @var array|null $rule
 * @var array      $services
 * @var array      $variables
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;

use Bookingistic\Admin\Email_Automations_Page;

$is_new = empty( $rule );
$rule   = $rule ?: [
	'id'               => 0,
	'name'             => '',
	'trigger_event'    => 'booking_confirmed',
	'recipient_type'   => 'customer',
	'recipient_custom' => '',
	'delay_amount'     => 0,
	'delay_unit'       => 'minutes',
	'subject'          => '',
	'body'             => '',
	'service_id'       => 0,
	'status'           => 'active',
];
?>
<div class="wrap bookingistic-wrap">
	<h1>
		<?php echo $is_new ? esc_html__( 'New automation', 'bookingistic' ) : esc_html__( 'Edit automation', 'bookingistic' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-emails' ) ); ?>" class="page-title-action"><?php esc_html_e( '← Back', 'bookingistic' ); ?></a>
	</h1>

	<?php if ( ! empty( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Automation saved.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>

	<div class="bookingistic-detail-grid">
		<div>
			<form method="post" id="bookingistic-automation-form">
				<?php wp_nonce_field( 'bookingistic_admin_email_automation' ); ?>
				<input type="hidden" name="bookingistic_admin_action" value="save_email_automation">
				<input type="hidden" name="automation_id" value="<?php echo (int) $rule['id']; ?>">

				<table class="form-table">
					<tr><th><label><?php esc_html_e( 'Name', 'bookingistic' ); ?></label></th>
						<td><input type="text" name="name" class="regular-text" required value="<?php echo esc_attr( $rule['name'] ); ?>"></td></tr>

					<tr><th><label><?php esc_html_e( 'Trigger event', 'bookingistic' ); ?></label></th>
						<td>
							<select name="trigger_event" required>
								<?php foreach ( Email_Automations_Page::TRIGGERS as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $rule['trigger_event'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td></tr>

					<tr><th><label><?php esc_html_e( 'Recipient', 'bookingistic' ); ?></label></th>
						<td>
							<select name="recipient_type">
								<?php foreach ( Email_Automations_Page::RECIPIENTS as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $rule['recipient_type'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<input type="email" name="recipient_custom" placeholder="<?php esc_attr_e( 'Custom email (only when recipient = custom)', 'bookingistic' ); ?>" value="<?php echo esc_attr( $rule['recipient_custom'] ); ?>" style="width:260px;margin-left:8px">
						</td></tr>

					<tr><th><label><?php esc_html_e( 'Delay', 'bookingistic' ); ?></label></th>
						<td>
							<input type="number" name="delay_amount" min="0" value="<?php echo esc_attr( $rule['delay_amount'] ); ?>" style="width:90px">
							<select name="delay_unit">
								<option value="minutes" <?php selected( $rule['delay_unit'], 'minutes' ); ?>><?php esc_html_e( 'minutes', 'bookingistic' ); ?></option>
								<option value="hours"   <?php selected( $rule['delay_unit'], 'hours' ); ?>><?php esc_html_e( 'hours', 'bookingistic' ); ?></option>
								<option value="days"    <?php selected( $rule['delay_unit'], 'days' ); ?>><?php esc_html_e( 'days', 'bookingistic' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'For reminders, this is how far before the booking start to send.', 'bookingistic' ); ?></p>
						</td></tr>

					<tr><th><label><?php esc_html_e( 'Subject', 'bookingistic' ); ?></label></th>
						<td><input type="text" name="subject" class="large-text" required value="<?php echo esc_attr( $rule['subject'] ); ?>" placeholder="<?php esc_attr_e( 'Your {service_name} on {booking_date}', 'bookingistic' ); ?>"></td></tr>

					<tr><th><label><?php esc_html_e( 'Body', 'bookingistic' ); ?></label></th>
						<td>
							<textarea name="body" rows="14" class="large-text" id="bookingistic-automation-body"><?php echo esc_textarea( $rule['body'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'HTML allowed. Click any variable on the right to insert it.', 'bookingistic' ); ?></p>
						</td></tr>

					<tr><th><label><?php esc_html_e( 'Service scope', 'bookingistic' ); ?></label></th>
						<td>
							<select name="service_id">
								<option value="0"><?php esc_html_e( 'All services', 'bookingistic' ); ?></option>
								<?php foreach ( $services as $s ) : ?>
									<option value="<?php echo (int) $s['id']; ?>" <?php selected( (int) $rule['service_id'], (int) $s['id'] ); ?>><?php echo esc_html( $s['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Service-specific rules override the all-services rule for the same trigger.', 'bookingistic' ); ?></p>
						</td></tr>

					<tr><th><label><?php esc_html_e( 'Status', 'bookingistic' ); ?></label></th>
						<td>
							<select name="status">
								<option value="active"   <?php selected( $rule['status'], 'active' ); ?>><?php esc_html_e( 'Active', 'bookingistic' ); ?></option>
								<option value="inactive" <?php selected( $rule['status'], 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'bookingistic' ); ?></option>
							</select>
						</td></tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'bookingistic' ); ?></button>
					<?php if ( ! $is_new ) : ?>
						<button type="button"
							class="button button-secondary"
							data-bookingistic-test-send
							data-automation-id="<?php echo (int) $rule['id']; ?>">
							<?php esc_html_e( 'Send test email', 'bookingistic' ); ?>
						</button>
					<?php endif; ?>
				</p>
			</form>

			<?php if ( ! $is_new ) : ?>
				<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this automation? Default seeded ones will be re-created on next plugin activation.', 'bookingistic' ) ); ?>')">
					<?php wp_nonce_field( 'bookingistic_admin_email_automation_delete' ); ?>
					<input type="hidden" name="bookingistic_admin_action" value="delete_email_automation">
					<input type="hidden" name="automation_id" value="<?php echo (int) $rule['id']; ?>">
					<p><button type="submit" class="button button-link-delete"><?php esc_html_e( 'Delete automation', 'bookingistic' ); ?></button></p>
				</form>
			<?php endif; ?>
		</div>

		<aside>
			<div class="bookingistic-card">
				<h2><?php esc_html_e( 'Template variables', 'bookingistic' ); ?></h2>
				<p class="bookingistic-muted"><?php esc_html_e( 'Click any variable to insert it at the cursor.', 'bookingistic' ); ?></p>
				<div class="bookingistic-var-grid">
					<?php foreach ( $variables as $v ) : ?>
						<button type="button"
							class="bookingistic-var"
							data-bookingistic-var="{<?php echo esc_attr( $v ); ?>}"><code>{<?php echo esc_html( $v ); ?>}</code></button>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="bookingistic-card" style="margin-top:16px">
				<h2><?php esc_html_e( 'About this trigger', 'bookingistic' ); ?></h2>
				<p class="bookingistic-muted">
					<?php esc_html_e( 'Triggers fire on the bookingistic_booking_* action hooks. Reminder rules run on the bookingistic_send_reminders cron tick (hourly) and look at the delay window.', 'bookingistic' ); ?>
				</p>
			</div>
		</aside>
	</div>
</div>
