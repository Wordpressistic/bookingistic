<?php
/**
 * Admin Appearance page — form template picker, calendar view, brand accent,
 * industry one-click presets with live preview.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Core\Capabilities;
use Bookingistic\Frontend\Template_Loader;

defined( 'ABSPATH' ) || exit;

class Appearance_Page {

	public const PAGE_SLUG = 'bookingistic-appearance';
	public const NONCE     = 'bookingistic_admin_appearance';

	public static function register(): void {
		add_action( 'admin_menu', [ self::class, 'add_page' ] );
		add_action( 'admin_init', [ self::class, 'maybe_save' ] );
	}

	public static function add_page(): void {
		add_submenu_page(
			Admin_Menu::SLUG,
			__( 'Appearance', 'bookingistic' ),
			__( 'Appearance', 'bookingistic' ),
			Capabilities::MANAGE,
			self::PAGE_SLUG,
			[ self::class, 'render' ]
		);
	}

	public static function render(): void {
		$appearance  = Template_Loader::appearance();
		$templates   = Template_Loader::form_templates();
		$calendars   = Template_Loader::calendar_views();
		$industries  = Template_Loader::industries();
		$sample_form = self::sample_form_html( $appearance );
		include BOOKINGISTIC_DIR . 'templates/admin/appearance.php';
	}

	public static function maybe_save(): void {
		if ( ! is_admin() || empty( $_POST['bookingistic_admin_action'] ) ) {
			return;
		}
		$action = sanitize_key( wp_unslash( $_POST['bookingistic_admin_action'] ) );

		if ( $action === 'save_appearance' ) {
			Capabilities::verify( Capabilities::MANAGE );
			check_admin_referer( self::NONCE );
			Template_Loader::update_appearance(
				[
					'template' => sanitize_key( wp_unslash( $_POST['template'] ?? '' ) ),
					'calendar' => sanitize_key( wp_unslash( $_POST['calendar'] ?? '' ) ),
					'accent'   => sanitize_hex_color( wp_unslash( $_POST['accent'] ?? '' ) ) ?: '#2563eb',
					'radius'   => (string) (int) ( $_POST['radius'] ?? 14 ),
				]
			);
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&saved=1' ) );
			exit;
		}

		if ( $action === 'apply_industry' ) {
			Capabilities::verify( Capabilities::MANAGE );
			check_admin_referer( self::NONCE );
			$key       = sanitize_key( wp_unslash( $_POST['industry'] ?? '' ) );
			$customize = Template_Loader::industries()[ $key ] ?? null;
			if ( $customize ) {
				Template_Loader::update_appearance(
					[
						'template' => $customize['template'],
						'calendar' => $customize['calendar'],
						'accent'   => $customize['accent'],
						'radius'   => $customize['radius'],
					]
				);

				// Merge industry scheduling defaults into Settings (only tighten user-visible knobs).
				$settings = get_option( 'bookingistic_settings', [] );
				foreach ( $customize['settings'] as $k => $v ) {
					$settings[ $k ] = $v;
				}
				update_option( 'bookingistic_settings', $settings );
			}
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&industry=' . rawurlencode( $key ) ) );
			exit;
		}
	}

	/**
	 * Sample preview markup (static demo — not wired to REST).
	 */
	private static function sample_form_html( array $appearance ): string {
		$accent = esc_attr( $appearance['accent'] );
		$radius = (int) $appearance['radius'];

		$cal = '';
		$dow = [ 'M', 'T', 'W', 'T', 'F', 'S', 'S' ];
		if ( $appearance['calendar'] === 'pills' ) {
			$cal .= '<div class="bk-pills" aria-hidden="true">';
			foreach ( [ 'Mon', 'Tue', 'Wed', 'Thu', 'Fri' ] as $i => $d ) {
				$sel  = $i === 2 ? ' is-selected' : '';
				$cal .= '<span class="bk-pill' . $sel . '"><span class="bk-pill__wd">' . esc_html( $d ) . '</span><span class="bk-pill__day">' . ( 13 + $i ) . '</span></span>';
			}
			$cal .= '</div>';
		} elseif ( $appearance['calendar'] === 'list' ) {
			$cal .= '<div class="bk-list" aria-hidden="true">';
			foreach ( [ 'Today', 'Tomorrow', 'Friday, Oct 16' ] as $d ) {
				$cal .= '<span class="bk-list__row"><span>' . esc_html( $d ) . '</span></span>';
			}
			$cal .= '</div>';
		} else {
			$cal .= '<div class="bk-cal__head" aria-hidden="true"><span class="bk-cal__nav">‹</span><span class="bk-cal__title">October 2026</span><span class="bk-cal__nav">›</span></div>';
			$cal .= '<div class="bk-cal__grid" aria-hidden="true">';
			foreach ( $dow as $d ) {
				$cal .= '<div class="bk-cal__dow">' . esc_html( $d ) . '</div>';
			}
			for ( $i = 1; $i <= 28; $i++ ) {
				$sel  = $i === 15 ? ' is-selected' : '';
				$dis  = $i < 13 ? ' disabled' : '';
				$cal .= '<span class="bk-cal__day' . $sel . $dis . '">' . (int) $i . '</span>';
			}
			$cal .= '</div>';
		}

		$slots = '';
		foreach ( [ '9:00 AM', '10:30 AM', '1:00 PM', '3:30 PM' ] as $i => $t ) {
			$sel    = $i === 1 ? ' is-selected' : '';
			$slots .= '<li><span class="bookingistic-slot' . $sel . '">' . esc_html( $t ) . '</span></li>';
		}

		return '<div class="bookingistic-form bookingistic-form--' . esc_attr( $appearance['template'] ) . '" style="--bk-accent:' . $accent . ';--bk-radius:' . $radius . 'px">'
			. '<div class="bookingistic-card" style="box-shadow:none;max-width:none">'
			. '<div class="bookingistic-card__head"><span class="bookingistic-chip">30 min</span><span class="bookingistic-chip">Free</span></div>'
			. '<h2 class="bookingistic-card__title">' . esc_html__( 'Sample Service', 'bookingistic' ) . '</h2>'
			. '<div class="bookingistic-calendar-zone">' . $cal . '</div>'
			. '<div class="bookingistic-slots-zone"><ul class="bookingistic-slot-list" style="display:grid">' . $slots . '</ul></div>'
			. '<div class="bookingistic-actions"><span class="bookingistic-btn bookingistic-btn--primary">' . esc_html__( 'Continue', 'bookingistic' ) . '</span></div>'
			. '</div></div>';
	}
}
