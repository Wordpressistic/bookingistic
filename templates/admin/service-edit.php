<?php
/**
 * Admin service edit form.
 *
 * @var array|null $service
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;
$is_new = empty( $service );
?>
<div class="wrap bookingistic-wrap">
	<h1><?php echo $is_new ? esc_html__( 'Add service', 'bookingistic' ) : esc_html__( 'Edit service', 'bookingistic' ); ?></h1>
	<form method="post" action="">
		<?php wp_nonce_field( 'bookingistic_admin_service' ); ?>
		<input type="hidden" name="bookingistic_admin_action" value="save_service">
		<input type="hidden" name="service_id" value="<?php echo (int) ( $service['id'] ?? 0 ); ?>">

		<table class="form-table">
			<tr>
				<th><label for="name"><?php esc_html_e( 'Name', 'bookingistic' ); ?></label></th>
				<td><input id="name" name="name" type="text" class="regular-text" required value="<?php echo esc_attr( $service['name'] ?? '' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="slug"><?php esc_html_e( 'Slug', 'bookingistic' ); ?></label></th>
				<td><input id="slug" name="slug" type="text" class="regular-text" value="<?php echo esc_attr( $service['slug'] ?? '' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="short_description"><?php esc_html_e( 'Short description', 'bookingistic' ); ?></label></th>
				<td><input id="short_description" name="short_description" type="text" class="large-text" value="<?php echo esc_attr( $service['short_description'] ?? '' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="description"><?php esc_html_e( 'Description', 'bookingistic' ); ?></label></th>
				<td><textarea id="description" name="description" rows="4" class="large-text"><?php echo esc_textarea( $service['description'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="duration_minutes"><?php esc_html_e( 'Duration (minutes)', 'bookingistic' ); ?></label></th>
				<td><input id="duration_minutes" name="duration_minutes" type="number" min="1" value="<?php echo esc_attr( $service['duration_minutes'] ?? 30 ); ?>"></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Buffer before / after (min)', 'bookingistic' ); ?></th>
				<td>
					<input name="buffer_before" type="number" min="0" value="<?php echo esc_attr( $service['buffer_before'] ?? 0 ); ?>" style="width:80px">
					/
					<input name="buffer_after" type="number" min="0" value="<?php echo esc_attr( $service['buffer_after'] ?? 0 ); ?>" style="width:80px">
				</td>
			</tr>
			<tr>
				<th><label for="price"><?php esc_html_e( 'Price', 'bookingistic' ); ?></label></th>
				<td><input id="price" name="price" type="number" step="0.01" min="0" value="<?php echo esc_attr( $service['price'] ?? 0 ); ?>"></td>
			</tr>
			<tr>
				<th><label for="category"><?php esc_html_e( 'Category', 'bookingistic' ); ?></label></th>
				<td><input id="category" name="category" type="text" value="<?php echo esc_attr( $service['category'] ?? '' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="booking_type"><?php esc_html_e( 'Booking type', 'bookingistic' ); ?></label></th>
				<td>
					<select id="booking_type" name="booking_type">
						<?php foreach ( [ 'one_to_one', 'service', 'call', 'group', 'resource' ] as $t ) : ?>
							<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $service['booking_type'] ?? 'one_to_one', $t ); ?>><?php echo esc_html( $t ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="confirmation_mode"><?php esc_html_e( 'Confirmation', 'bookingistic' ); ?></label></th>
				<td>
					<select id="confirmation_mode" name="confirmation_mode">
						<option value="auto"   <?php selected( $service['confirmation_mode'] ?? 'auto', 'auto' ); ?>><?php esc_html_e( 'Auto-confirm', 'bookingistic' ); ?></option>
						<option value="manual" <?php selected( $service['confirmation_mode'] ?? 'auto', 'manual' ); ?>><?php esc_html_e( 'Manual approval', 'bookingistic' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="max_attendees"><?php esc_html_e( 'Max attendees', 'bookingistic' ); ?></label></th>
				<td><input id="max_attendees" name="max_attendees" type="number" min="1" value="<?php echo esc_attr( $service['max_attendees'] ?? 1 ); ?>"></td>
			</tr>
			<tr>
				<th><label for="status"><?php esc_html_e( 'Status', 'bookingistic' ); ?></label></th>
				<td>
					<select id="status" name="status">
						<option value="active"   <?php selected( $service['status'] ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'bookingistic' ); ?></option>
						<option value="inactive" <?php selected( $service['status'] ?? 'active', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'bookingistic' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="sort_order"><?php esc_html_e( 'Sort order', 'bookingistic' ); ?></label></th>
				<td><input id="sort_order" name="sort_order" type="number" value="<?php echo esc_attr( $service['sort_order'] ?? 0 ); ?>"></td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'bookingistic' ); ?></button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-services' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'bookingistic' ); ?></a>
		</p>
	</form>
</div>
