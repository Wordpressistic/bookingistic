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
	<h1>
		<?php esc_html_e( 'Services', 'bookingistic' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-services&new=1' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add new', 'bookingistic' ); ?></a>
	</h1>

	<?php if ( ! empty( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Service saved.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>

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
			<?php foreach ( $services as $s ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $s['name'] ); ?></strong></td>
					<td><code><?php echo esc_html( $s['slug'] ); ?></code></td>
					<td><?php echo esc_html( $s['duration_minutes'] . ' min' ); ?></td>
					<td><?php echo $s['price'] > 0 ? esc_html( number_format_i18n( (float) $s['price'], 2 ) ) : esc_html__( 'Free', 'bookingistic' ); ?></td>
					<td><?php echo esc_html( $s['confirmation_mode'] ); ?></td>
					<td><?php echo esc_html( $s['status'] ); ?></td>
					<td><code>[bookingistic service="<?php echo esc_attr( $s['slug'] ); ?>"]</code></td>
					<td><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-services&edit=' . (int) $s['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'bookingistic' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
