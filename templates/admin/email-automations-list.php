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
	<header class="bookingistic-header">
		<h1>
			<?php esc_html_e( 'Email Automations', 'bookingistic' ); ?>
			<span class="bookingistic-header__badges"><span class="bookingistic-header__badge"><?php echo esc_html( number_format_i18n( count( $rules ) ) ); ?></span></span>
			<span class="bookingistic-header__actions"><a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-emails&new=1' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add new', 'bookingistic' ); ?></a></span>
		</h1>
		<p class="bookingistic-header__tagline"><?php esc_html_e( 'Automated emails for every stage of the booking lifecycle.', 'bookingistic' ); ?></p>
	</header>

	<?php // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display notice below. ?>
	<?php if ( ! empty( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Automation deleted.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>
	<?php // phpcs:enable ?>

	<p class="bookingistic-muted" style="margin:0 0 14px">
		<?php esc_html_e( 'Each rule fires when its trigger event runs (or, for reminders, when the cron tick finds a booking inside the delay window). Service-specific rules override the generic rule for that service.', 'bookingistic' ); ?>
	</p>

	<div class="bookingistic-card bookingistic-card--flush">
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
			<?php foreach ( $rules as $bookingistic_rule ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $bookingistic_rule['name'] ); ?></strong></td>
					<td><code><?php echo esc_html( $bookingistic_rule['trigger_event'] ); ?></code></td>
					<td><?php echo esc_html( $bookingistic_rule['recipient_type'] . ( $bookingistic_rule['recipient_custom'] ? ' (' . $bookingistic_rule['recipient_custom'] . ')' : '' ) ); ?></td>
					<td><?php echo esc_html( $bookingistic_rule['delay_amount'] . ' ' . $bookingistic_rule['delay_unit'] ); ?></td>
					<td><?php echo $bookingistic_rule['service_id'] ? esc_html__( 'Service-specific', 'bookingistic' ) : esc_html__( 'All services', 'bookingistic' ); ?></td>
					<td><span class="bookingistic-pill bookingistic-pill--<?php echo $bookingistic_rule['status'] === 'active' ? 'confirmed' : 'cancelled'; ?>"><?php echo esc_html( $bookingistic_rule['status'] ); ?></span></td>
					<td>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-emails&edit=' . (int) $bookingistic_rule['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'bookingistic' ); ?></a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	</div>
</div>
