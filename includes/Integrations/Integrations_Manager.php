<?php
/**
 * Discovers and boots integrations.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Integrations;

use Bookingistic\Integrations\CRMistic\CRMistic_Integration;
use Bookingistic\Integrations\Google_Calendar\Google_Calendar_Integration;
use Bookingistic\Integrations\Messageistic\Messageistic_Integration;
use Bookingistic\Integrations\Outlook\Outlook_Integration;
use Bookingistic\Integrations\WooCommerce\WooCommerce_Integration;

defined( 'ABSPATH' ) || exit;

class Integrations_Manager {

	/** @var Integration_Base[] */
	private static array $registry = [];

	public static function register(): void {
		add_action( 'plugins_loaded', [ self::class, 'discover_and_boot' ], 20 );
	}

	public static function discover_and_boot(): void {
		self::$registry = self::default_integrations();

		/**
		 * Filter the registry of integrations.
		 *
		 * @param Integration_Base[] $registry
		 */
		self::$registry = apply_filters( 'bookingistic_integrations', self::$registry );

		foreach ( self::$registry as $integration ) {
			if ( $integration->is_available() && $integration->is_enabled() ) {
				$integration->boot();
			}
		}
	}

	/** @return Integration_Base[] */
	public static function all(): array {
		if ( empty( self::$registry ) ) {
			self::$registry = apply_filters( 'bookingistic_integrations', self::default_integrations() );
		}
		return self::$registry;
	}

	public static function find( string $slug ): ?Integration_Base {
		foreach ( self::all() as $integration ) {
			if ( $integration->slug() === $slug ) {
				return $integration;
			}
		}
		return null;
	}

	/** @return Integration_Base[] */
	private static function default_integrations(): array {
		return [
			new WooCommerce_Integration(),
			new Google_Calendar_Integration(),
			new Outlook_Integration(),
			new Messageistic_Integration(),
			new CRMistic_Integration(),
		];
	}
}
