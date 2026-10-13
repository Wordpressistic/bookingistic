<?php
/**
 * Frontend booking form template — Classic (default).
 *
 * DOM contract consumed by assets/frontend/js/booking-form.js:
 *   .bookingistic-form[data-service-id][data-calendar-view]
 *   [data-calendar-view]       month | pills | list
 *   [data-bookingistic-tz]     timezone hint line
 *   [data-bookingistic-staff-picker] .bookingistic-staff__option[data-staff-id]
 *   [data-bookingistic-calendar]       calendar mounts here
 *   [data-bookingistic-slots]          slot <ul>
 *   .bookingistic-slots-empty          empty/loading line
 *   [data-bookingistic-summary]        step-2 summary line
 *   [data-bookingistic-fields]         the <form>
 *   [data-bookingistic-error]          error box
 *   [data-bookingistic-success-title] / [data-bookingistic-success-body]
 *   .bookingistic-form__steps[data-step]  + [data-step-content="1|2|3"]
 *
 * Override per design by copying to {theme}/bookingistic/form-classic.php.
 *
 * @package Bookingistic
 *
 * @var array  $service           Service row.
 * @var string $booking_form_id   Unique DOM id.
 * @var int    $reschedule_id     Booking id in reschedule mode (else 0).
 * @var string $reschedule_token  Matching reschedule token.
 * @var array  $assignable_staff  Staff rows for this service.
 * @var string $calendar_view     Calendar variant key (month|pills|list).
 */

defined( 'ABSPATH' ) || exit;

$is_reschedule = $reschedule_id > 0 && $reschedule_token !== '';
?>
<div id="<?php echo esc_attr( $booking_form_id ); ?>" class="bookingistic-form bookingistic-form--classic" data-service-id="<?php echo esc_attr( $service['id'] ); ?>" data-calendar-view="<?php echo esc_attr( $calendar_view ); ?>"
	<?php if ( $is_reschedule ) : ?>
		data-reschedule-id="<?php echo esc_attr( $reschedule_id ); ?>"
		data-reschedule-token="<?php echo esc_attr( $reschedule_token ); ?>"
	<?php endif; ?>>

	<div class="bookingistic-form__panel bookingistic-form__panel--service">
		<div class="bookingistic-form__service-meta">
			<span class="bookingistic-form__service-duration"><?php echo esc_html( $service['duration_minutes'] . ' ' . __( 'min', 'bookingistic' ) ); ?></span>
			<span class="bookingistic-form__service-price">
				<?php echo $service['price'] > 0
					? esc_html( number_format_i18n( (float) $service['price'], 2 ) )
					: esc_html__( 'Free', 'bookingistic' ); ?>
			</span>
		</div>
		<h2 class="bookingistic-form__service-name"><?php echo esc_html( $service['name'] ); ?></h2>
		<?php if ( ! empty( $service['short_description'] ) ) : ?>
			<p class="bookingistic-form__service-tagline"><?php echo esc_html( $service['short_description'] ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $service['description'] ) ) : ?>
			<div class="bookingistic-form__service-description"><?php echo wp_kses_post( wpautop( $service['description'] ) ); ?></div>
		<?php endif; ?>
		<ul class="bookingistic-form__trust">
			<li><?php esc_html_e( 'Instant confirmation email', 'bookingistic' ); ?></li>
			<li><?php esc_html_e( 'Reschedule or cancel anytime', 'bookingistic' ); ?></li>
			<li><?php esc_html_e( 'No spam — just your booking', 'bookingistic' ); ?></li>
		</ul>
	</div>

	<div class="bookingistic-form__panel bookingistic-form__panel--booking">
		<div class="bookingistic-form__steps" data-step="1">

			<!-- Step 1: date + time -->
			<section class="bookingistic-step" data-step-content="1">
				<header class="bookingistic-form__step-header">
					<h3><?php esc_html_e( 'Choose a date & time', 'bookingistic' ); ?></h3>
					<p class="bookingistic-hint" data-bookingistic-tz></p>
				</header>

				<?php if ( count( $assignable_staff ) > 1 ) : ?>
					<div class="bookingistic-staff" data-bookingistic-staff-picker>
						<span class="bookingistic-staff__label"><?php esc_html_e( 'Host', 'bookingistic' ); ?></span>
						<button type="button" class="bookingistic-staff__option" data-staff-id="0" aria-pressed="true"><?php esc_html_e( 'Any', 'bookingistic' ); ?></button>
						<?php foreach ( $assignable_staff as $staff_row ) : ?>
							<button type="button" class="bookingistic-staff__option" data-staff-id="<?php echo (int) $staff_row['id']; ?>" aria-pressed="false">
								<?php echo esc_html( $staff_row['name'] ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="bookingistic-calendar-zone" data-bookingistic-calendar></div>
				<div class="bookingistic-slots-zone">
					<p class="bookingistic-slots-empty"><?php esc_html_e( 'Pick a date to see available times', 'bookingistic' ); ?></p>
					<ul class="bookingistic-slot-list" data-bookingistic-slots hidden></ul>
				</div>

				<div class="bookingistic-actions">
					<button type="button" class="bookingistic-btn bookingistic-btn--primary" data-action="next" disabled>
						<?php esc_html_e( 'Continue', 'bookingistic' ); ?>
					</button>
				</div>
			</section>

			<!-- Step 2: customer details -->
			<section class="bookingistic-step" data-step-content="2" hidden>
				<header class="bookingistic-form__step-header">
					<h3><?php esc_html_e( 'Your details', 'bookingistic' ); ?></h3>
					<p class="bookingistic-summary" data-bookingistic-summary></p>
				</header>
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
							<span><?php esc_html_e( 'Company / Website', 'bookingistic' ); ?></span>
							<input type="text" name="company" autocomplete="organization">
						</label>
					</div>
					<label class="bookingistic-field">
						<span><?php esc_html_e( 'Notes (optional)', 'bookingistic' ); ?></span>
						<textarea name="notes" rows="3"></textarea>
					</label>

					<div class="bookingistic-actions">
						<button type="button" class="bookingistic-btn bookingistic-btn--ghost" data-action="back">
							<?php esc_html_e( 'Back', 'bookingistic' ); ?>
						</button>
						<button type="submit" class="bookingistic-btn bookingistic-btn--primary" data-action="submit">
							<?php echo $is_reschedule
								? esc_html__( 'Confirm reschedule', 'bookingistic' )
								: esc_html__( 'Confirm booking', 'bookingistic' ); ?>
						</button>
					</div>
				</form>
			</section>

			<!-- Step 3: confirmation -->
			<section class="bookingistic-step" data-step-content="3" hidden>
				<div class="bookingistic-success">
					<span class="bookingistic-success__icon" aria-hidden="true">✓</span>
					<h3 data-bookingistic-success-title><?php esc_html_e( 'Booking confirmed', 'bookingistic' ); ?></h3>
					<p data-bookingistic-success-body><?php esc_html_e( 'Check your inbox for the details.', 'bookingistic' ); ?></p>
					<div data-bookingistic-pay class="bookingistic-payment-box" hidden></div>
				</div>
			</section>

			<div class="bookingistic-error" data-bookingistic-error role="alert" hidden></div>
		</div>
	</div>
</div>
