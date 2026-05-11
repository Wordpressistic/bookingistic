<?php
/**
 * Customer profile drilldown.
 *
 * @var array $customer
 * @var array $bookings
 * @var array $stats
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap bookingistic-wrap">
	<h1>
		<?php echo esc_html( $customer['full_name'] ?: $customer['email'] ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-customers' ) ); ?>" class="page-title-action"><?php esc_html_e( '← Back', 'bookingistic' ); ?></a>
	</h1>

	<div class="bookingistic-stats">
		<div class="bookingistic-stat"><span class="bookingistic-stat__label"><?php esc_html_e( 'Total bookings', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo (int) $stats['total']; ?></span></div>
		<div class="bookingistic-stat"><span class="bookingistic-stat__label"><?php esc_html_e( 'Confirmed', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo (int) $stats['confirmed']; ?></span></div>
		<div class="bookingistic-stat"><span class="bookingistic-stat__label"><?php esc_html_e( 'Completed', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo (int) $stats['completed']; ?></span></div>
		<div class="bookingistic-stat"><span class="bookingistic-stat__label"><?php esc_html_e( 'Cancelled', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo (int) $stats['cancelled']; ?></span></div>
		<div class="bookingistic-stat"><span class="bookingistic-stat__label"><?php esc_html_e( 'Revenue', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo esc_html( number_format_i18n( (float) $stats['revenue'], 2 ) ); ?></span></div>
	</div>

	<div class="bookingistic-detail-grid">
		<div>
			<div class="bookingistic-card">
				<h2><?php esc_html_e( 'Booking history', 'bookingistic' ); ?></h2>
				<?php if ( empty( $bookings ) ) : ?>
					<p class="bookingistic-muted"><?php esc_html_e( 'No bookings yet.', 'bookingistic' ); ?></p>
				<?php else : ?>
					<table class="widefat">
						<thead><tr>
							<th><?php esc_html_e( 'ID', 'bookingistic' ); ?></th>
							<th><?php esc_html_e( 'Service', 'bookingistic' ); ?></th>
							<th><?php esc_html_e( 'When', 'bookingistic' ); ?></th>
							<th><?php esc_html_e( 'Status', 'bookingistic' ); ?></th>
							<th><?php esc_html_e( 'Payment', 'bookingistic' ); ?></th>
							<th></th>
						</tr></thead>
						<tbody>
							<?php foreach ( $bookings as $b ) :
								$svc = \Bookingistic\Database\Service_Repository::find( (int) $b['service_id'] ); ?>
								<tr>
									<td>#<?php echo (int) $b['id']; ?></td>
									<td><?php echo esc_html( $svc['name'] ?? '—' ); ?></td>
									<td><?php echo $b['start_datetime'] ? esc_html( $b['start_datetime'] . ' UTC' ) : '—'; ?></td>
									<td><span class="bookingistic-pill bookingistic-pill--<?php echo esc_attr( $b['status'] ); ?>"><?php echo esc_html( $b['status'] ); ?></span></td>
									<td><?php echo esc_html( $b['payment_status'] ); ?></td>
									<td><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-bookings&view=' . (int) $b['id'] ) ); ?>"><?php esc_html_e( 'Open', 'bookingistic' ); ?></a></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>

		<aside>
			<div class="bookingistic-card">
				<h2><?php esc_html_e( 'Profile', 'bookingistic' ); ?></h2>
				<dl class="bookingistic-detail">
					<dt><?php esc_html_e( 'Email', 'bookingistic' ); ?></dt>
					<dd><a href="mailto:<?php echo esc_attr( $customer['email'] ); ?>"><?php echo esc_html( $customer['email'] ); ?></a></dd>
					<dt><?php esc_html_e( 'Phone', 'bookingistic' ); ?></dt>
					<dd><?php echo esc_html( $customer['phone'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Company', 'bookingistic' ); ?></dt>
					<dd><?php echo esc_html( $customer['company'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Website', 'bookingistic' ); ?></dt>
					<dd><?php
						if ( $customer['website'] ) {
							echo '<a href="' . esc_url( $customer['website'] ) . '" target="_blank" rel="noopener">' . esc_html( $customer['website'] ) . '</a>';
						} else {
							echo '—';
						}
					?></dd>
					<dt><?php esc_html_e( 'Timezone', 'bookingistic' ); ?></dt>
					<dd><?php echo esc_html( $customer['timezone'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Source', 'bookingistic' ); ?></dt>
					<dd><?php echo esc_html( $customer['source'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Tags', 'bookingistic' ); ?></dt>
					<dd><?php echo esc_html( $customer['tags'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Created', 'bookingistic' ); ?></dt>
					<dd><?php echo esc_html( $customer['created_at'] ); ?></dd>
				</dl>
			</div>

			<?php if ( ! empty( $customer['notes'] ) ) : ?>
				<div class="bookingistic-card" style="margin-top:16px">
					<h2><?php esc_html_e( 'Notes', 'bookingistic' ); ?></h2>
					<p><?php echo nl2br( esc_html( $customer['notes'] ) ); ?></p>
				</div>
			<?php endif; ?>
		</aside>
	</div>
</div>
