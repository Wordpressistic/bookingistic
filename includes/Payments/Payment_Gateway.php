<?php
/**
 * Payment gateway contract.
 *
 * A gateway connects a booking to a payment flow and reports transitions
 * back through Payment_Manager::record_event(). Gateways must never store
 * card data — they delegate to provider SDKs / hosted checkout.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Payments;

defined( 'ABSPATH' ) || exit;

interface Payment_Gateway {

	/** Stable slug (also the ledger `provider` value). */
	public function slug(): string;

	/** Human label for admin UI. */
	public function label(): string;

	/** One-line admin description. */
	public function description(): string;

	/** Whether the gateway can run in this environment (SDK present, keys set…). */
	public function is_available(): bool;

	/**
	 * Whether this gateway is switched on for paid bookings.
	 * Default reads the `payment_gateways` map in plugin settings.
	 */
	public function is_enabled(): bool;

	/**
	 * Register WP hooks. Called on every request when the gateway is
	 * available (even if not enabled — gateways guard their own hooks).
	 */
	public function boot(): void;
}
