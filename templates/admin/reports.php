<?php
/**
 * Admin reports template.
 *
 * @var int    $days
 * @var array  $totals
 * @var array  $by_date
 * @var array  $by_service
 * @var array  $by_staff
 * @var array  $revenue_by_service
 * @var array  $by_source
 * @var array  $email_stats
 * @var array  $service_lookup
 * @var array  $staff_lookup
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;

$max_date_count = 0;
foreach ( $by_date as $row ) {
	if ( (int) $row['c'] > $max_date_count ) {
		$max_date_count = (int) $row['c'];
	}
}

$render_bar = static function ( int $value, int $max ): string {
	$pct = $max > 0 ? round( 100 * $value / $max ) : 0;
	return '<div class="bookingistic-report-bar"><span style="width:' . (int) $pct . '%"></span></div>';
};
?>
<div class="wrap bookingistic-wrap">
	<h1>
		<?php esc_html_e( 'Reports', 'bookingistic' ); ?>
		<form method="get" style="display:inline-block;margin-left:14px">
			<input type="hidden" name="page" value="bookingistic-reports">
			<select name="days" onchange="this.form.submit()">
				<?php foreach ( [ 7, 14, 30, 60, 90, 180 ] as $opt ) : ?>
					<option value="<?php echo (int) $opt; ?>" <?php selected( $days, $opt ); ?>><?php printf( esc_html__( 'Last %d days', 'bookingistic' ), $opt ); ?></option>
				<?php endforeach; ?>
			</select>
		</form>
	</h1>

	<div class="bookingistic-stats">
		<div class="bookingistic-stat"><span class="bookingistic-stat__label"><?php esc_html_e( 'Bookings (window)', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo (int) $totals['in_window']; ?></span></div>
		<div class="bookingistic-stat"><span class="bookingistic-stat__label"><?php esc_html_e( 'Completed', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo (int) $totals['completed']; ?></span></div>
		<div class="bookingistic-stat"><span class="bookingistic-stat__label"><?php esc_html_e( 'Cancellation rate', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo esc_html( $totals['cancellation_rate'] . '%' ); ?></span></div>
		<div class="bookingistic-stat"><span class="bookingistic-stat__label"><?php esc_html_e( 'No-show rate', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo esc_html( $totals['no_show_rate'] . '%' ); ?></span></div>
		<div class="bookingistic-stat"><span class="bookingistic-stat__label"><?php esc_html_e( 'Revenue', 'bookingistic' ); ?></span><span class="bookingistic-stat__value"><?php echo esc_html( number_format_i18n( (float) $totals['revenue'], 2 ) ); ?></span></div>
	</div>

	<div class="bookingistic-grid bookingistic-grid--two">
		<div class="bookingistic-card">
			<h2><?php esc_html_e( 'Bookings by day', 'bookingistic' ); ?></h2>
			<?php if ( empty( $by_date ) ) : ?>
				<p class="bookingistic-muted"><?php esc_html_e( 'No bookings in this range.', 'bookingistic' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Date', 'bookingistic' ); ?></th><th><?php esc_html_e( 'Count', 'bookingistic' ); ?></th><th></th></tr></thead>
					<tbody>
						<?php foreach ( $by_date as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['d'] ); ?></td>
								<td><?php echo (int) $row['c']; ?></td>
								<td><?php echo $render_bar( (int) $row['c'], $max_date_count ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<div class="bookingistic-card">
			<h2><?php esc_html_e( 'Top services', 'bookingistic' ); ?></h2>
			<?php if ( empty( $by_service ) ) : ?>
				<p class="bookingistic-muted"><?php esc_html_e( 'No data.', 'bookingistic' ); ?></p>
			<?php else :
				$max_s = max( array_map( static fn( $r ) => (int) $r['c'], $by_service ) );
				?>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Service', 'bookingistic' ); ?></th><th><?php esc_html_e( 'Bookings', 'bookingistic' ); ?></th><th></th></tr></thead>
					<tbody>
						<?php foreach ( $by_service as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $service_lookup[ (int) $row['service_id'] ] ?? '#' . (int) $row['service_id'] ); ?></td>
								<td><?php echo (int) $row['c']; ?></td>
								<td><?php echo $render_bar( (int) $row['c'], $max_s ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>

	<div class="bookingistic-grid bookingistic-grid--two" style="margin-top:16px">
		<div class="bookingistic-card">
			<h2><?php esc_html_e( 'Top staff', 'bookingistic' ); ?></h2>
			<?php if ( empty( $by_staff ) || ( count( $by_staff ) === 1 && (int) $by_staff[0]['staff_id'] === 0 ) ) : ?>
				<p class="bookingistic-muted"><?php esc_html_e( 'No staff assigned yet — every booking is routed to the default host.', 'bookingistic' ); ?></p>
			<?php else :
				$max_st = max( array_map( static fn( $r ) => (int) $r['c'], $by_staff ) );
				?>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Staff', 'bookingistic' ); ?></th><th><?php esc_html_e( 'Bookings', 'bookingistic' ); ?></th><th></th></tr></thead>
					<tbody>
						<?php foreach ( $by_staff as $row ) :
							$name = (int) $row['staff_id'] === 0 ? __( 'Default host', 'bookingistic' ) : ( $staff_lookup[ (int) $row['staff_id'] ] ?? '#' . (int) $row['staff_id'] ); ?>
							<tr>
								<td><?php echo esc_html( $name ); ?></td>
								<td><?php echo (int) $row['c']; ?></td>
								<td><?php echo $render_bar( (int) $row['c'], $max_st ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<div class="bookingistic-card">
			<h2><?php esc_html_e( 'Revenue by service', 'bookingistic' ); ?></h2>
			<?php if ( empty( $revenue_by_service ) ) : ?>
				<p class="bookingistic-muted"><?php esc_html_e( 'No paid bookings in this range.', 'bookingistic' ); ?></p>
			<?php else :
				$max_r = max( array_map( static fn( $r ) => (float) $r['revenue'], $revenue_by_service ) );
				?>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Service', 'bookingistic' ); ?></th><th><?php esc_html_e( 'Revenue', 'bookingistic' ); ?></th><th></th></tr></thead>
					<tbody>
						<?php foreach ( $revenue_by_service as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $service_lookup[ (int) $row['service_id'] ] ?? '#' . (int) $row['service_id'] ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (float) $row['revenue'], 2 ) ); ?></td>
								<td><?php echo $render_bar( (int) round( (float) $row['revenue'] * 100 ), (int) round( $max_r * 100 ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>

	<div class="bookingistic-grid bookingistic-grid--two" style="margin-top:16px">
		<div class="bookingistic-card">
			<h2><?php esc_html_e( 'Booking source', 'bookingistic' ); ?></h2>
			<?php if ( empty( $by_source ) ) : ?>
				<p class="bookingistic-muted"><?php esc_html_e( 'No data.', 'bookingistic' ); ?></p>
			<?php else :
				$max_src = max( array_map( static fn( $r ) => (int) $r['c'], $by_source ) );
				?>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Source', 'bookingistic' ); ?></th><th><?php esc_html_e( 'Bookings', 'bookingistic' ); ?></th><th></th></tr></thead>
					<tbody>
						<?php foreach ( $by_source as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['source'] ?: '—' ); ?></td>
								<td><?php echo (int) $row['c']; ?></td>
								<td><?php echo $render_bar( (int) $row['c'], $max_src ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<div class="bookingistic-card">
			<h2><?php esc_html_e( 'Email delivery', 'bookingistic' ); ?></h2>
			<dl class="bookingistic-detail">
				<dt><?php esc_html_e( 'Sent', 'bookingistic' ); ?></dt><dd><?php echo (int) $email_stats['sent']; ?></dd>
				<dt><?php esc_html_e( 'Failed', 'bookingistic' ); ?></dt><dd><?php echo (int) $email_stats['failed']; ?></dd>
				<dt><?php esc_html_e( 'Success rate', 'bookingistic' ); ?></dt>
				<dd>
					<?php
					$den = $email_stats['sent'] + $email_stats['failed'];
					echo esc_html( $den ? round( 100 * $email_stats['sent'] / $den, 1 ) . '%' : '—' );
					?>
				</dd>
			</dl>
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-email-logs' ) ); ?>" class="button"><?php esc_html_e( 'Open email logs', 'bookingistic' ); ?></a></p>
		</div>
	</div>
</div>
