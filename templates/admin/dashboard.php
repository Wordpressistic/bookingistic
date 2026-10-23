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
		<h1><?php esc_html_e( 'Dashboard', 'bookingistic' ); ?><span class="bookingistic-header__badges"><span class="bookingistic-header__badge"><span class="dashicons dashicons-awards"></span><?php esc_html_e( 'Premium', 'bookingistic' ); ?></span></span></h1>
		<p class="bookingistic-header__tagline"><?php esc_html_e( 'Build Your Business Automation with AI', 'bookingistic' ); ?></p>
	</header>

	<div class="bookingistic-stats">
		<div class="bookingistic-stat"><span class="bookingistic-stat__label"><?php esc_html_e( 'Total bookings', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo esc_html( number_format_i18n( $total ) ); ?></span></div>
		<div class="bookingistic-stat bookingistic-stat--blue"><span class="bookingistic-stat__label"><?php esc_html_e( 'Upcoming', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo esc_html( number_format_i18n( $upcoming ) ); ?></span></div>
		<div class="bookingistic-stat bookingistic-stat--amber"><span class="bookingistic-stat__label"><?php esc_html_e( 'Pending', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo esc_html( number_format_i18n( $pending ) ); ?></span></div>
		<div class="bookingistic-stat bookingistic-stat--green"><span class="bookingistic-stat__label"><?php esc_html_e( 'Completed', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo esc_html( number_format_i18n( $completed ) ); ?></span></div>
		<div class="bookingistic-stat bookingistic-stat--red"><span class="bookingistic-stat__label"><?php esc_html_e( 'Cancelled', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo esc_html( number_format_i18n( $cancelled ) ); ?></span></div>
	</div>

	<div class="bookingistic-grid bookingistic-grid--two">
		<div class="bookingistic-card">
			<h2><span class="dashicons dashicons-calendar-alt"></span><?php esc_html_e( 'Recent bookings', 'bookingistic' ); ?></h2>
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
						<?php foreach ( $recent_bookings['items'] as $bookingistic_booking ) : ?>
							<tr>
								<td>#<?php echo (int) $bookingistic_booking['id']; ?></td>
								<td><?php echo esc_html( $bookingistic_booking['service_id'] ); ?></td>
								<td><?php echo $bookingistic_booking['start_datetime'] ? esc_html( $bookingistic_booking['start_datetime'] . ' UTC' ) : '—'; ?></td>
								<td><span class="bookingistic-pill bookingistic-pill--<?php echo esc_attr( $bookingistic_booking['status'] ); ?>"><?php echo esc_html( $bookingistic_booking['status'] ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<div class="bookingistic-card">
			<h2><span class="dashicons dashicons-email-alt"></span><?php esc_html_e( 'Recent email activity', 'bookingistic' ); ?></h2>
			<?php if ( empty( $logs ) ) : ?>
				<p class="bookingistic-muted"><?php esc_html_e( 'No emails sent yet.', 'bookingistic' ); ?></p>
			<?php else : ?>
				<ul class="bookingistic-activity">
					<?php foreach ( $logs as $bookingistic_log_row ) : ?>
						<li>
							<strong><?php echo esc_html( $bookingistic_log_row['subject'] ); ?></strong>
							<span class="bookingistic-muted"><?php echo esc_html( $bookingistic_log_row['recipient_email'] . ' · ' . $bookingistic_log_row['status'] . ' · ' . $bookingistic_log_row['created_at'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<div class="bookingistic-card bookingistic-card--muted">
		<h2><span class="dashicons dashicons-editor-code"></span><?php esc_html_e( 'Embed the booking form', 'bookingistic' ); ?></h2>
		<p>
			<?php esc_html_e( 'Use the shortcode below in any page or post:', 'bookingistic' ); ?>
			<code>[bookingistic service="strategy-call"]</code>
		</p>
	</div>
</div>
