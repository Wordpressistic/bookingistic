<?php
/**
 * Database installer / upgrader.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Core;

use Bookingistic\Database\Tables;

defined( 'ABSPATH' ) || exit;

class Installer {

	const DB_VERSION_OPTION = 'bookingistic_db_version';
	const DB_VERSION        = '2.0.0';

	public static function install(): void {
		Tables::create_all();
		self::seed_default_service();
		self::seed_default_settings();
		self::seed_default_email_automations();
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );

		if ( ! wp_next_scheduled( 'bookingistic_send_reminders' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'bookingistic_send_reminders' );
		}
	}

	public static function maybe_upgrade(): void {
		$current = get_option( self::DB_VERSION_OPTION );
		if ( $current === self::DB_VERSION ) {
			return;
		}
		self::install();
	}

	private static function seed_default_service(): void {
		global $wpdb;
		$table = Tables::services_table();
		$exists = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $exists > 0 ) {
			return;
		}
		$now = current_time( 'mysql', true );
		$wpdb->insert(
			$table,
			[
				'name'              => 'Strategy Call',
				'slug'              => 'strategy-call',
				'description'       => 'A free 30-minute WordPressistic strategy call to map your automation goals.',
				'short_description' => 'Free 30-minute consultation.',
				'duration_minutes'  => 30,
				'buffer_before'     => 0,
				'buffer_after'      => 10,
				'price'             => 0,
				'category'          => 'consultation',
				'booking_type'      => 'one_to_one',
				'confirmation_mode' => 'auto',
				'max_attendees'     => 1,
				'status'            => 'active',
				'sort_order'        => 0,
				'created_at'        => $now,
				'updated_at'        => $now,
			],
			[ '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s' ]
		);
	}

	private static function seed_default_settings(): void {
		if ( false === get_option( 'bookingistic_settings' ) ) {
			add_option(
				'bookingistic_settings',
				[
					'business_name'     => get_bloginfo( 'name' ),
					'business_email'    => get_option( 'admin_email' ),
					'business_phone'    => '',
					'business_address'  => '',
					'from_name'         => get_bloginfo( 'name' ),
					'from_email'        => get_option( 'admin_email' ),
					'reply_to'          => get_option( 'admin_email' ),
					'admin_notify'      => get_option( 'admin_email' ),
					'staff_notify'      => '',
					'logo_url'          => '',
					'footer_text'       => '',
					'min_notice_hours'  => 4,
					'max_advance_days'  => 60,
					'slot_interval'     => 15,
					'cancel_window_hrs' => 12,
					'reschedule_window_hrs' => 24,
					'working_days'      => [ 1, 2, 3, 4, 5 ],
					'working_start'     => '09:00',
					'working_end'       => '17:00',
					'timezone'          => wp_timezone_string(),
				]
			);
		}
	}

	private static function seed_default_email_automations(): void {
		global $wpdb;
		$table = Tables::email_automations_table();
		$exists = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $exists > 0 ) {
			return;
		}

		$now      = current_time( 'mysql', true );
		$defaults = [
			[
				'name'           => 'Customer confirmation',
				'trigger_event'  => 'booking_confirmed',
				'recipient_type' => 'customer',
				'delay_amount'   => 0,
				'delay_unit'     => 'minutes',
				'subject'        => 'Your booking is confirmed — {service_name}',
				'body'           => "<p>Hi {customer_first_name},</p>\n<p>Your booking is confirmed.</p>\n<p><strong>{service_name}</strong><br>{booking_date} at {booking_time} ({booking_timezone})</p>\n<p>Need to make changes? <a href=\"{reschedule_link}\">Reschedule</a> or <a href=\"{cancel_link}\">cancel</a>.</p>\n<p>— {business_name}</p>",
			],
			[
				'name'           => 'Admin notification',
				'trigger_event'  => 'booking_submitted',
				'recipient_type' => 'admin',
				'delay_amount'   => 0,
				'delay_unit'     => 'minutes',
				'subject'        => 'New booking — {service_name} · {customer_name}',
				'body'           => "<p>New booking received.</p>\n<ul><li><strong>Service:</strong> {service_name}</li><li><strong>Customer:</strong> {customer_name} ({customer_email})</li><li><strong>When:</strong> {booking_date} {booking_time} {booking_timezone}</li></ul>",
			],
			[
				'name'           => 'Customer reminder (24h)',
				'trigger_event'  => 'booking_reminder',
				'recipient_type' => 'customer',
				'delay_amount'   => 24,
				'delay_unit'     => 'hours',
				'subject'        => 'Reminder: {service_name} tomorrow',
				'body'           => "<p>Hi {customer_first_name},</p>\n<p>This is a reminder for your upcoming <strong>{service_name}</strong> on {booking_date} at {booking_time} ({booking_timezone}).</p>\n<p>— {business_name}</p>",
			],
			[
				'name'           => 'Customer cancellation',
				'trigger_event'  => 'booking_cancelled',
				'recipient_type' => 'customer',
				'delay_amount'   => 0,
				'delay_unit'     => 'minutes',
				'subject'        => 'Your booking has been cancelled',
				'body'           => "<p>Hi {customer_first_name},</p>\n<p>Your booking for {service_name} on {booking_date} at {booking_time} has been cancelled.</p>\n<p>— {business_name}</p>",
			],
			[
				'name'           => 'Customer reschedule',
				'trigger_event'  => 'booking_rescheduled',
				'recipient_type' => 'customer',
				'delay_amount'   => 0,
				'delay_unit'     => 'minutes',
				'subject'        => 'Your booking has been rescheduled',
				'body'           => "<p>Hi {customer_first_name},</p>\n<p>Your booking for {service_name} has been moved to {booking_date} at {booking_time} ({booking_timezone}).</p>\n<p>— {business_name}</p>",
			],
		];

		foreach ( $defaults as $row ) {
			$wpdb->insert(
				$table,
				array_merge(
					$row,
					[
						'service_id' => 0,
						'status'     => 'active',
						'created_at' => $now,
						'updated_at' => $now,
					]
				)
			);
		}
	}
}
