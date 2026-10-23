<?php
/**
 * Admin Integrations page — animated card grid.
 *
 * @var \Bookingistic\Integrations\Integration_Base[] $integrations
 * @var array<string,array{icon:string,tag:string}>   $ui_map
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only saved-notice.
$bookingistic_saved_slug = isset( $_GET['saved'] ) ? sanitize_key( wp_unslash( $_GET['saved'] ) ) : '';
?>
<div class="wrap bookingistic-wrap">
	<header class="bookingistic-header">
		<h1><?php esc_html_e( 'Integrations', 'bookingistic' ); ?><span class="bookingistic-header__badges"><span class="bookingistic-header__badge"><span class="dashicons dashicons-plugins"></span><?php echo esc_html( count( $integrations ) ); ?></span></span></h1>
		<p class="bookingistic-header__tagline"><?php esc_html_e( 'Plug Bookingistic into the rest of your stack. Each integration is opt-in and only loads its hooks when enabled.', 'bookingistic' ); ?></p>
	</header>

	<?php if ( $bookingistic_saved_slug ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Integration settings saved.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>

	<div class="bookingistic-integrations">
		<?php $bookingistic_card_index = 0; ?>
		<?php foreach ( $integrations as $bookingistic_integration ) :
			$bookingistic_slug      = $bookingistic_integration->slug();
			$bookingistic_available = $bookingistic_integration->is_available();
			$bookingistic_enabled   = $bookingistic_integration->is_enabled();
			$bookingistic_fields    = $bookingistic_integration->fields();
			$bookingistic_current   = $bookingistic_integration->all_settings();
			$bookingistic_meta      = $ui_map[ $bookingistic_slug ] ?? [ 'icon' => 'dashicons-admin-generic', 'tag' => '' ];
			$bookingistic_connected = $bookingistic_enabled && $bookingistic_available;
			$bookingistic_state_key = ! $bookingistic_available ? 'disabled' : ( $bookingistic_enabled ? 'confirmed' : 'pending' );
			?>
			<div class="bookingistic-integration<?php echo $bookingistic_connected ? ' bookingistic-integration--connected' : ''; ?>" style="--bk-i: <?php echo (int) $bookingistic_card_index++; ?>">
				<div class="bookingistic-integration__icon"><span class="dashicons <?php echo esc_attr( $bookingistic_meta['icon'] ); ?>"></span></div>

				<div class="bookingistic-integration__head">
					<h2 class="bookingistic-integration__name"><?php echo esc_html( $bookingistic_integration->label() ); ?></h2>
					<span class="bookingistic-pill bookingistic-pill--<?php echo esc_attr( $bookingistic_state_key ); ?>">
						<?php
						if ( ! $bookingistic_available ) {
							esc_html_e( 'Not available', 'bookingistic' );
						} elseif ( $bookingistic_enabled ) {
							esc_html_e( 'Connected', 'bookingistic' );
						} else {
							esc_html_e( 'Not connected', 'bookingistic' );
						}
						?>
					</span>
				</div>

				<?php if ( '' !== $bookingistic_meta['tag'] ) : ?>
					<span class="bookingistic-header__badge" style="align-self:flex-start;margin-bottom:2px"><?php echo esc_html( $bookingistic_meta['tag'] ); ?></span>
				<?php endif; ?>

				<p class="bookingistic-integration__desc"><?php echo esc_html( $bookingistic_integration->description() ); ?></p>

				<div class="bookingistic-integration__foot">
					<?php if ( $bookingistic_saved_slug === $bookingistic_slug ) : ?>
						<span class="bookingistic-pill bookingistic-pill--confirmed"><?php esc_html_e( 'Saved', 'bookingistic' ); ?></span>
					<?php endif; ?>
					<?php if ( ! $bookingistic_available ) : ?>
						<span class="bookingistic-muted">
							<?php
							if ( $bookingistic_slug === 'woocommerce' ) {
								esc_html_e( 'WooCommerce is not active on this site. Install and activate WooCommerce to use this integration.', 'bookingistic' );
							} else {
								esc_html_e( 'Required dependencies are not available in this environment.', 'bookingistic' );
							}
							?>
						</span>
					<?php else : ?>
						<button type="button" class="button <?php echo $bookingistic_enabled ? '' : 'button-primary'; ?>" data-bookingistic-toggle-panel="<?php echo esc_attr( $bookingistic_slug ); ?>">
							<?php echo $bookingistic_enabled ? esc_html__( 'Configure', 'bookingistic' ) : esc_html__( 'Connect', 'bookingistic' ); ?>
						</button>
					<?php endif; ?>
				</div>

				<?php if ( $bookingistic_available ) : ?>
					<div class="bookingistic-integration__panel" id="bookingistic-panel-<?php echo esc_attr( $bookingistic_slug ); ?>" hidden>
						<form method="post">
							<?php wp_nonce_field( 'bookingistic_admin_integration' ); ?>
							<input type="hidden" name="bookingistic_admin_action" value="save_integration">
							<input type="hidden" name="integration_slug" value="<?php echo esc_attr( $bookingistic_slug ); ?>">

							<label class="bookingistic-integration__check">
								<input type="checkbox" name="_enabled" value="1" <?php checked( $bookingistic_enabled ); ?>>
								<?php esc_html_e( 'Activate this integration', 'bookingistic' ); ?>
							</label>

							<?php foreach ( $bookingistic_fields as $bookingistic_field_key => $bookingistic_field_schema ) :
								$bookingistic_field_value = $bookingistic_current[ $bookingistic_field_key ] ?? '';
								$bookingistic_field_type  = $bookingistic_field_schema['type'] ?? 'text';
								?>
								<?php if ( 'checkbox' === $bookingistic_field_type ) : ?>
									<label class="bookingistic-integration__check">
										<input type="checkbox" name="<?php echo esc_attr( $bookingistic_field_key ); ?>" value="1" <?php checked( ! empty( $bookingistic_field_value ) ); ?>>
										<?php echo '' !== (string) ( $bookingistic_field_schema['description'] ?? '' ) ? esc_html( (string) $bookingistic_field_schema['description'] ) : esc_html( (string) ( $bookingistic_field_schema['label'] ?? $bookingistic_field_key ) ); ?>
									</label>
								<?php else : ?>
									<label>
										<?php echo esc_html( (string) ( $bookingistic_field_schema['label'] ?? $bookingistic_field_key ) ); ?>
										<?php if ( 'textarea' === $bookingistic_field_type ) : ?>
											<textarea name="<?php echo esc_attr( $bookingistic_field_key ); ?>" rows="4"><?php echo esc_textarea( (string) $bookingistic_field_value ); ?></textarea>
										<?php else : ?>
											<input type="<?php echo esc_attr( 'password' === $bookingistic_field_type ? 'password' : $bookingistic_field_type ); ?>" name="<?php echo esc_attr( $bookingistic_field_key ); ?>" value="<?php echo esc_attr( (string) $bookingistic_field_value ); ?>" autocomplete="off" placeholder="<?php echo esc_attr( (string) ( $bookingistic_field_schema['placeholder'] ?? '' ) ); ?>">
										<?php endif; ?>
										<?php if ( 'checkbox' !== $bookingistic_field_type && ! empty( $bookingistic_field_schema['description'] ) ) : ?>
											<span class="description"><?php echo esc_html( (string) $bookingistic_field_schema['description'] ); ?></span>
										<?php endif; ?>
									</label>
								<?php endif; ?>
							<?php endforeach; ?>

							<p style="margin:4px 0 0">
								<button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'bookingistic' ); ?></button>
							</p>
						</form>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
