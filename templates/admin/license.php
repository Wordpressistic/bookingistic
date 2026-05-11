<?php
/**
 * Admin license template.
 *
 * @var array $state
 * @var bool  $is_active
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;

use Bookingistic\Core\License_Manager;
?>
<div class="wrap bookingistic-wrap">
	<h1><?php esc_html_e( 'License', 'bookingistic' ); ?></h1>

	<?php if ( isset( $_GET['ok'] ) ) : ?>
		<div class="notice notice-<?php echo $_GET['ok'] === '1' ? 'success' : 'error'; ?>">
			<p><?php echo esc_html( wp_unslash( $_GET['message'] ?? '' ) ); ?></p>
		</div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['deactivated'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'License removed from this site.', 'bookingistic' ); ?></p></div>
	<?php endif; ?>

	<div class="bookingistic-detail-grid">
		<div>
			<div class="bookingistic-card">
				<h2><?php esc_html_e( 'Premium license', 'bookingistic' ); ?></h2>
				<p class="bookingistic-muted">
					<?php
					printf(
						/* translators: %s: account URL */
						wp_kses(
							__( 'Don\'t have a key yet? Create an account on <a href="%s" target="_blank" rel="noopener">WordPressistic.com</a> to purchase or claim your license.', 'bookingistic' ),
							[ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ]
						),
						esc_url( License_Manager::ACCOUNT_URL )
					);
					?>
				</p>

				<form method="post">
					<?php wp_nonce_field( 'bookingistic_admin_license' ); ?>
					<input type="hidden" name="bookingistic_admin_action" value="activate_license">
					<table class="form-table">
						<tr>
							<th><label for="license_key"><?php esc_html_e( 'License key', 'bookingistic' ); ?></label></th>
							<td>
								<input type="text"
									id="license_key"
									name="license_key"
									class="regular-text code"
									autocomplete="off"
									placeholder="WPSTC-XXXX-XXXX-XXXX"
									value="<?php echo esc_attr( $state['key'] ); ?>">
								<p class="description">
									<?php esc_html_e( 'Paste the key from your WordPressistic.com account dashboard.', 'bookingistic' ); ?>
								</p>
							</td>
						</tr>
					</table>
					<p class="submit">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Activate license', 'bookingistic' ); ?></button>
						<a href="<?php echo esc_url( License_Manager::ACCOUNT_URL ); ?>" target="_blank" rel="noopener" class="button"><?php esc_html_e( 'Open account', 'bookingistic' ); ?></a>
					</p>
				</form>

				<?php if ( $state['key'] ) : ?>
					<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Remove this license from the site?', 'bookingistic' ) ); ?>')">
						<?php wp_nonce_field( 'bookingistic_admin_license' ); ?>
						<input type="hidden" name="bookingistic_admin_action" value="deactivate_license">
						<p><button type="submit" class="button button-link-delete"><?php esc_html_e( 'Deactivate this license', 'bookingistic' ); ?></button></p>
					</form>
				<?php endif; ?>
			</div>
		</div>

		<aside>
			<div class="bookingistic-card">
				<h2><?php esc_html_e( 'Status', 'bookingistic' ); ?></h2>
				<dl class="bookingistic-detail">
					<dt><?php esc_html_e( 'License', 'bookingistic' ); ?></dt>
					<dd>
						<?php if ( $is_active ) : ?>
							<span class="bookingistic-pill bookingistic-pill--confirmed"><?php esc_html_e( 'Active', 'bookingistic' ); ?></span>
						<?php elseif ( $state['key'] ) : ?>
							<span class="bookingistic-pill bookingistic-pill--cancelled"><?php echo esc_html( $state['status'] ?: 'inactive' ); ?></span>
						<?php else : ?>
							<span class="bookingistic-pill bookingistic-pill--pending"><?php esc_html_e( 'No key', 'bookingistic' ); ?></span>
						<?php endif; ?>
					</dd>
					<dt><?php esc_html_e( 'Plan', 'bookingistic' ); ?></dt>
					<dd><?php echo esc_html( $state['plan'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Expires', 'bookingistic' ); ?></dt>
					<dd><?php echo esc_html( $state['expires_at'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Last checked', 'bookingistic' ); ?></dt>
					<dd><?php echo esc_html( $state['last_checked'] ?: '—' ); ?></dd>
					<?php if ( $state['message'] ) : ?>
						<dt><?php esc_html_e( 'Message', 'bookingistic' ); ?></dt>
						<dd><?php echo esc_html( $state['message'] ); ?></dd>
					<?php endif; ?>
				</dl>
			</div>

			<div class="bookingistic-card" style="margin-top:16px">
				<h2><?php esc_html_e( 'What premium unlocks', 'bookingistic' ); ?></h2>
				<ul style="margin:0;padding-left:18px">
					<li><?php esc_html_e( 'Remove WordPressistic branding from automated emails', 'bookingistic' ); ?></li>
					<li><?php esc_html_e( 'Stripe Checkout payments', 'bookingistic' ); ?></li>
					<li><?php esc_html_e( 'Group bookings with seat capacity', 'bookingistic' ); ?></li>
					<li><?php esc_html_e( 'Advanced reports', 'bookingistic' ); ?></li>
					<li><?php esc_html_e( 'Priority support', 'bookingistic' ); ?></li>
				</ul>
			</div>
		</aside>
	</div>
</div>
