<?php
/**
 * Base class every Bookingistic integration extends.
 *
 * Subclasses define a stable string `slug`, a human-facing `label`, the
 * settings schema (`fields()`), and `boot()` to register hooks. The
 * Integrations_Manager toggles integrations on/off based on the
 * `bookingistic_integrations` option.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Integrations;

defined( 'ABSPATH' ) || exit;

abstract class Integration_Base {

	public const OPTION = 'bookingistic_integrations';

	abstract public function slug(): string;
	abstract public function label(): string;
	abstract public function description(): string;

	/**
	 * Settings schema for this integration.
	 *
	 * @return array<string,array{label:string,type:string,placeholder?:string,description?:string}>
	 */
	public function fields(): array {
		return [];
	}

	/**
	 * Whether this integration is available in the current environment.
	 * Override to gate on e.g. WooCommerce being installed.
	 */
	public function is_available(): bool {
		return true;
	}

	/**
	 * Wire WP hooks. Only called when the integration is enabled.
	 */
	abstract public function boot(): void;

	/* ===== Settings helpers ===== */

	public function is_enabled(): bool {
		$all = (array) get_option( self::OPTION, [] );
		return ! empty( $all[ $this->slug() ]['_enabled'] );
	}

	public function setting( string $key, $default = '' ) {
		$all = (array) get_option( self::OPTION, [] );
		return $all[ $this->slug() ][ $key ] ?? $default;
	}

	public function all_settings(): array {
		$all = (array) get_option( self::OPTION, [] );
		return $all[ $this->slug() ] ?? [];
	}

	public function update_settings( array $values ): void {
		$all = (array) get_option( self::OPTION, [] );
		$all[ $this->slug() ] = array_merge( $all[ $this->slug() ] ?? [], $values );
		update_option( self::OPTION, $all );
	}
}
