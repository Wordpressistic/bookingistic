<?php
/**
 * Email automations list.
 *
 * @var array $rules
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap bookingistic-wrap">
	<h1>
		<?php esc_html_e( 'Email Automations', 'bookingistic' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-emails&new=1' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add new', 'bookingistic' ); ?></a>
	</h1>

	<?php if ( ! empty( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Automation deleted.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>

	<p class="bookingistic-muted">
		<?php esc_html_e( 'Each rule fires when its trigger event runs (or, for reminders, when the cron tick finds a booking inside the delay window). Service-specific rules override the generic rule for that service.', 'bookingistic' ); ?>
	</p>

	<table class="widefat striped">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Trigger', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Recipient', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Delay', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Scope', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Status', 'bookingistic' ); ?></th>
			<th></th>
		</tr></thead>
		<tbody>
			<?php foreach ( $rules as $r ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $r['name'] ); ?></strong></td>
					<td><code><?php echo esc_html( $r['trigger_event'] ); ?></code></td>
					<td><?php echo esc_html( $r['recipient_type'] . ( $r['recipient_custom'] ? ' (' . $r['recipient_custom'] . ')' : '' ) ); ?></td>
					<td><?php echo esc_html( $r['delay_amount'] . ' ' . $r['delay_unit'] ); ?></td>
					<td><?php echo $r['service_id'] ? esc_html__( 'Service-specific', 'bookingistic' ) : esc_html__( 'All services', 'bookingistic' ); ?></td>
					<td><span class="bookingistic-pill bookingistic-pill--<?php echo $r['status'] === 'active' ? 'confirmed' : 'cancelled'; ?>"><?php echo esc_html( $r['status'] ); ?></span></td>
					<td>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-emails&edit=' . (int) $r['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'bookingistic' ); ?></a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
