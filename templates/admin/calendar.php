<?php
/**
 * Admin calendar template.
 *
 * @var array $services
 * @var array $staff
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap bookingistic-wrap bookingistic-calendar-wrap">
	<header class="bookingistic-calendar__header">
		<div>
			<h1 class="bookingistic-calendar__title" data-bookingistic-calendar-title><?php esc_html_e( 'Calendar', 'bookingistic' ); ?></h1>
		</div>
		<div class="bookingistic-calendar__nav">
			<button type="button" class="button" data-bookingistic-calendar-prev>‹</button>
			<button type="button" class="button" data-bookingistic-calendar-today><?php esc_html_e( 'Today', 'bookingistic' ); ?></button>
			<button type="button" class="button" data-bookingistic-calendar-next>›</button>
		</div>
		<div class="bookingistic-calendar__views">
			<button type="button" class="button button-primary" data-bookingistic-calendar-view="month"><?php esc_html_e( 'Month', 'bookingistic' ); ?></button>
			<button type="button" class="button" data-bookingistic-calendar-view="week"><?php esc_html_e( 'Week', 'bookingistic' ); ?></button>
			<button type="button" class="button" data-bookingistic-calendar-view="day"><?php esc_html_e( 'Day', 'bookingistic' ); ?></button>
			<button type="button" class="button" data-bookingistic-calendar-view="list"><?php esc_html_e( 'List', 'bookingistic' ); ?></button>
		</div>
	</header>

	<div class="bookingistic-calendar__filters">
		<label>
			<span><?php esc_html_e( 'Service', 'bookingistic' ); ?></span>
			<select data-bookingistic-calendar-filter="service_id">
				<option value=""><?php esc_html_e( 'All', 'bookingistic' ); ?></option>
				<?php foreach ( $services as $bookingistic_service_option ) : ?>
					<option value="<?php echo (int) $bookingistic_service_option['id']; ?>"><?php echo esc_html( $bookingistic_service_option['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<span><?php esc_html_e( 'Staff', 'bookingistic' ); ?></span>
			<select data-bookingistic-calendar-filter="staff_id">
				<option value=""><?php esc_html_e( 'All', 'bookingistic' ); ?></option>
				<?php foreach ( $staff as $bookingistic_staff_option ) : ?>
					<option value="<?php echo (int) $bookingistic_staff_option['id']; ?>"><?php echo esc_html( $bookingistic_staff_option['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<span><?php esc_html_e( 'Status', 'bookingistic' ); ?></span>
			<select data-bookingistic-calendar-filter="status">
				<option value=""><?php esc_html_e( 'All', 'bookingistic' ); ?></option>
				<?php foreach ( [ 'pending', 'confirmed', 'rescheduled', 'completed', 'cancelled', 'no_show' ] as $bookingistic_calendar_status ) : ?>
					<option value="<?php echo esc_attr( $bookingistic_calendar_status ); ?>"><?php echo esc_html( $bookingistic_calendar_status ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<span><?php esc_html_e( 'Payment', 'bookingistic' ); ?></span>
			<select data-bookingistic-calendar-filter="payment_status">
				<option value=""><?php esc_html_e( 'All', 'bookingistic' ); ?></option>
				<?php foreach ( [ 'free', 'pending', 'paid', 'deposit_paid', 'failed', 'refunded', 'pay_later' ] as $bookingistic_calendar_pay_status ) : ?>
					<option value="<?php echo esc_attr( $bookingistic_calendar_pay_status ); ?>"><?php echo esc_html( $bookingistic_calendar_pay_status ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<div class="bookingistic-calendar__filters-spacer"></div>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=bookingistic-bookings&new=1' ) ); ?>"><?php esc_html_e( 'New booking', 'bookingistic' ); ?></a>
	</div>

	<div class="bookingistic-calendar" data-bookingistic-calendar></div>

	<div class="bookingistic-modal" data-bookingistic-modal hidden role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Booking details', 'bookingistic' ); ?>">
		<div class="bookingistic-modal__overlay" data-bookingistic-modal-close></div>
		<div class="bookingistic-modal__panel">
			<button type="button" class="bookingistic-modal__close" data-bookingistic-modal-close aria-label="<?php esc_attr_e( 'Close', 'bookingistic' ); ?>">×</button>
			<div data-bookingistic-modal-body></div>
		</div>
	</div>
</div>
