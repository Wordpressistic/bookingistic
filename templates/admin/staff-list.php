<?php
/**
 * Staff list template.
 *
 * @var array $staff
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap bookingistic-wrap">
	<h1>
		<?php esc_html_e( 'Staff', 'bookingistic' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-staff&new=1' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add new', 'bookingistic' ); ?></a>
	</h1>

	<?php if ( ! empty( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Staff deleted.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>

	<?php if ( empty( $staff ) ) : ?>
		<div class="bookingistic-card bookingistic-card--muted">
			<p><?php esc_html_e( 'No staff yet. For solo operators, bookings default to a single host using the business working hours from Settings — adding staff is optional.', 'bookingistic' ); ?></p>
		</div>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<table class="widefat striped">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Email', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Phone', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Daily limit', 'bookingistic' ); ?></th>
			<th><?php esc_html_e( 'Status', 'bookingistic' ); ?></th>
			<th></th>
		</tr></thead>
		<tbody>
			<?php foreach ( $staff as $s ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $s['name'] ); ?></strong></td>
					<td><?php echo esc_html( $s['email'] ); ?></td>
					<td><?php echo esc_html( $s['phone'] ); ?></td>
					<td><?php echo $s['booking_limit_per_day'] ? (int) $s['booking_limit_per_day'] : '∞'; ?></td>
					<td><span class="bookingistic-pill bookingistic-pill--<?php echo $s['status'] === 'active' ? 'confirmed' : 'cancelled'; ?>"><?php echo esc_html( $s['status'] ); ?></span></td>
					<td><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-staff&edit=' . (int) $s['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'bookingistic' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
