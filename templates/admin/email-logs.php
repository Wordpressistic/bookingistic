<?php
/**
 * Email logs.
 *
 * @var array $rows
 * @var array $automations
 * @var int   $total
 * @var int   $page
 * @var int   $pages
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;

$status        = sanitize_key( $_GET['status'] ?? '' );
$automation_id = (int) ( $_GET['automation_id'] ?? 0 );
$lookup        = [];
foreach ( $automations as $a ) {
	$lookup[ (int) $a['id'] ] = $a['name'];
}
?>
<div class="wrap bookingistic-wrap">
	<h1><?php esc_html_e( 'Email logs', 'bookingistic' ); ?></h1>

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
				<?php foreach ( $automations as $a ) : ?>
					<option value="<?php echo (int) $a['id']; ?>" <?php selected( $automation_id, (int) $a['id'] ); ?>><?php echo esc_html( $a['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<button type="submit" class="button"><?php esc_html_e( 'Filter', 'bookingistic' ); ?></button>
	</form>

	<?php if ( empty( $rows ) ) : ?>
		<div class="bookingistic-card bookingistic-card--muted"><p><?php esc_html_e( 'No emails sent yet.', 'bookingistic' ); ?></p></div>
		</div>
		<?php return; ?>
	<?php endif; ?>

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
			<?php foreach ( $rows as $r ) : ?>
				<tr>
					<td><?php echo esc_html( $r['created_at'] ); ?></td>
					<td><?php echo esc_html( $r['recipient_email'] ); ?></td>
					<td><?php echo esc_html( $r['subject'] ); ?></td>
					<td><?php echo esc_html( $lookup[ (int) $r['automation_id'] ] ?? '—' ); ?></td>
					<td>
						<?php if ( $r['booking_id'] ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-bookings&view=' . (int) $r['booking_id'] ) ); ?>">#<?php echo (int) $r['booking_id']; ?></a>
						<?php else : ?>—<?php endif; ?>
					</td>
					<td>
						<?php if ( $r['status'] === 'sent' ) : ?>
							<span class="bookingistic-pill bookingistic-pill--confirmed">sent</span>
						<?php else : ?>
							<span class="bookingistic-pill bookingistic-pill--cancelled" title="<?php echo esc_attr( $r['error_message'] ); ?>">failed</span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( $pages > 1 ) : ?>
		<div class="tablenav"><div class="tablenav-pages">
			<?php
			echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				[
					'base'    => add_query_arg( 'paged', '%#%' ),
					'current' => $page,
					'total'   => $pages,
				]
			);
			?>
		</div></div>
	<?php endif; ?>
</div>
