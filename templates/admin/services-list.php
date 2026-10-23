<?php
/**
 * Admin services list.
 *
 * @var array $services
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap bookingistic-wrap">
	<header class="bookingistic-header">
		<h1>
			<?php esc_html_e( 'Services', 'bookingistic' ); ?>
			<span class="bookingistic-header__badges"><span class="bookingistic-header__badge"><?php echo esc_html( number_format_i18n( count( $services ) ) ); ?></span></span>
			<span class="bookingistic-header__actions"><a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-services&new=1' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add new', 'bookingistic' ); ?></a></span>
		</h1>
		<p class="bookingistic-header__tagline"><?php esc_html_e( 'Bookable offerings — duration, pricing, deposits, and confirmation flow.', 'bookingistic' ); ?></p>
	</header>

	<?php // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display notice below. ?>
	<?php if ( ! empty( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Service saved.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>
	<?php // phpcs:enable ?>

	<div class="bookingistic-card bookingistic-card--flush">
		<div class="bookingistic-table-card__head bookingistic-card__body" style="padding-bottom:0">
			<p class="bookingistic-muted" style="margin:0"><?php esc_html_e( 'Each service gets its own booking form via shortcode or Elementor widget.', 'bookingistic' ); ?></p>
		</div>

		<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'Slug', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'Duration', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'Price', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'Confirmation', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'Status', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'Shortcode', 'bookingistic' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $services as $bookingistic_service ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $bookingistic_service['name'] ); ?></strong></td>
					<td><code><?php echo esc_html( $bookingistic_service['slug'] ); ?></code></td>
					<td><?php echo esc_html( $bookingistic_service['duration_minutes'] . ' min' ); ?></td>
					<td><?php echo $bookingistic_service['price'] > 0 ? esc_html( number_format_i18n( (float) $bookingistic_service['price'], 2 ) ) : esc_html__( 'Free', 'bookingistic' ); ?></td>
					<td><?php echo esc_html( $bookingistic_service['confirmation_mode'] ); ?></td>
					<td><?php echo esc_html( $bookingistic_service['status'] ); ?></td>
					<td><code>[bookingistic service="<?php echo esc_attr( $bookingistic_service['slug'] ); ?>"]</code></td>
					<td><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-services&edit=' . (int) $bookingistic_service['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'bookingistic' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	</div>
</div>
