<?php
/**
 * Admin dashboard template.
 *
 * @var int   $total
 * @var int   $upcoming
 * @var int   $pending
 * @var int   $completed
 * @var int   $cancelled
 * @var array $logs
 * @var array $recent_bookings
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap bookingistic-wrap">
	<header class="bookingistic-header">
		<h1><?php esc_html_e( 'Bookingistic — Dashboard', 'bookingistic' ); ?></h1>
		<p class="bookingistic-header__tagline"><?php esc_html_e( 'Build Your Business Automation with AI', 'bookingistic' ); ?></p>
	</header>

	<div class="bookingistic-stats">
		<div class="bookingistic-stat"><span class="bookingistic-stat__label"><?php esc_html_e( 'Total bookings', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo esc_html( number_format_i18n( $total ) ); ?></span></div>
		<div class="bookingistic-stat"><span class="bookingistic-stat__label"><?php esc_html_e( 'Upcoming', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo esc_html( number_format_i18n( $upcoming ) ); ?></span></div>
		<div class="bookingistic-stat"><span class="bookingistic-stat__label"><?php esc_html_e( 'Pending', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo esc_html( number_format_i18n( $pending ) ); ?></span></div>
		<div class="bookingistic-stat"><span class="bookingistic-stat__label"><?php esc_html_e( 'Completed', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo esc_html( number_format_i18n( $completed ) ); ?></span></div>
		<div class="bookingistic-stat"><span class="bookingistic-stat__label"><?php esc_html_e( 'Cancelled', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo esc_html( number_format_i18n( $cancelled ) ); ?></span></div>
	</div>

	<div class="bookingistic-grid bookingistic-grid--two">
		<div class="bookingistic-card">
			<h2><?php esc_html_e( 'Recent bookings', 'bookingistic' ); ?></h2>
			<?php if ( empty( $recent_bookings['items'] ) ) : ?>
				<p class="bookingistic-muted"><?php esc_html_e( 'No bookings yet.', 'bookingistic' ); ?></p>
			<?php else : ?>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'ID', 'bookingistic' ); ?></th>
							<th><?php esc_html_e( 'Service', 'bookingistic' ); ?></th>
							<th><?php esc_html_e( 'When', 'bookingistic' ); ?></th>
							<th><?php esc_html_e( 'Status', 'bookingistic' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent_bookings['items'] as $b ) : ?>
							<tr>
								<td>#<?php echo (int) $b['id']; ?></td>
								<td><?php echo esc_html( $b['service_id'] ); ?></td>
								<td><?php echo $b['start_datetime'] ? esc_html( $b['start_datetime'] . ' UTC' ) : '—'; ?></td>
								<td><span class="bookingistic-pill bookingistic-pill--<?php echo esc_attr( $b['status'] ); ?>"><?php echo esc_html( $b['status'] ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<div class="bookingistic-card">
			<h2><?php esc_html_e( 'Recent email activity', 'bookingistic' ); ?></h2>
			<?php if ( empty( $logs ) ) : ?>
				<p class="bookingistic-muted"><?php esc_html_e( 'No emails sent yet.', 'bookingistic' ); ?></p>
			<?php else : ?>
				<ul class="bookingistic-activity">
					<?php foreach ( $logs as $log ) : ?>
						<li>
							<strong><?php echo esc_html( $log['subject'] ); ?></strong>
							<span class="bookingistic-muted"><?php echo esc_html( $log['recipient_email'] . ' · ' . $log['status'] . ' · ' . $log['created_at'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<div class="bookingistic-card bookingistic-card--muted">
		<h2><?php esc_html_e( 'Embed the booking form', 'bookingistic' ); ?></h2>
		<p>
			<?php esc_html_e( 'Use the shortcode below in any page or post:', 'bookingistic' ); ?>
			<code>[bookingistic service="strategy-call"]</code>
		</p>
	</div>
</div>
