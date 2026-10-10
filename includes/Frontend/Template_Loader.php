<?php
/**
 * Frontend template loader — form templates, calendar views, industry presets.
 *
 * Form templates implement the same DOM contract (data-bookingistic-* hooks),
 * so a single JS controller drives every design. Themes can override any
 * template by placing a file at:
 *   {theme}/bookingistic/{template}.php
 *
 * @package Bookingistic
 */

namespace Bookingistic\Frontend;

defined( 'ABSPATH' ) || exit;

class Template_Loader {

	public const OPTION_KEY = 'bookingistic_appearance';

	/**
	 * Registered form templates: key => [label, screenshot, description].
	 */
	public static function form_templates(): array {
		return [
			'classic' => [
				'label'       => __( 'Classic', 'bookingistic' ),
				'description' => __( 'Two-panel layout with service details on the left. The original Bookingistic look.', 'bookingistic' ),
			],
			'modern'  => [
				'label'       => __( 'Modern Card', 'bookingistic' ),
				'description' => __( 'Single elevated card, floating labels, soft shadows. Great for coaches and consultants.', 'bookingistic' ),
			],
			'split'   => [
				'label'       => __( 'Split Hero', 'bookingistic' ),
				'description' => __( 'Full-width hero header with inline calendar. Ideal for salons, clinics and agencies.', 'bookingistic' ),
			],
			'minimal' => [
				'label'       => __( 'Minimal Inline', 'bookingistic' ),
				'description' => __( 'Borderless, compact, inline steps. Blends into any theme with zero chrome.', 'bookingistic' ),
			],
		];
	}

	/**
	 * Calendar view variants.
	 */
	public static function calendar_views(): array {
		return [
			'month' => __( 'Month grid', 'bookingistic' ),
			'pills' => __( 'Next 14 days (pills)', 'bookingistic' ),
			'list'  => __( 'Upcoming dates (list)', 'bookingistic' ),
		];
	}

	/**
	 * Industry presets: appearance + working-hours defaults applied together.
	 */
	public static function industries(): array {
		return [
			'consulting' => [
				'label'     => __( 'Consulting / Coaching', 'bookingistic' ),
				'accent'    => '#2563eb',
				'radius'    => '14',
				'template'  => 'modern',
				'calendar'  => 'month',
				'settings'  => [
					'slot_interval'    => 30,
					'min_notice_hours' => 12,
					'max_advance_days' => 45,
				],
			],
			'salon'      => [
				'label'     => __( 'Salon / Spa / Barber', 'bookingistic' ),
				'accent'    => '#db2777',
				'radius'    => '18',
				'template'  => 'split',
				'calendar'  => 'pills',
				'settings'  => [
					'slot_interval'    => 15,
					'min_notice_hours' => 3,
					'max_advance_days' => 60,
				],
			],
			'clinic'     => [
				'label'     => __( 'Clinic / Medical / Dental', 'bookingistic' ),
				'accent'    => '#0d9488',
				'radius'    => '12',
				'template'  => 'classic',
				'calendar'  => 'month',
				'settings'  => [
					'slot_interval'    => 20,
					'min_notice_hours' => 24,
					'max_advance_days' => 90,
				],
			],
			'fitness'    => [
				'label'     => __( 'Fitness / Yoga / Trainer', 'bookingistic' ),
				'accent'    => '#ea580c',
				'radius'    => '16',
				'template'  => 'split',
				'calendar'  => 'pills',
				'settings'  => [
					'slot_interval'    => 30,
					'min_notice_hours' => 2,
					'max_advance_days' => 30,
				],
			],
			'tutoring'   => [
				'label'     => __( 'Tutoring / Lessons', 'bookingistic' ),
				'accent'    => '#7c3aed',
				'radius'    => '12',
				'template'  => 'minimal',
				'calendar'  => 'list',
				'settings'  => [
					'slot_interval'    => 30,
					'min_notice_hours' => 6,
					'max_advance_days' => 30,
				],
			],
			'services'   => [
				'label'     => __( 'Home / Professional Services', 'bookingistic' ),
				'accent'    => '#16a34a',
				'radius'    => '10',
				'template'  => 'minimal',
				'calendar'  => 'list',
				'settings'  => [
					'slot_interval'    => 60,
					'min_notice_hours' => 24,
					'max_advance_days' => 30,
				],
			],
		];
	}

	/**
	 * Appearance settings (with defaults).
	 *
	 * @return array{template:string,calendar:string,accent:string,radius:string,font:string}
	 */
	public static function appearance(): array {
		$defaults = [
			'template' => 'classic',
			'calendar' => 'month',
			'accent'   => '#2563eb',
			'radius'   => '14',
			'font'     => 'inherit',
		];
		$saved = (array) get_option( self::OPTION_KEY, [] );
		return array_merge( $defaults, $saved );
	}

	public static function update_appearance( array $values ): void {
		$valid_template = array_key_exists( $values['template'] ?? '', self::form_templates() );
		$valid_calendar = array_key_exists( $values['calendar'] ?? '', self::calendar_views() );

		$current = self::appearance();

		if ( $valid_template ) {
			$current['template'] = sanitize_key( $values['template'] );
		}
		if ( $valid_calendar ) {
			$current['calendar'] = sanitize_key( $values['calendar'] );
		}
		if ( isset( $values['accent'] ) ) {
			$accent = sanitize_hex_color( $values['accent'] );
			if ( $accent ) {
				$current['accent'] = $accent;
			}
		}
		if ( isset( $values['radius'] ) ) {
			$current['radius'] = (string) max( 0, min( 32, (int) $values['radius'] ) );
		}
		if ( isset( $values['font'] ) ) {
			$current['font'] = sanitize_text_field( $values['font'] );
		}

		update_option( self::OPTION_KEY, $current );
	}

	/**
	 * Resolve which template file renders a form.
	 *
	 * @return string Absolute path.
	 */
	public static function form_template_path( string $template ): string {
		$known = array_key_exists( $template, self::form_templates() ) ? $template : 'classic';

		$theme = locate_template( 'bookingistic/form-' . $known . '.php' );
		if ( $theme ) {
			return $theme;
		}
		return BOOKINGISTIC_DIR . 'templates/frontend/form-' . $known . '.php';
	}

	/**
	 * Full list of theme-overridable template files.
	 *
	 * @return string[] Relative paths.
	 */
	public static function overridable_templates(): array {
		return [ 'bookingistic/form-classic.php', 'bookingistic/form-modern.php', 'bookingistic/form-split.php', 'bookingistic/form-minimal.php' ];
	}
}
