<?php
/**
 * Admin Integrations page.
 *
 * @var \Bookingistic\Integrations\Integration_Base[] $integrations
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;

$saved_slug = sanitize_key( $_GET['saved'] ?? '' );
?>
<div class="wrap bookingistic-wrap">
	<h1><?php esc_html_e( 'Integrations', 'bookingistic' ); ?></h1>
	<p class="bookingistic-muted"><?php esc_html_e( 'Plug Bookingistic into the rest of your stack. Each integration is opt-in and only loads its hooks when enabled.', 'bookingistic' ); ?></p>

	<?php foreach ( $integrations as $integration ) :
		$slug        = $integration->slug();
		$available   = $integration->is_available();
		$enabled     = $integration->is_enabled();
		$fields      = $integration->fields();
		$current     = $integration->all_settings();
		?>
		<div class="bookingistic-card" style="margin-top:16px">
			<h2 style="display:flex;align-items:center;gap:10px">
				<?php echo esc_html( $integration->label() ); ?>
				<?php if ( ! $available ) : ?>
					<span class="bookingistic-pill bookingistic-pill--cancelled"><?php esc_html_e( 'Not available', 'bookingistic' ); ?></span>
				<?php elseif ( $enabled ) : ?>
					<span class="bookingistic-pill bookingistic-pill--confirmed"><?php esc_html_e( 'Enabled', 'bookingistic' ); ?></span>
				<?php else : ?>
					<span class="bookingistic-pill bookingistic-pill--pending"><?php esc_html_e( 'Disabled', 'bookingistic' ); ?></span>
				<?php endif; ?>
			</h2>
			<p class="bookingistic-muted"><?php echo esc_html( $integration->description() ); ?></p>

			<?php if ( $saved_slug === $slug ) : ?>
				<div class="notice notice-success inline"><p><?php esc_html_e( 'Saved.', 'bookingistic' ); ?></p></div>
			<?php endif; ?>

			<?php if ( ! $available ) : ?>
				<p>
					<?php
					if ( $slug === 'woocommerce' ) {
						esc_html_e( 'WooCommerce is not active on this site. Install and activate WooCommerce to use this integration.', 'bookingistic' );
					} else {
						esc_html_e( 'Required dependencies are not available in this environment.', 'bookingistic' );
					}
					?>
				</p>
			<?php else : ?>
				<form method="post">
					<?php wp_nonce_field( 'bookingistic_admin_integration' ); ?>
					<input type="hidden" name="bookingistic_admin_action" value="save_integration">
					<input type="hidden" name="integration_slug" value="<?php echo esc_attr( $slug ); ?>">

					<table class="form-table">
						<tr>
							<th><label><?php esc_html_e( 'Enabled', 'bookingistic' ); ?></label></th>
							<td>
								<label>
									<input type="checkbox" name="_enabled" value="1" <?php checked( $enabled ); ?>>
									<?php esc_html_e( 'Activate this integration', 'bookingistic' ); ?>
								</label>
							</td>
						</tr>
						<?php foreach ( $fields as $key => $schema ) :
							$value = $current[ $key ] ?? '';
							$type  = $schema['type'] ?? 'text';
							?>
							<tr>
								<th><label for="<?php echo esc_attr( $slug . '_' . $key ); ?>"><?php echo esc_html( $schema['label'] ?? $key ); ?></label></th>
								<td>
									<?php if ( $type === 'checkbox' ) : ?>
										<label>
											<input type="checkbox" id="<?php echo esc_attr( $slug . '_' . $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="1" <?php checked( ! empty( $value ) ); ?>>
											<?php echo ! empty( $schema['description'] ) ? esc_html( $schema['description'] ) : ''; ?>
										</label>
									<?php elseif ( $type === 'textarea' ) : ?>
										<textarea id="<?php echo esc_attr( $slug . '_' . $key ); ?>" name="<?php echo esc_attr( $key ); ?>" class="large-text" rows="4"><?php echo esc_textarea( (string) $value ); ?></textarea>
										<?php if ( ! empty( $schema['description'] ) ) : ?>
											<p class="description"><?php echo esc_html( $schema['description'] ); ?></p>
										<?php endif; ?>
									<?php elseif ( $type === 'password' ) : ?>
										<input type="password" id="<?php echo esc_attr( $slug . '_' . $key ); ?>" name="<?php echo esc_attr( $key ); ?>" class="regular-text" value="<?php echo esc_attr( (string) $value ); ?>" autocomplete="off"
											placeholder="<?php echo esc_attr( $schema['placeholder'] ?? '' ); ?>">
										<?php if ( ! empty( $schema['description'] ) ) : ?>
											<p class="description"><?php echo esc_html( $schema['description'] ); ?></p>
										<?php endif; ?>
									<?php else : ?>
										<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $slug . '_' . $key ); ?>" name="<?php echo esc_attr( $key ); ?>" class="regular-text" value="<?php echo esc_attr( (string) $value ); ?>"
											placeholder="<?php echo esc_attr( $schema['placeholder'] ?? '' ); ?>">
										<?php if ( ! empty( $schema['description'] ) ) : ?>
											<p class="description"><?php echo esc_html( $schema['description'] ); ?></p>
										<?php endif; ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
					<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'bookingistic' ); ?></button></p>
				</form>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
