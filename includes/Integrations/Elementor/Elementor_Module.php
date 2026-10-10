<?php
/**
 * Elementor integration — "Bookingistic Form" widget + category.
 *
 * Registers only when Elementor is installed and initialized. The widget wraps
 * the same shortcode/template pipeline, so every design option available to the
 * shortcode works inside Elementor with live preview controls.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Integrations\Elementor;

defined( 'ABSPATH' ) || exit;

class Elementor_Module {

	public static function register(): void {
		// Elementor may load before or after us — cover both orders.
		if ( did_action( 'elementor/loaded' ) ) {
			self::boot_widgets();
			return;
		}
		add_action( 'elementor/loaded', [ self::class, 'boot_widgets' ] );
	}

	public static function boot_widgets(): void {
		add_action( 'elementor/widgets/register', [ self::class, 'register_widgets' ] );
		add_action( 'elementor/elements/categories_registered', [ self::class, 'register_category' ] );
	}

	public static function register_category( $manager ): void {
		$manager->add_category(
			'bookingistic',
			[
				'title' => __( 'Bookingistic', 'bookingistic' ),
				'icon'  => 'eicon-calendar',
			]
		);
	}

	public static function register_widgets( $manager ): void {
		require_once __DIR__ . '/class-bookingistic-widget.php';
		if ( ! class_exists( Bookingistic_Widget::class ) ) {
			return; // Elementor base classes unavailable — degrade silently.
		}
		$manager->register( new Bookingistic_Widget() );
	}
}
