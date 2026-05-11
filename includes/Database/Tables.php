<?php
/**
 * Custom database tables.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Database;

defined( 'ABSPATH' ) || exit;

class Tables {

	public static function services_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'bookingistic_services';
	}

	public static function staff_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'bookingistic_staff';
	}

	public static function staff_services_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'bookingistic_staff_services';
	}

	public static function availability_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'bookingistic_availability';
	}

	public static function bookings_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'bookingistic_bookings';
	}

	public static function customers_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'bookingistic_customers';
	}

	public static function booking_meta_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'bookingistic_booking_meta';
	}

	public static function email_automations_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'bookingistic_email_automations';
	}

	public static function email_logs_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'bookingistic_email_logs';
	}

	public static function calendar_events_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'bookingistic_calendar_events';
	}

	public static function payment_records_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'bookingistic_payment_records';
	}

	public static function create_all(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();

		$schemas = [
			"CREATE TABLE " . self::services_table() . " (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(191) NOT NULL,
				slug VARCHAR(191) NOT NULL,
				description LONGTEXT NULL,
				short_description TEXT NULL,
				duration_minutes INT UNSIGNED NOT NULL DEFAULT 30,
				buffer_before INT UNSIGNED NOT NULL DEFAULT 0,
				buffer_after INT UNSIGNED NOT NULL DEFAULT 0,
				price DECIMAL(10,2) NOT NULL DEFAULT 0,
				category VARCHAR(100) NULL,
				booking_type VARCHAR(50) NOT NULL DEFAULT 'one_to_one',
				confirmation_mode VARCHAR(20) NOT NULL DEFAULT 'auto',
				max_attendees INT UNSIGNED NOT NULL DEFAULT 1,
				status VARCHAR(20) NOT NULL DEFAULT 'active',
				sort_order INT NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY slug (slug),
				KEY status (status),
				KEY category (category)
			) {$charset};",

			"CREATE TABLE " . self::staff_table() . " (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT UNSIGNED NULL,
				name VARCHAR(191) NOT NULL,
				email VARCHAR(191) NOT NULL,
				phone VARCHAR(50) NULL,
				photo_url VARCHAR(500) NULL,
				bio TEXT NULL,
				working_hours LONGTEXT NULL,
				days_off LONGTEXT NULL,
				special_availability LONGTEXT NULL,
				booking_limit_per_day INT UNSIGNED NOT NULL DEFAULT 0,
				status VARCHAR(20) NOT NULL DEFAULT 'active',
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY email (email),
				KEY status (status)
			) {$charset};",

			"CREATE TABLE " . self::staff_services_table() . " (
				staff_id BIGINT UNSIGNED NOT NULL,
				service_id BIGINT UNSIGNED NOT NULL,
				PRIMARY KEY  (staff_id, service_id),
				KEY service_id (service_id)
			) {$charset};",

			"CREATE TABLE " . self::availability_table() . " (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				staff_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				kind VARCHAR(20) NOT NULL DEFAULT 'weekly',
				day_of_week TINYINT UNSIGNED NULL,
				date_from DATE NULL,
				date_to DATE NULL,
				time_start TIME NULL,
				time_end TIME NULL,
				is_available TINYINT(1) NOT NULL DEFAULT 1,
				PRIMARY KEY  (id),
				KEY staff_id (staff_id),
				KEY kind (kind)
			) {$charset};",

			"CREATE TABLE " . self::customers_table() . " (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				first_name VARCHAR(100) NULL,
				last_name VARCHAR(100) NULL,
				full_name VARCHAR(200) NULL,
				email VARCHAR(191) NOT NULL,
				phone VARCHAR(50) NULL,
				timezone VARCHAR(100) NULL,
				company VARCHAR(191) NULL,
				website VARCHAR(500) NULL,
				notes LONGTEXT NULL,
				tags VARCHAR(500) NULL,
				source VARCHAR(100) NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY email (email),
				KEY phone (phone)
			) {$charset};",

			"CREATE TABLE " . self::bookings_table() . " (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				service_id BIGINT UNSIGNED NOT NULL,
				staff_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				customer_id BIGINT UNSIGNED NOT NULL,
				start_datetime DATETIME NULL,
				end_datetime DATETIME NULL,
				timezone VARCHAR(100) NULL,
				status VARCHAR(30) NOT NULL DEFAULT 'pending',
				payment_status VARCHAR(30) NOT NULL DEFAULT 'free',
				payment_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
				source VARCHAR(50) NULL,
				notes LONGTEXT NULL,
				internal_notes LONGTEXT NULL,
				cancel_token VARCHAR(64) NULL,
				reschedule_token VARCHAR(64) NULL,
				reminder_sent_at DATETIME NULL,
				legacy_post_id BIGINT UNSIGNED NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY service_id (service_id),
				KEY staff_id (staff_id),
				KEY customer_id (customer_id),
				KEY start_datetime (start_datetime),
				KEY status (status),
				KEY payment_status (payment_status)
			) {$charset};",

			"CREATE TABLE " . self::booking_meta_table() . " (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				booking_id BIGINT UNSIGNED NOT NULL,
				meta_key VARCHAR(191) NOT NULL,
				meta_value LONGTEXT NULL,
				PRIMARY KEY  (id),
				KEY booking_id (booking_id),
				KEY meta_key (meta_key)
			) {$charset};",

			"CREATE TABLE " . self::email_automations_table() . " (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(191) NOT NULL,
				trigger_event VARCHAR(100) NOT NULL,
				recipient_type VARCHAR(50) NOT NULL DEFAULT 'customer',
				recipient_custom VARCHAR(191) NULL,
				delay_amount INT NOT NULL DEFAULT 0,
				delay_unit VARCHAR(20) NOT NULL DEFAULT 'minutes',
				subject VARCHAR(255) NOT NULL,
				body LONGTEXT NOT NULL,
				service_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				status VARCHAR(20) NOT NULL DEFAULT 'active',
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY trigger_event (trigger_event),
				KEY status (status),
				KEY service_id (service_id)
			) {$charset};",

			"CREATE TABLE " . self::email_logs_table() . " (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				automation_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				booking_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				customer_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				recipient_email VARCHAR(191) NOT NULL,
				subject VARCHAR(255) NOT NULL,
				status VARCHAR(30) NOT NULL DEFAULT 'sent',
				error_message TEXT NULL,
				sent_at DATETIME NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY booking_id (booking_id),
				KEY automation_id (automation_id),
				KEY status (status)
			) {$charset};",

			"CREATE TABLE " . self::calendar_events_table() . " (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				booking_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				provider VARCHAR(50) NOT NULL,
				external_event_id VARCHAR(255) NULL,
				status VARCHAR(30) NOT NULL DEFAULT 'pending',
				payload LONGTEXT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY booking_id (booking_id),
				KEY provider (provider)
			) {$charset};",

			"CREATE TABLE " . self::payment_records_table() . " (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				booking_id BIGINT UNSIGNED NOT NULL,
				provider VARCHAR(50) NOT NULL,
				external_id VARCHAR(255) NULL,
				amount DECIMAL(10,2) NOT NULL DEFAULT 0,
				currency VARCHAR(10) NOT NULL DEFAULT 'USD',
				status VARCHAR(30) NOT NULL DEFAULT 'pending',
				payload LONGTEXT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY booking_id (booking_id),
				KEY provider (provider),
				KEY status (status)
			) {$charset};",
		];

		foreach ( $schemas as $sql ) {
			dbDelta( $sql );
		}
	}

	public static function drop_all(): void {
		global $wpdb;
		$tables = [
			self::payment_records_table(),
			self::calendar_events_table(),
			self::email_logs_table(),
			self::email_automations_table(),
			self::booking_meta_table(),
			self::bookings_table(),
			self::customers_table(),
			self::availability_table(),
			self::staff_services_table(),
			self::staff_table(),
			self::services_table(),
		];
		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
	}
}
