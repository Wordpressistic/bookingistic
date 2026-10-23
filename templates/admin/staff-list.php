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
	<header class="bookingistic-header">
		<h1>
			<?php esc_html_e( 'Staff', 'bookingistic' ); ?>
			<span class="bookingistic-header__badges"><span class="bookingistic-header__badge"><?php echo esc_html( number_format_i18n( count( $staff ) ) ); ?></span></span>
			<span class="bookingistic-header__actions"><a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-staff&new=1' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add new', 'bookingistic' ); ?></a></span>
		</h1>
		<p class="bookingistic-header__tagline"><?php esc_html_e( 'Team members, working hours, and Google Calendar sync.', 'bookingistic' ); ?></p>
	</header>

	<?php // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display notice below. ?>
	<?php if ( ! empty( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Staff deleted.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>
	<?php // phpcs:enable ?>

	<?php if ( empty( $staff ) ) : ?>
		<div class="bookingistic-card">
			<div class="bookingistic-empty">
				<span class="dashicons"></span>
				<p><?php esc_html_e( 'No staff yet. For solo operators, bookings default to a single host using the business working hours from Settings — adding staff is optional.', 'bookingistic' ); ?></p>
			</div>
		</div>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<div class="bookingistic-card bookingistic-card--flush">
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
			<?php foreach ( $staff as $bookingistic_staff_member ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $bookingistic_staff_member['name'] ); ?></strong></td>
					<td><?php echo esc_html( $bookingistic_staff_member['email'] ); ?></td>
					<td><?php echo esc_html( $bookingistic_staff_member['phone'] ); ?></td>
					<td><?php echo $bookingistic_staff_member['booking_limit_per_day'] ? (int) $bookingistic_staff_member['booking_limit_per_day'] : '∞'; ?></td>
					<td><span class="bookingistic-pill bookingistic-pill--<?php echo $bookingistic_staff_member['status'] === 'active' ? 'confirmed' : 'cancelled'; ?>"><?php echo esc_html( $bookingistic_staff_member['status'] ); ?></span></td>
					<td><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-staff&edit=' . (int) $bookingistic_staff_member['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'bookingistic' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	</div>
</div>
