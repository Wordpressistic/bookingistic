<?php
/**
 * Admin reports template.
 *
 * @var array<int,array{status:string,n:string}> $status_rows
 * @var array<int,array{name:string,n:string}>   $service_rows
 * @var array<int,array{wk:string,n:string}>     $week_rows
 * @var int                                      $total
 * @var int                                      $max_week
 * @var array<string,string>                     $status_labels
 * @var array<int,array{label:string,value:string,hint?:string,tone?:string}> $stats
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap bookingistic-wrap">
	<header class="bookingistic-header">
		<h1><?php esc_html_e( 'Reports', 'bookingistic' ); ?><span class="bookingistic-header__badges"><span class="bookingistic-header__badge"><?php echo esc_html( number_format_i18n( $total ) ); ?></span></span></h1>
		<p class="bookingistic-header__tagline"><?php esc_html_e( 'Live summaries of your booking activity — computed straight from your data.', 'bookingistic' ); ?></p>
	</header>

	<?php if ( ! $stats ) : ?>
		<div class="bookingistic-card">
			<div class="bookingistic-empty">
				<span class="dashicons dashicons-chart-bar"></span>
				<p><?php esc_html_e( 'No booking data yet. Reports appear here as soon as bookings start coming in.', 'bookingistic' ); ?></p>
			</div>
		</div>
	</div>
	<?php return; ?>
	<?php endif; ?>

	<div class="bookingistic-stats">
		<?php foreach ( $stats as $bookingistic_stat ) : ?>
			<div class="bookingistic-stat<?php echo ! empty( $bookingistic_stat['tone'] ) ? ' bookingistic-stat--' . esc_attr( $bookingistic_stat['tone'] ) : ''; ?>">
				<span class="bookingistic-stat__label"><?php echo esc_html( $bookingistic_stat['label'] ); ?></span>
				<span class="bookingistic-stat__value"><?php echo esc_html( $bookingistic_stat['value'] ); ?></span>
				<?php if ( ! empty( $bookingistic_stat['hint'] ) ) : ?>
					<span class="bookingistic-stat__hint"><?php echo esc_html( $bookingistic_stat['hint'] ); ?></span>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="bookingistic-grid bookingistic-grid--even">
		<div class="bookingistic-card">
			<h2><span class="dashicons dashicons-chart-bar"></span><?php esc_html_e( 'Bookings by service', 'bookingistic' ); ?></h2>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Service', 'bookingistic' ); ?></th>
					<th><?php esc_html_e( 'Bookings', 'bookingistic' ); ?></th>
				</tr></thead>
				<tbody>
					<?php foreach ( $service_rows as $bookingistic_row ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $bookingistic_row['name'] ); ?></strong></td>
							<td><?php echo esc_html( number_format_i18n( (int) $bookingistic_row['n'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div class="bookingistic-card">
			<h2><span class="dashicons dashicons-chart-line"></span><?php esc_html_e( 'Bookings per week', 'bookingistic' ); ?></h2>
			<?php if ( empty( $week_rows ) ) : ?>
				<p class="bookingistic-muted"><?php esc_html_e( 'No scheduled bookings yet.', 'bookingistic' ); ?></p>
			<?php else : ?>
				<ul style="list-style:none;margin:0;padding:0;display:grid;gap:8px">
					<?php foreach ( $week_rows as $bookingistic_week ) : ?>
						<li style="display:grid;grid-template-columns:92px 1fr 40px;align-items:center;gap:10px">
							<span class="bookingistic-muted" style="font-size:12px"><?php echo esc_html( $bookingistic_week['wk'] ); ?></span>
							<span style="display:block;height:10px;border-radius:999px;background:#f1ecfe;overflow:hidden">
								<span style="display:block;height:100%;border-radius:999px;background:var(--bk-accent,#7c3aed);width:<?php echo esc_attr( $max_week > 0 ? round( ( (int) $bookingistic_week['n'] / $max_week ) * 100 ) : 0 ); ?>%"></span>
							</span>
							<span style="font-weight:600;text-align:right;font-variant-numeric:tabular-nums"><?php echo esc_html( number_format_i18n( (int) $bookingistic_week['n'] ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</div>
