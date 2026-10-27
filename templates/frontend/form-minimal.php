<?php
/**
 * Form template: Minimal Inline — compact, borderless.
 * Same data-* contract as form-classic.php.
 *
 * @package Bookingistic
 *
 * @var array  $service
 * @var string $booking_form_id
 * @var int    $reschedule_id
 * @var string $reschedule_token
 * @var array  $assignable_staff
 * @var string $calendar_view
 */

defined( 'ABSPATH' ) || exit;

$bookingistic_is_reschedule = $reschedule_id > 0 && $reschedule_token !== '';
?>
<div id="<?php echo esc_attr( $booking_form_id ); ?>" class="bookingistic-form bookingistic-form--minimal" data-service-id="<?php echo esc_attr( $service['id'] ); ?>" data-calendar-view="<?php echo esc_attr( $calendar_view ); ?>"
	<?php if ( $bookingistic_is_reschedule ) : ?>
		data-reschedule-id="<?php echo esc_attr( $reschedule_id ); ?>"
		data-reschedule-token="<?php echo esc_attr( $reschedule_token ); ?>"
	<?php endif; ?>>

	<header class="bookingistic-min__head">
		<strong><?php echo esc_html( $service['name'] ); ?></strong>
		<span class="bookingistic-min__meta">
			<?php echo esc_html( $service['duration_minutes'] . ' ' . __( 'min', 'bookingistic' ) ); ?> ·
			<?php echo $service['price'] > 0
				? esc_html( number_format_i18n( (float) $service['price'], 2 ) )
				: esc_html__( 'Free', 'bookingistic' ); ?>
		</span>
	</header>

	<div class="bookingistic-form__steps" data-step="1">
		<?php require __DIR__ . '/partials/progress.php'; ?>
		<!-- Step 1 -->
		<section class="bookingistic-step" data-step-content="1">
			<p class="bookingistic-hint" data-bookingistic-tz></p>

			<?php if ( count( $assignable_staff ) > 1 ) : ?>
				<div class="bookingistic-staff" data-bookingistic-staff-picker>
					<button type="button" class="bookingistic-staff__option" data-staff-id="0" aria-pressed="true"><?php esc_html_e( 'Any host', 'bookingistic' ); ?></button>
					<?php foreach ( $assignable_staff as $bookingistic_staff_row ) : ?>
						<button type="button" class="bookingistic-staff__option" data-staff-id="<?php echo (int) $bookingistic_staff_row['id']; ?>" aria-pressed="false">
							<?php echo esc_html( $bookingistic_staff_row['name'] ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<p class="bookingistic-label" id="<?php echo esc_attr( $booking_form_id . '-days' ); ?>"><?php esc_html_e( 'Choose a day', 'bookingistic' ); ?></p>
			<div class="bookingistic-calendar-zone" data-bookingistic-calendar role="group" aria-labelledby="<?php echo esc_attr( $booking_form_id . '-days' ); ?>"></div>
			<p class="bookingistic-label" id="<?php echo esc_attr( $booking_form_id . '-times' ); ?>"><?php esc_html_e( 'Available times', 'bookingistic' ); ?></p>
			<div class="bookingistic-slots-zone">
				<p class="bookingistic-slots-empty"><?php esc_html_e( 'Pick a date to see available times', 'bookingistic' ); ?></p>
				<ul class="bookingistic-slot-list" data-bookingistic-slots aria-labelledby="<?php echo esc_attr( $booking_form_id . '-times' ); ?>" hidden></ul>
			</div>

			<div class="bookingistic-actions">
				<button type="button" class="bookingistic-btn bookingistic-btn--primary bookingistic-btn--cta" data-action="next" disabled>
					<?php esc_html_e( 'Continue', 'bookingistic' ); ?>
				</button>
			</div>
			<?php require __DIR__ . '/partials/status.php'; ?>
		</section>

		<!-- Step 2 -->
		<section class="bookingistic-step" data-step-content="2" hidden>
			<p class="bookingistic-summary" data-bookingistic-summary></p>

			<form class="bookingistic-fields" data-bookingistic-fields novalidate>
				<input type="text" name="website2" tabindex="-1" autocomplete="off" value="" style="position:absolute;left:-9999px" aria-hidden="true">

				<label class="bookingistic-field">
					<span><?php esc_html_e( 'Full name', 'bookingistic' ); ?> *</span>
					<input type="text" name="name" required autocomplete="name">
				</label>
				<label class="bookingistic-field">
					<span><?php esc_html_e( 'Email', 'bookingistic' ); ?> *</span>
					<input type="email" name="email" required autocomplete="email">
				</label>
				<div class="bookingistic-fields__row">
					<label class="bookingistic-field">
						<span><?php esc_html_e( 'Phone', 'bookingistic' ); ?></span>
						<input type="tel" name="phone" autocomplete="tel">
					</label>
					<label class="bookingistic-field">
						<span><?php esc_html_e( 'Notes (optional)', 'bookingistic' ); ?></span>
						<input type="text" name="notes">
					</label>
				</div>

				<div class="bookingistic-actions">
					<button type="button" class="bookingistic-btn bookingistic-btn--ghost" data-action="back"><?php esc_html_e( 'Back', 'bookingistic' ); ?></button>
					<button type="submit" class="bookingistic-btn bookingistic-btn--primary" data-action="submit">
						<?php echo $bookingistic_is_reschedule ? esc_html__( 'Confirm reschedule', 'bookingistic' ) : esc_html__( 'Confirm booking', 'bookingistic' ); ?>
					</button>
				</div>
			</form>
		</section>

		<!-- Step 3 -->
		<section class="bookingistic-step" data-step-content="3" hidden>
			<div class="bookingistic-success bookingistic-success--inline">
				<?php require __DIR__ . '/partials/success-icon.php'; ?>
				<p><strong data-bookingistic-success-title><?php esc_html_e( 'Booking confirmed', 'bookingistic' ); ?></strong><br>
				<span data-bookingistic-success-body><?php esc_html_e( 'Check your inbox for the details.', 'bookingistic' ); ?></span></p>
			</div>
			<div data-bookingistic-pay class="bookingistic-payment-box" hidden></div>
		</section>

		<div class="bookingistic-error" data-bookingistic-error role="alert" aria-live="assertive" hidden></div>
	</div>
</div>
