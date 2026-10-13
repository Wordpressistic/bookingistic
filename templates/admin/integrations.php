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

$saved_slug = sanitize_key( $_GET['saved'] ?? '' );
?>
<div class="wrap bookingistic-wrap">
	<header class="bookingistic-header">
		<h1><?php esc_html_e( 'Integrations', 'bookingistic' ); ?><span class="bookingistic-header__badges"><span class="bookingistic-header__badge"><span class="dashicons dashicons-plugins"></span><?php echo esc_html( count( $integrations ) ); ?></span></span></h1>
		<p class="bookingistic-header__tagline"><?php esc_html_e( 'Plug Bookingistic into the rest of your stack. Each integration is opt-in and only loads its hooks when enabled.', 'bookingistic' ); ?></p>
	</header>

	<?php if ( $saved_slug ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Integration settings saved.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>

	<div class="bookingistic-integrations">
		<?php $card_index = 0; ?>
		<?php foreach ( $integrations as $integration ) :
			$slug      = $integration->slug();
			$available = $integration->is_available();
			$enabled   = $integration->is_enabled();
			$fields    = $integration->fields();
			$current   = $integration->all_settings();
			$meta      = $ui_map[ $slug ] ?? [ 'icon' => 'dashicons-admin-generic', 'tag' => '' ];
			$connected = $enabled && $available;
			$state_key = ! $available ? 'disabled' : ( $enabled ? 'confirmed' : 'pending' );
			?>
			<div class="bookingistic-integration<?php echo $connected ? ' bookingistic-integration--connected' : ''; ?>" style="--bk-i: <?php echo (int) $card_index++; ?>">
				<div class="bookingistic-integration__icon"><span class="dashicons <?php echo esc_attr( $meta['icon'] ); ?>"></span></div>

				<div class="bookingistic-integration__head">
					<h2 class="bookingistic-integration__name"><?php echo esc_html( $integration->label() ); ?></h2>
					<span class="bookingistic-pill bookingistic-pill--<?php echo esc_attr( $state_key ); ?>">
						<?php
						if ( ! $available ) {
							esc_html_e( 'Not available', 'bookingistic' );
						} elseif ( $enabled ) {
							esc_html_e( 'Connected', 'bookingistic' );
						} else {
							esc_html_e( 'Not connected', 'bookingistic' );
						}
						?>
					</span>
				</div>

				<?php if ( '' !== $meta['tag'] ) : ?>
					<span class="bookingistic-header__badge" style="align-self:flex-start;margin-bottom:2px"><?php echo esc_html( $meta['tag'] ); ?></span>
				<?php endif; ?>

				<p class="bookingistic-integration__desc"><?php echo esc_html( $integration->description() ); ?></p>

				<div class="bookingistic-integration__foot">
					<?php if ( $saved_slug === $slug ) : ?>
						<span class="bookingistic-pill bookingistic-pill--confirmed"><?php esc_html_e( 'Saved', 'bookingistic' ); ?></span>
					<?php endif; ?>
					<?php if ( ! $available ) : ?>
						<span class="bookingistic-muted">
							<?php
							if ( $slug === 'woocommerce' ) {
								esc_html_e( 'WooCommerce is not active on this site. Install and activate WooCommerce to use this integration.', 'bookingistic' );
							} else {
								esc_html_e( 'Required dependencies are not available in this environment.', 'bookingistic' );
							}
							?>
						</span>
					<?php else : ?>
						<button type="button" class="button <?php echo $enabled ? '' : 'button-primary'; ?>" data-bookingistic-toggle-panel="<?php echo esc_attr( $slug ); ?>">
							<?php echo $enabled ? esc_html__( 'Configure', 'bookingistic' ) : esc_html__( 'Connect', 'bookingistic' ); ?>
						</button>
					<?php endif; ?>
				</div>

				<?php if ( $available ) : ?>
					<div class="bookingistic-integration__panel" id="bookingistic-panel-<?php echo esc_attr( $slug ); ?>" hidden>
						<form method="post">
							<?php wp_nonce_field( 'bookingistic_admin_integration' ); ?>
							<input type="hidden" name="bookingistic_admin_action" value="save_integration">
							<input type="hidden" name="integration_slug" value="<?php echo esc_attr( $slug ); ?>">

							<label class="bookingistic-integration__check">
								<input type="checkbox" name="_enabled" value="1" <?php checked( $enabled ); ?>>
								<?php esc_html_e( 'Activate this integration', 'bookingistic' ); ?>
							</label>

							<?php foreach ( $fields as $key => $schema ) :
								$value = $current[ $key ] ?? '';
								$type  = $schema['type'] ?? 'text';
								?>
								<?php if ( 'checkbox' === $type ) : ?>
									<label class="bookingistic-integration__check">
										<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1" <?php checked( ! empty( $value ) ); ?>>
										<?php echo '' !== (string) ( $schema['description'] ?? '' ) ? esc_html( (string) $schema['description'] ) : esc_html( (string) ( $schema['label'] ?? $key ) ); ?>
									</label>
								<?php else : ?>
									<label>
										<?php echo esc_html( (string) ( $schema['label'] ?? $key ) ); ?>
										<?php if ( 'textarea' === $type ) : ?>
											<textarea name="<?php echo esc_attr( $key ); ?>" rows="4"><?php echo esc_textarea( (string) $value ); ?></textarea>
										<?php else : ?>
											<input type="<?php echo esc_attr( 'password' === $type ? 'password' : $type ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" autocomplete="off" placeholder="<?php echo esc_attr( (string) ( $schema['placeholder'] ?? '' ) ); ?>">
										<?php endif; ?>
										<?php if ( 'checkbox' !== $type && ! empty( $schema['description'] ) ) : ?>
											<span class="description"><?php echo esc_html( (string) $schema['description'] ); ?></span>
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
