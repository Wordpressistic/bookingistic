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

$bookingistic_enabled_map = (array) ( ( (array) get_option( 'bookingistic_settings', [] ) )['payment_gateways'] ?? [] );

// Guest names for the ledger table (one query per row; pages are small).
$bookingistic_guest_labels = [];
foreach ( $rows as $bookingistic_ledger_row ) {
	$bookingistic_booking_id = (int) $bookingistic_ledger_row['booking_id'];
	if ( isset( $bookingistic_guest_labels[ $bookingistic_booking_id ] ) ) {
		continue;
	}
	$bookingistic_booking = \Bookingistic\Database\Booking_Repository::find( $bookingistic_booking_id );
	$bookingistic_customer = $bookingistic_booking ? \Bookingistic\Database\Customer_Repository::find( (int) $bookingistic_booking['customer_id'] ) : null;
	$bookingistic_guest_labels[ $bookingistic_booking_id ] = $bookingistic_customer ? trim( ( $bookingistic_customer['first_name'] ?? '' ) . ' ' . ( $bookingistic_customer['last_name'] ?? '' ) ) : '';
}
$bookingistic_pill = [
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

	<?php // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display notices below. ?>
	<?php if ( ! empty( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Payment settings saved.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Payment record updated.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>
	<?php // phpcs:enable ?>

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
					<?php foreach ( $gateways as $bookingistic_gateway_slug => $bookingistic_gateway ) : ?>
						<tr>
							<td style="width:36px">
								<input type="checkbox" name="gateway_<?php echo esc_attr( $bookingistic_gateway_slug ); ?>" value="1" <?php checked( ! empty( $bookingistic_enabled_map[ $bookingistic_gateway_slug ] ) ); ?>>
							</td>
							<td>
								<strong><?php echo esc_html( $bookingistic_gateway->label() ); ?></strong><br>
								<span class="description"><?php echo esc_html( $bookingistic_gateway->description() ); ?></span>
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
				<?php foreach ( \Bookingistic\Database\Payment_Repository::STATUSES as $bookingistic_pay_status ) : ?>
					<option value="<?php echo esc_attr( $bookingistic_pay_status ); ?>" <?php selected( $status, $bookingistic_pay_status ); ?>><?php echo esc_html( $bookingistic_pay_status ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<span><?php esc_html_e( 'Provider', 'bookingistic' ); ?></span>
			<select name="provider">
				<option value=""><?php esc_html_e( 'All', 'bookingistic' ); ?></option>
				<?php foreach ( array_keys( $gateways ) as $bookingistic_gateway_slug ) : ?>
					<option value="<?php echo esc_attr( $bookingistic_gateway_slug ); ?>" <?php selected( $provider, $bookingistic_gateway_slug ); ?>><?php echo esc_html( $bookingistic_gateway_slug ); ?></option>
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
			<?php foreach ( $rows as $bookingistic_ledger_row ) : ?>
				<tr>
					<td><?php echo esc_html( $bookingistic_ledger_row['created_at'] ); ?></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-bookings&view=' . (int) $bookingistic_ledger_row['booking_id'] ) ); ?>">#<?php echo (int) $bookingistic_ledger_row['booking_id']; ?></a>
					</td>
					<td><?php echo esc_html( $bookingistic_guest_labels[ (int) $bookingistic_ledger_row['booking_id'] ] ?? '' ); ?></td>
					<td><?php echo esc_html( $bookingistic_ledger_row['provider'] ); ?></td>
					<td><?php echo esc_html( $bookingistic_ledger_row['external_id'] !== '' ? $bookingistic_ledger_row['external_id'] : '—' ); ?></td>
					<td><?php echo esc_html( $bookingistic_ledger_row['currency'] . ' ' . number_format( (float) $bookingistic_ledger_row['amount'], 2 ) ); ?></td>
					<td>
						<span class="bookingistic-pill <?php echo esc_attr( $bookingistic_pill[ $bookingistic_ledger_row['status'] ] ?? 'bookingistic-pill--pending' ); ?>"><?php echo esc_html( $bookingistic_ledger_row['status'] ); ?></span>
					</td>
					<td>
						<form method="post" style="display:flex;gap:6px">
							<?php wp_nonce_field( \Bookingistic\Admin\Payments_Page::NONCE ); ?>
							<input type="hidden" name="bookingistic_admin_action" value="payment_mark_paid">
							<input type="hidden" name="booking_id" value="<?php echo (int) $bookingistic_ledger_row['booking_id']; ?>">
							<?php if ( 'pending' === $bookingistic_ledger_row['status'] || 'failed' === $bookingistic_ledger_row['status'] ) : ?>
								<button type="submit" class="button button-small"><?php esc_html_e( 'Mark paid', 'bookingistic' ); ?></button>
							<?php endif; ?>
						</form>
						<?php if ( 'paid' === $bookingistic_ledger_row['status'] ) : ?>
						<form method="post" style="display:flex;gap:6px">
							<?php wp_nonce_field( \Bookingistic\Admin\Payments_Page::NONCE ); ?>
							<input type="hidden" name="bookingistic_admin_action" value="payment_refund">
							<input type="hidden" name="booking_id" value="<?php echo (int) $bookingistic_ledger_row['booking_id']; ?>">
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
