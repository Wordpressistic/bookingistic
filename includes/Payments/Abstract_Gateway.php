<?php
/**
 * Shared payment gateway plumbing.
 *
 * Gateways extend this to inherit sane defaults; only environment-specific
 * behaviour needs overriding. Gateways must never store card data — they
 * delegate to provider SDKs / hosted checkout.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Payments;

defined( 'ABSPATH' ) || exit;

abstract class Abstract_Gateway implements Payment_Gateway {

	/** @var string Stable slug — also the ledger `provider` value. */
	protected string $slug = '';

	public function slug(): string {
		return $this->slug;
	}

	public function label(): string {
		// Generic label derived from the slug; override with a proper label.
		return ucwords( str_replace( [ '_', '-' ], ' ', $this->slug ) );
	}

	public function description(): string {
		return __( 'Payment gateway for paid bookings.', 'bookingistic' );
	}

	/**
	 * Whether the gateway can run in this environment. Override for SDK /
	 * API-key checks. Unavailable gateways are excluded from the registry.
	 */
	public function is_available(): bool {
		return '' !== $this->slug;
	}

	/**
	 * Whether this gateway is switched on for paid bookings. Reads the
	 * `payment_gateways` enable map in plugin settings.
	 */
	public function is_enabled(): bool {
		return Payment_Manager::is_gateway_enabled( $this->slug() );
	}

	/**
	 * Register WP hooks. Called on every request when the gateway is
	 * available (even when not enabled — gateways guard their own hooks).
	 */
	public function boot(): void {}
}
