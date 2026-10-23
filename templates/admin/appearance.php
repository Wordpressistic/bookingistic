<?php
/**
 * Admin Appearance page template.
 *
 * @package Bookingistic
 *
 * @var array $appearance   Current appearance settings.
 * @var array $templates    Form templates (key => meta).
 * @var array $calendars    Calendar views (key => label).
 * @var array $industries   Industry presets (key => meta).
 * @var string $sample_form Rendered preview HTML.
 */

defined( 'ABSPATH' ) || exit;

use Bookingistic\Admin\Appearance_Page;
use Bookingistic\Frontend\Template_Loader;
?>
<div class="wrap bookingistic-appearance">
	<h1><?php esc_html_e( 'Bookingistic — Appearance', 'bookingistic' ); ?></h1>

	<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display notice. ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Appearance saved.', 'bookingistic' ); ?></p></div>
	<?php elseif ( ! empty( $_GET['industry'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display notice. ?>
		<div class="notice notice-success is-dismissible"><p>
			<?php
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display notice.
			$bookingistic_industry = Template_Loader::industries()[ sanitize_key( wp_unslash( $_GET['industry'] ) ) ] ?? null;
			echo esc_html(
				$bookingistic_industry
					/* translators: %s: industry label */
					? sprintf( __( 'Industry preset applied: %s', 'bookingistic' ), $bookingistic_industry['label'] )
					: __( 'Industry preset applied.', 'bookingistic' )
			);
			?>
		</p></div>
	<?php endif; ?>

	<div class="bookingistic-appearance__grid">
		<div class="bookingistic-appearance__form">
			<h2><?php esc_html_e( 'One-click industry preset', 'bookingistic' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Applies a template, calendar style, brand color and scheduling defaults tuned for your type of business. You can fine-tune everything below afterwards.', 'bookingistic' ); ?></p>
			<form method="post" action="">
				<?php wp_nonce_field( Appearance_Page::NONCE ); ?>
				<input type="hidden" name="bookingistic_admin_action" value="apply_industry">
				<p>
					<select name="industry">
						<?php foreach ( $industries as $bookingistic_key => $bookingistic_industry ) : ?>
							<option value="<?php echo esc_attr( $bookingistic_key ); ?>">
								<?php echo esc_html( $bookingistic_industry['label'] ); ?>
								— <?php echo esc_html( $templates[ $bookingistic_industry['template'] ]['label'] ?? '' ); ?> · <?php echo esc_html( $calendars[ $bookingistic_industry['calendar'] ] ?? '' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="submit" class="button button-secondary"><?php esc_html_e( 'Apply preset', 'bookingistic' ); ?></button>
				</p>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Design', 'bookingistic' ); ?></h2>
			<form method="post" action="">
				<?php wp_nonce_field( Appearance_Page::NONCE ); ?>
				<input type="hidden" name="bookingistic_admin_action" value="save_appearance">

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="bk-template"><?php esc_html_e( 'Form template', 'bookingistic' ); ?></label></th>
						<td>
							<select name="template" id="bk-template">
								<?php foreach ( $templates as $bookingistic_key => $bookingistic_meta ) : ?>
									<option value="<?php echo esc_attr( $bookingistic_key ); ?>" <?php selected( $appearance['template'], $bookingistic_key ); ?>>
										<?php echo esc_html( $bookingistic_meta['label'] ); ?> — <?php echo esc_html( $bookingistic_meta['description'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Tip: any template can be overridden per shortcode with [bookingistic template="modern"].', 'bookingistic' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bk-calendar"><?php esc_html_e( 'Calendar view', 'bookingistic' ); ?></label></th>
						<td>
							<select name="calendar" id="bk-calendar">
								<?php foreach ( $calendars as $bookingistic_key => $bookingistic_label ) : ?>
									<option value="<?php echo esc_attr( $bookingistic_key ); ?>" <?php selected( $appearance['calendar'], $bookingistic_key ); ?>><?php echo esc_html( $bookingistic_label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Month grid = classic date picker · Pills = horizontal next-14-days scroller · List = upcoming dates with Today/Tomorrow labels.', 'bookingistic' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bk-accent"><?php esc_html_e( 'Brand accent color', 'bookingistic' ); ?></label></th>
						<td>
							<input type="color" name="accent" id="bk-accent" value="<?php echo esc_attr( $appearance['accent'] ); ?>">
							<p class="description"><?php esc_html_e( 'Used for buttons, selected dates and links inside the booking form.', 'bookingistic' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bk-radius"><?php esc_html_e( 'Corner radius', 'bookingistic' ); ?></label></th>
						<td>
							<input type="number" name="radius" id="bk-radius" min="0" max="32" value="<?php echo esc_attr( $appearance['radius'] ); ?>" class="small-text"> px
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save appearance', 'bookingistic' ) ); ?>
			</form>
		</div>

		<div class="bookingistic-appearance__preview">
			<h2><?php esc_html_e( 'Live preview', 'bookingistic' ); ?></h2>
			<?php echo $sample_form; // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped in Appearance_Page. ?>
		</div>
	</div>
</div>
