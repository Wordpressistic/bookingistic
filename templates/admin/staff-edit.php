<?php
/**
 * Staff editor.
 *
 * @var array|null $staff
 * @var array      $services
 * @var array      $assigned_services  staff_id → [service_id,…]
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;

use Bookingistic\Admin\Staff_Page;

$bookingistic_is_new = empty( $staff );
$bookingistic_staff  = $staff ?: [
	'id'                    => 0,
	'name'                  => '',
	'email'                 => '',
	'phone'                 => '',
	'photo_url'             => '',
	'bio'                   => '',
	'working_hours'         => [],
	'days_off'              => [],
	'booking_limit_per_day' => 0,
	'status'                => 'active',
];

// Normalize working_hours to per-day shape for editing.
$bookingistic_wh_per_day = [];
foreach ( Staff_Page::DAYS as $bookingistic_dow => $bookingistic_dow_label ) {
	$bookingistic_wh_per_day[ $bookingistic_dow ] = [ 'on' => false, 'start' => '09:00', 'end' => '17:00' ];
}
$bookingistic_wh = $bookingistic_staff['working_hours'];
if ( isset( $bookingistic_wh['start'], $bookingistic_wh['end'] ) ) {
	// Legacy shape.
	$bookingistic_days = ! empty( $bookingistic_wh['days'] ) ? array_map( 'intval', (array) $bookingistic_wh['days'] ) : [ 1, 2, 3, 4, 5 ];
	foreach ( $bookingistic_days as $bookingistic_day ) {
		if ( isset( $bookingistic_wh_per_day[ $bookingistic_day ] ) ) {
			$bookingistic_wh_per_day[ $bookingistic_day ] = [ 'on' => true, 'start' => $bookingistic_wh['start'], 'end' => $bookingistic_wh['end'] ];
		}
	}
} else {
	foreach ( $bookingistic_wh as $bookingistic_wh_key => $bookingistic_wh_entry ) {
		$bookingistic_day = (int) $bookingistic_wh_key;
		if ( isset( $bookingistic_wh_per_day[ $bookingistic_day ] ) && is_array( $bookingistic_wh_entry ) && ! empty( $bookingistic_wh_entry['start'] ) && ! empty( $bookingistic_wh_entry['end'] ) ) {
			$bookingistic_wh_per_day[ $bookingistic_day ] = [ 'on' => true, 'start' => $bookingistic_wh_entry['start'], 'end' => $bookingistic_wh_entry['end'] ];
		}
	}
}
?>
<div class="wrap bookingistic-wrap">
	<h1>
		<?php echo $bookingistic_is_new ? esc_html__( 'Add staff', 'bookingistic' ) : esc_html__( 'Edit staff', 'bookingistic' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-staff' ) ); ?>" class="page-title-action"><?php esc_html_e( '← Back', 'bookingistic' ); ?></a>
	</h1>

	<?php // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display notices below. ?>
	<?php if ( ! empty( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Staff saved.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['error'] ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['error'] ) ) ); ?></p></div>
	<?php endif; ?>

	<?php // phpcs:enable ?>

	<form method="post">
		<?php wp_nonce_field( 'bookingistic_admin_staff' ); ?>
		<input type="hidden" name="bookingistic_admin_action" value="save_staff">
		<input type="hidden" name="staff_id" value="<?php echo (int) $bookingistic_staff['id']; ?>">

		<h2><?php esc_html_e( 'Profile', 'bookingistic' ); ?></h2>
		<table class="form-table">
			<tr><th><label><?php esc_html_e( 'Name', 'bookingistic' ); ?></label></th>
				<td><input type="text" name="name" class="regular-text" required value="<?php echo esc_attr( $bookingistic_staff['name'] ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Email', 'bookingistic' ); ?></label></th>
				<td><input type="email" name="email" class="regular-text" required value="<?php echo esc_attr( $bookingistic_staff['email'] ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Phone', 'bookingistic' ); ?></label></th>
				<td><input type="tel" name="phone" class="regular-text" value="<?php echo esc_attr( $bookingistic_staff['phone'] ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Photo URL', 'bookingistic' ); ?></label></th>
				<td><input type="url" name="photo_url" class="regular-text" value="<?php echo esc_attr( $bookingistic_staff['photo_url'] ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Bio', 'bookingistic' ); ?></label></th>
				<td><textarea name="bio" rows="3" class="large-text"><?php echo esc_textarea( $bookingistic_staff['bio'] ); ?></textarea></td></tr>
			<tr><th><label><?php esc_html_e( 'Daily booking limit', 'bookingistic' ); ?></label></th>
				<td><input type="number" name="booking_limit_per_day" min="0" value="<?php echo (int) $bookingistic_staff['booking_limit_per_day']; ?>">
					<p class="description"><?php esc_html_e( '0 = unlimited.', 'bookingistic' ); ?></p></td></tr>
			<tr><th><label><?php esc_html_e( 'Status', 'bookingistic' ); ?></label></th>
				<td><select name="status">
					<option value="active"   <?php selected( $bookingistic_staff['status'], 'active' ); ?>><?php esc_html_e( 'Active', 'bookingistic' ); ?></option>
					<option value="inactive" <?php selected( $bookingistic_staff['status'], 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'bookingistic' ); ?></option>
				</select></td></tr>
		</table>

		<h2><?php esc_html_e( 'Google Calendar', 'bookingistic' ); ?></h2>
		<?php
		$bookingistic_gcal_connected = ! $bookingistic_is_new && ! empty( $bookingistic_staff['gcal_connected'] );
		$bookingistic_gcal_url       = $bookingistic_is_new ? '' : Staff_Page::gcal_connect_url( (int) $bookingistic_staff['id'] );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display notice.
		$bookingistic_gcal_notice    = isset( $_GET['gcal'] ) ? sanitize_key( wp_unslash( $_GET['gcal'] ) ) : '';
		if ( $bookingistic_gcal_notice ) : ?>
			<div class="notice inline notice-success" style="margin:10px 0"><p>
				<?php
				echo esc_html(
					match ( $bookingistic_gcal_notice ) {
						'connected'    => __( 'Google Calendar connected.', 'bookingistic' ),
						'disconnected' => __( 'Google Calendar disconnected.', 'bookingistic' ),
						'synced'       => __( 'Google Calendar busy times refreshed.', 'bookingistic' ),
						default        => __( 'Google Calendar action failed — check the OAuth settings under Integrations.', 'bookingistic' ),
					}
				);
				?>
			</p></div>
		<?php endif; ?>
		<?php if ( $bookingistic_is_new ) : ?>
			<p class="description"><?php esc_html_e( 'Save this staff member first, then connect their Google Calendar.', 'bookingistic' ); ?></p>
		<?php elseif ( $bookingistic_gcal_connected ) : ?>
			<p style="color:green">✓ <?php esc_html_e( 'Connected — confirmed bookings sync to their Google Calendar and external events block booking slots.', 'bookingistic' ); ?></p>
			<form method="post" style="display:inline">
				<?php wp_nonce_field( 'bookingistic_admin_gcal' ); ?>
				<input type="hidden" name="bookingistic_admin_action" value="gcal_sync_now">
				<input type="hidden" name="staff_id" value="<?php echo (int) $bookingistic_staff['id']; ?>">
				<button type="submit" class="button"><?php esc_html_e( 'Sync now', 'bookingistic' ); ?></button>
			</form>
			<form method="post" style="display:inline;margin-inline-start:8px">
				<?php wp_nonce_field( 'bookingistic_admin_gcal' ); ?>
				<input type="hidden" name="bookingistic_admin_action" value="gcal_disconnect">
				<input type="hidden" name="staff_id" value="<?php echo (int) $bookingistic_staff['id']; ?>">
				<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Disconnect', 'bookingistic' ); ?></button>
			</form>
		<?php elseif ( $bookingistic_gcal_url ) : ?>
			<a href="<?php echo esc_url( $bookingistic_gcal_url ); ?>" class="button button-secondary"><?php esc_html_e( 'Connect Google Calendar', 'bookingistic' ); ?></a>
			<p class="description"><?php esc_html_e( 'Two-way sync: confirmed bookings appear in their calendar, and their external events automatically block booking slots. Refreshed every 15 minutes.', 'bookingistic' ); ?></p>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'To enable per-staff Google Calendar sync, add an OAuth Client ID + Secret under Bookingistic → Integrations → Google Calendar.', 'bookingistic' ); ?></p>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Assigned services', 'bookingistic' ); ?></h2>
		<?php if ( empty( $services ) ) : ?>
			<p class="bookingistic-muted"><?php esc_html_e( 'No services yet.', 'bookingistic' ); ?></p>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'Customers booking a service can be matched to any assigned staff member.', 'bookingistic' ); ?></p>
			<div class="bookingistic-staff-services">
				<?php foreach ( $services as $bookingistic_service_option ) : ?>
					<label>
						<input type="checkbox" name="service_ids[]" value="<?php echo (int) $bookingistic_service_option['id']; ?>"
							<?php checked( in_array( (int) $bookingistic_service_option['id'], (array) $assigned_services, true ) ); ?>>
						<?php echo esc_html( $bookingistic_service_option['name'] ); ?>
					</label>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Working hours', 'bookingistic' ); ?></h2>
		<p class="description"><?php esc_html_e( 'When unchecked, the staff member is off that day. Times are in the business timezone.', 'bookingistic' ); ?></p>
		<table class="widefat bookingistic-wh-table" style="max-width:620px">
			<thead><tr>
				<th><?php esc_html_e( 'Day', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'Working?', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'Start', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'End', 'bookingistic' ); ?></th>
			</tr></thead>
			<tbody>
				<?php foreach ( Staff_Page::DAYS as $bookingistic_dow => $bookingistic_dow_label ) :
					$bookingistic_row = $bookingistic_wh_per_day[ $bookingistic_dow ]; ?>
					<tr>
						<td><strong><?php echo esc_html( $bookingistic_dow_label ); ?></strong></td>
						<td><input type="checkbox" name="wh[<?php echo (int) $bookingistic_dow; ?>][on]" value="1" <?php checked( $bookingistic_row['on'] ); ?>></td>
						<td><input type="time" name="wh[<?php echo (int) $bookingistic_dow; ?>][start]" value="<?php echo esc_attr( $bookingistic_row['start'] ); ?>"></td>
						<td><input type="time" name="wh[<?php echo (int) $bookingistic_dow; ?>][end]" value="<?php echo esc_attr( $bookingistic_row['end'] ); ?>"></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Days off', 'bookingistic' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Specific dates this person is unavailable. Use the From/To range for vacations.', 'bookingistic' ); ?></p>
		<table class="widefat bookingistic-daysoff-table" style="max-width:620px" data-bookingistic-daysoff>
			<thead><tr>
				<th><?php esc_html_e( 'From', 'bookingistic' ); ?></th>
				<th><?php esc_html_e( 'To', 'bookingistic' ); ?></th>
				<th></th>
			</tr></thead>
			<tbody data-bookingistic-daysoff-rows>
				<?php
				$bookingistic_rows = $bookingistic_staff['days_off'] ?: [];
				if ( empty( $bookingistic_rows ) ) {
					$bookingistic_rows[] = [ 'from' => '', 'to' => '' ];
				}
				foreach ( $bookingistic_rows as $bookingistic_index => $bookingistic_entry ) :
					$bookingistic_from = is_array( $bookingistic_entry ) ? ( $bookingistic_entry['from'] ?? '' ) : $bookingistic_entry;
					$bookingistic_to   = is_array( $bookingistic_entry ) ? ( $bookingistic_entry['to'] ?? '' ) : $bookingistic_entry;
					?>
					<tr>
						<td><input type="date" name="days_off[<?php echo (int) $bookingistic_index; ?>][from]" value="<?php echo esc_attr( $bookingistic_from ); ?>"></td>
						<td><input type="date" name="days_off[<?php echo (int) $bookingistic_index; ?>][to]" value="<?php echo esc_attr( $bookingistic_to ); ?>"></td>
						<td><button type="button" class="button-link-delete" data-bookingistic-daysoff-remove>×</button></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot><tr><td colspan="3"><button type="button" class="button" data-bookingistic-daysoff-add><?php esc_html_e( 'Add date range', 'bookingistic' ); ?></button></td></tr></tfoot>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save staff', 'bookingistic' ); ?></button>
		</p>
	</form>

	<?php if ( ! $bookingistic_is_new ) : ?>
		<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this staff member? Existing bookings are kept, just unlinked from this staff record.', 'bookingistic' ) ); ?>')">
			<?php wp_nonce_field( 'bookingistic_admin_staff_delete' ); ?>
			<input type="hidden" name="bookingistic_admin_action" value="delete_staff">
			<input type="hidden" name="staff_id" value="<?php echo (int) $bookingistic_staff['id']; ?>">
			<p><button type="submit" class="button button-link-delete"><?php esc_html_e( 'Delete staff', 'bookingistic' ); ?></button></p>
		</form>
	<?php endif; ?>
</div>
