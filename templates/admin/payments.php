<?php
/**
 * Admin payments template.
 *
 * @var array<int,array<string,mixed>> $rows      Ledger rows (booking_id, provider, external_id, amount, currency, status, created_at).
 * @var array<string,\Bookingistic\Payments\Payment_Gateway> $gateways
 * @var int   $total
 * @var int   $page
 * @var int   $pages
 * @var string $status
 * @var string $provider
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;

use Bookingistic\Payments\Payment_Manager;

$enabled_map = (array) ( ( (array) get_option( 'bookingistic_settings', [] ) )['payment_gateways'] ?? [] );

// Guest names for the ledger table (one query per row; pages are small).
$booking_labels = [];
foreach ( $rows as $r ) {
	$bid = (int) $r['booking_id'];
	if ( isset( $booking_labels[ $bid ] ) ) {
		continue;
	}
	$b = \Bookingistic\Database\Booking_Repository::find( $bid );
	$c = $b ? \Bookingistic\Database\Customer_Repository::find( (int) $b['customer_id'] ) : null;
	$booking_labels[ $bid ] = $c ? trim( ( $c['first_name'] ?? '' ) . ' ' . ( $c['last_name'] ?? '' ) ) : '';
}
$pill = [
	'pending'   => 'bookingistic-pill--pending',
	'paid'      => 'bookingistic-pill--confirmed',
	'failed'    => 'bookingistic-pill--cancelled',
	'refunded'  => 'bookingistic-pill--cancelled',
	'cancelled' => 'bookingistic-pill--cancelled',
];
?>
<div class="wrap bookingistic-wrap">
	<header class="bookingistic-header">
		<h1><?php esc_html_e( 'Payments', 'bookingistic' ); ?><span class="bookingistic-header__badges"><span class="bookingistic-header__badge"><span class="dashicons dashicons-money-alt"></span><?php echo esc_html( number_format_i18n( $total ) ); ?></span></span></h1>
		<p class="bookingistic-header__tagline"><?php esc_html_e( 'Payment methods and the full ledger of every payment attempt.', 'bookingistic' ); ?></p>
	</header>

	<?php if ( ! empty( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Payment settings saved.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Payment record updated.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>

	<?php if ( empty( $gateways ) ) : ?>
		<div class="bookingistic-card">
			<div class="bookingistic-empty">
				<span class="dashicons dashicons-money-alt"></span>
				<p><?php esc_html_e( 'No payment gateways are available yet. Install WooCommerce to collect payments for paid bookings, or add a gateway via the bookingistic_payment_gateways filter.', 'bookingistic' ); ?></p>
			</div>
		</div>
	<?php else : ?>
		<div class="bookingistic-card" style="max-width:860px">
			<h2><span class="dashicons dashicons-cart"></span><?php esc_html_e( 'Payment methods', 'bookingistic' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( \Bookingistic\Admin\Payments_Page::NONCE ); ?>
				<input type="hidden" name="bookingistic_admin_action" value="save_payments">
				<table class="widefat striped">
				<tbody>
					<?php foreach ( $gateways as $slug => $gateway ) : ?>
						<tr>
							<td style="width:36px">
								<input type="checkbox" name="gateway_<?php echo esc_attr( $slug ); ?>" value="1" <?php checked( ! empty( $enabled_map[ $slug ] ) ); ?>>
							</td>
							<td>
								<strong><?php echo esc_html( $gateway->label() ); ?></strong><br>
								<span class="description"><?php echo esc_html( $gateway->description() ); ?></span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p style="margin:14px 0 0"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save payment methods', 'bookingistic' ); ?></button></p>
		</form>
		</div>
	<?php endif; ?>

	<div class="bookingistic-card bookingistic-card--flush" style="margin-top:16px">
		<div class="bookingistic-table-card__head bookingistic-card__body" style="padding-bottom:0">
			<h2 style="margin:0"><span class="dashicons dashicons-list-view"></span><?php esc_html_e( 'Payment records', 'bookingistic' ); ?></h2>
		</div>

	<form method="get" class="bookingistic-calendar__filters" style="margin-top:6px">
		<input type="hidden" name="page" value="bookingistic-payments">
		<label>
			<span><?php esc_html_e( 'Status', 'bookingistic' ); ?></span>
			<select name="status">
				<option value=""><?php esc_html_e( 'All', 'bookingistic' ); ?></option>
				<?php foreach ( \Bookingistic\Database\Payment_Repository::STATUSES as $s ) : ?>
					<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status, $s ); ?>><?php echo esc_html( $s ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<span><?php esc_html_e( 'Provider', 'bookingistic' ); ?></span>
			<select name="provider">
				<option value=""><?php esc_html_e( 'All', 'bookingistic' ); ?></option>
				<?php foreach ( array_keys( $gateways ) as $slug ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $provider, $slug ); ?>><?php echo esc_html( $slug ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<button type="submit" class="button"><?php esc_html_e( 'Filter', 'bookingistic' ); ?></button>
	</form>

	<?php if ( empty( $rows ) ) : ?>
		<div class="bookingistic-empty">
			<span class="dashicons dashicons-money-alt"></span>
			<p><?php esc_html_e( 'No payment records yet. Records appear here once a paid booking goes through a payment method.', 'bookingistic' ); ?></p>
		</div>
	<?php endif; ?>

	<table class="widefat striped">
		<thead><tr>
			<th><?php esc_html_e( 'When', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Booking', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Guest', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Provider', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Reference', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Amount', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Status', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'bookingistic' ); ?></th>
		</tr></thead>
		<tbody>
			<?php foreach ( $rows as $r ) : ?>
				<tr>
					<td><?php echo esc_html( $r['created_at'] ); ?></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-bookings&view=' . (int) $r['booking_id'] ) ); ?>">#<?php echo (int) $r['booking_id']; ?></a>
					</td>
					<td><?php echo esc_html( $booking_labels[ (int) $r['booking_id'] ] ?? '' ); ?></td>
					<td><?php echo esc_html( $r['provider'] ); ?></td>
					<td><?php echo esc_html( $r['external_id'] !== '' ? $r['external_id'] : '—' ); ?></td>
					<td><?php echo esc_html( $r['currency'] . ' ' . number_format( (float) $r['amount'], 2 ) ); ?></td>
					<td>
						<span class="bookingistic-pill <?php echo esc_attr( $pill[ $r['status'] ] ?? 'bookingistic-pill--pending' ); ?>"><?php echo esc_html( $r['status'] ); ?></span>
					</td>
					<td>
						<form method="post" style="display:flex;gap:6px">
							<?php wp_nonce_field( \Bookingistic\Admin\Payments_Page::NONCE ); ?>
							<input type="hidden" name="bookingistic_admin_action" value="payment_mark_paid">
							<input type="hidden" name="booking_id" value="<?php echo (int) $r['booking_id']; ?>">
							<?php if ( 'pending' === $r['status'] || 'failed' === $r['status'] ) : ?>
								<button type="submit" class="button button-small"><?php esc_html_e( 'Mark paid', 'bookingistic' ); ?></button>
							<?php endif; ?>
						</form>
						<?php if ( 'paid' === $r['status'] ) : ?>
						<form method="post" style="display:flex;gap:6px">
							<?php wp_nonce_field( \Bookingistic\Admin\Payments_Page::NONCE ); ?>
							<input type="hidden" name="bookingistic_admin_action" value="payment_refund">
							<input type="hidden" name="booking_id" value="<?php echo (int) $r['booking_id']; ?>">
							<button type="submit" class="button button-small"><?php esc_html_e( 'Refund', 'bookingistic' ); ?></button>
						</form>
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
</div>
