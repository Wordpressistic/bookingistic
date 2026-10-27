<?php
/**
 * Email logs.
 *
 * @var array  $rows
 * @var array  $automations
 * @var string $status        Active status filter (from Email_Logs_Page).
 * @var int    $automation_id Active automation filter (from Email_Logs_Page).
 * @var int    $total
 * @var int    $page
 * @var int    $pages
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;

$bookingistic_lookup = [];
foreach ( $automations as $bookingistic_automation ) {
	$bookingistic_lookup[ (int) $bookingistic_automation['id'] ] = $bookingistic_automation['name'];
}
?>
<div class="wrap bookingistic-wrap">
	<header class="bookingistic-header">
		<h1><?php esc_html_e( 'Email Logs', 'bookingistic' ); ?><span class="bookingistic-header__badges"><span class="bookingistic-header__badge"><?php echo esc_html( number_format_i18n( $total ) ); ?></span></span></h1>
		<p class="bookingistic-header__tagline"><?php esc_html_e( 'Every automated email Bookingistic attempted to send.', 'bookingistic' ); ?></p>
	</header>

	<form method="get" class="bookingistic-calendar__filters" style="margin-top:6px">
		<input type="hidden" name="page" value="bookingistic-email-logs">
		<label>
			<span><?php esc_html_e( 'Status', 'bookingistic' ); ?></span>
			<select name="status">
				<option value=""><?php esc_html_e( 'All', 'bookingistic' ); ?></option>
				<option value="sent"   <?php selected( $status, 'sent' ); ?>><?php esc_html_e( 'Sent', 'bookingistic' ); ?></option>
				<option value="failed" <?php selected( $status, 'failed' ); ?>><?php esc_html_e( 'Failed', 'bookingistic' ); ?></option>
			</select>
		</label>
		<label>
			<span><?php esc_html_e( 'Automation', 'bookingistic' ); ?></span>
			<select name="automation_id">
				<option value="0"><?php esc_html_e( 'All', 'bookingistic' ); ?></option>
				<?php foreach ( $automations as $bookingistic_automation ) : ?>
					<option value="<?php echo (int) $bookingistic_automation['id']; ?>" <?php selected( $automation_id, (int) $bookingistic_automation['id'] ); ?>><?php echo esc_html( $bookingistic_automation['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<button type="submit" class="button"><?php esc_html_e( 'Filter', 'bookingistic' ); ?></button>
	</form>

	<?php if ( empty( $rows ) ) : ?>
		<div class="bookingistic-card">
			<div class="bookingistic-empty">
				<span class="dashicons dashicons-email-alt"></span>
				<p><?php esc_html_e( 'No emails sent yet.', 'bookingistic' ); ?></p>
			</div>
		</div>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<div class="bookingistic-card bookingistic-card--flush">
	<table class="widefat striped">
		<thead><tr>
			<th><?php esc_html_e( 'When', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Recipient', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Subject', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Automation', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Booking', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Status', 'bookingistic' ); ?></th>
		</tr></thead>
		<tbody>
			<?php foreach ( $rows as $bookingistic_log_row ) : ?>
				<tr>
					<td><?php echo esc_html( $bookingistic_log_row['created_at'] ); ?></td>
					<td><?php echo esc_html( $bookingistic_log_row['recipient_email'] ); ?></td>
					<td><?php echo esc_html( $bookingistic_log_row['subject'] ); ?></td>
					<td><?php echo esc_html( $bookingistic_lookup[ (int) $bookingistic_log_row['automation_id'] ] ?? '—' ); ?></td>
					<td>
						<?php if ( $bookingistic_log_row['booking_id'] ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-bookings&view=' . (int) $bookingistic_log_row['booking_id'] ) ); ?>">#<?php echo (int) $bookingistic_log_row['booking_id']; ?></a>
						<?php else : ?>—<?php endif; ?>
					</td>
					<td>
						<?php if ( $bookingistic_log_row['status'] === 'sent' ) : ?>
							<span class="bookingistic-pill bookingistic-pill--confirmed">sent</span>
						<?php else : ?>
							<span class="bookingistic-pill bookingistic-pill--cancelled" title="<?php echo esc_attr( $bookingistic_log_row['error_message'] ); ?>">failed</span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( $pages > 1 ) : ?>
		<div class="tablenav"><div class="tablenav-pages">
			<?php
			echo wp_kses_post(
				paginate_links(
				[
					'base'    => add_query_arg( 'paged', '%#%' ),
					'current' => $page,
					'total'   => $pages,
				]
				)
			);
			?>
		</div></div>
	<?php endif; ?>
	</div>
</div>
