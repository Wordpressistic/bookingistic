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

$is_new = empty( $staff );
$staff  = $staff ?: [
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
$wh_per_day = [];
foreach ( Staff_Page::DAYS as $dow => $_label ) {
	$wh_per_day[ $dow ] = [ 'on' => false, 'start' => '09:00', 'end' => '17:00' ];
}
$wh = $staff['working_hours'];
if ( isset( $wh['start'], $wh['end'] ) ) {
	// Legacy shape.
	$days = ! empty( $wh['days'] ) ? array_map( 'intval', (array) $wh['days'] ) : [ 1, 2, 3, 4, 5 ];
	foreach ( $days as $d ) {
		if ( isset( $wh_per_day[ $d ] ) ) {
			$wh_per_day[ $d ] = [ 'on' => true, 'start' => $wh['start'], 'end' => $wh['end'] ];
		}
	}
} else {
	foreach ( $wh as $key => $entry ) {
		$d = (int) $key;
		if ( isset( $wh_per_day[ $d ] ) && is_array( $entry ) && ! empty( $entry['start'] ) && ! empty( $entry['end'] ) ) {
			$wh_per_day[ $d ] = [ 'on' => true, 'start' => $entry['start'], 'end' => $entry['end'] ];
		}
	}
}
?>
<div class="wrap bookingistic-wrap">
	<h1>
		<?php echo $is_new ? esc_html__( 'Add staff', 'bookingistic' ) : esc_html__( 'Edit staff', 'bookingistic' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-staff' ) ); ?>" class="page-title-action"><?php esc_html_e( '← Back', 'bookingistic' ); ?></a>
	</h1>

	<?php if ( ! empty( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Staff saved.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['error'] ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( wp_unslash( $_GET['error'] ) ); ?></p></div>
	<?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'bookingistic_admin_staff' ); ?>
		<input type="hidden" name="bookingistic_admin_action" value="save_staff">
		<input type="hidden" name="staff_id" value="<?php echo (int) $staff['id']; ?>">

		<h2><?php esc_html_e( 'Profile', 'bookingistic' ); ?></h2>
		<table class="form-table">
			<tr><th><label><?php esc_html_e( 'Name', 'bookingistic' ); ?></label></th>
				<td><input type="text" name="name" class="regular-text" required value="<?php echo esc_attr( $staff['name'] ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Email', 'bookingistic' ); ?></label></th>
				<td><input type="email" name="email" class="regular-text" required value="<?php echo esc_attr( $staff['email'] ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Phone', 'bookingistic' ); ?></label></th>
				<td><input type="tel" name="phone" class="regular-text" value="<?php echo esc_attr( $staff['phone'] ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Photo URL', 'bookingistic' ); ?></label></th>
				<td><input type="url" name="photo_url" class="regular-text" value="<?php echo esc_attr( $staff['photo_url'] ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Bio', 'bookingistic' ); ?></label></th>
				<td><textarea name="bio" rows="3" class="large-text"><?php echo esc_textarea( $staff['bio'] ); ?></textarea></td></tr>
			<tr><th><label><?php esc_html_e( 'Daily booking limit', 'bookingistic' ); ?></label></th>
				<td><input type="number" name="booking_limit_per_day" min="0" value="<?php echo (int) $staff['booking_limit_per_day']; ?>">
					<p class="description"><?php esc_html_e( '0 = unlimited.', 'bookingistic' ); ?></p></td></tr>
			<tr><th><label><?php esc_html_e( 'Status', 'bookingistic' ); ?></label></th>
				<td><select name="status">
					<option value="active"   <?php selected( $staff['status'], 'active' ); ?>><?php esc_html_e( 'Active', 'bookingistic' ); ?></option>
					<option value="inactive" <?php selected( $staff['status'], 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'bookingistic' ); ?></option>
				</select></td></tr>
		</table>

		<h2><?php esc_html_e( 'Assigned services', 'bookingistic' ); ?></h2>
		<?php if ( empty( $services ) ) : ?>
			<p class="bookingistic-muted"><?php esc_html_e( 'No services yet.', 'bookingistic' ); ?></p>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'Customers booking a service can be matched to any assigned staff member.', 'bookingistic' ); ?></p>
			<div class="bookingistic-staff-services">
				<?php foreach ( $services as $svc ) : ?>
					<label>
						<input type="checkbox" name="service_ids[]" value="<?php echo (int) $svc['id']; ?>"
							<?php checked( in_array( (int) $svc['id'], (array) $assigned_services, true ) ); ?>>
						<?php echo esc_html( $svc['name'] ); ?>
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
				<?php foreach ( Staff_Page::DAYS as $dow => $label ) :
					$row = $wh_per_day[ $dow ]; ?>
					<tr>
						<td><strong><?php echo esc_html( $label ); ?></strong></td>
						<td><input type="checkbox" name="wh[<?php echo (int) $dow; ?>][on]" value="1" <?php checked( $row['on'] ); ?>></td>
						<td><input type="time" name="wh[<?php echo (int) $dow; ?>][start]" value="<?php echo esc_attr( $row['start'] ); ?>"></td>
						<td><input type="time" name="wh[<?php echo (int) $dow; ?>][end]" value="<?php echo esc_attr( $row['end'] ); ?>"></td>
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
				$rows = $staff['days_off'] ?: [];
				if ( empty( $rows ) ) {
					$rows[] = [ 'from' => '', 'to' => '' ];
				}
				foreach ( $rows as $i => $entry ) :
					$from = is_array( $entry ) ? ( $entry['from'] ?? '' ) : $entry;
					$to   = is_array( $entry ) ? ( $entry['to'] ?? '' ) : $entry;
					?>
					<tr>
						<td><input type="date" name="days_off[<?php echo (int) $i; ?>][from]" value="<?php echo esc_attr( $from ); ?>"></td>
						<td><input type="date" name="days_off[<?php echo (int) $i; ?>][to]" value="<?php echo esc_attr( $to ); ?>"></td>
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

	<?php if ( ! $is_new ) : ?>
		<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this staff member? Existing bookings are kept, just unlinked from this staff record.', 'bookingistic' ) ); ?>')">
			<?php wp_nonce_field( 'bookingistic_admin_staff_delete' ); ?>
			<input type="hidden" name="bookingistic_admin_action" value="delete_staff">
			<input type="hidden" name="staff_id" value="<?php echo (int) $staff['id']; ?>">
			<p><button type="submit" class="button button-link-delete"><?php esc_html_e( 'Delete staff', 'bookingistic' ); ?></button></p>
		</form>
	<?php endif; ?>
</div>
