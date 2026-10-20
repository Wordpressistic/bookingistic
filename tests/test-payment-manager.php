<?php
/**
 * Payment_Manager::amount_due() deposit math with stubbed repositories.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Database {
	class Service_Repository {
		public static function find( int $id ): ?array {
			return $GLOBALS['__stub_services'][ $id ] ?? null;
		}
	}
	class Payment_Repository {
		public static function latest_for_booking( int $id, string $provider = '' ): ?array {
			return null;
		}
		public static function find( int $id ): ?array {
			return null;
		}
		public static function create( array $row ): int {
			return 1;
		}
		public static function mark( int $id, string $status, string $note = '' ): void {
		}
	}
	class Booking_Repository {
		public static function find( int $id ): ?array {
			return null;
		}
		public static function update( int $id, array $fields ): void {
		}
	}
}

namespace {
	require_once __DIR__ . '/../includes/Payments/Payment_Gateway.php';
	require_once __DIR__ . '/../includes/Payments/Payment_Manager.php';

	use Bookingistic\Payments\Payment_Manager;

	$GLOBALS['__stub_services'] = [
		5 => [ 'id' => 5, 'deposit_percent' => 25.0 ],
		6 => [ 'id' => 6, 'deposit_percent' => 0.0 ],
		7 => [ 'id' => 7, 'deposit_percent' => 100.0 ],
		8 => [ 'id' => 8, 'deposit_percent' => 33.335 ],
	];

	t_check( 'full price when no deposit (0%)', Payment_Manager::amount_due( [ 'payment_amount' => 120.0, 'service_id' => 6 ] ) === 120.0 );
	t_check( 'full price when deposit is 100%', Payment_Manager::amount_due( [ 'payment_amount' => 120.0, 'service_id' => 7 ] ) === 120.0 );
	t_check( '25% deposit of 120 = 30', Payment_Manager::amount_due( [ 'payment_amount' => 120.0, 'service_id' => 5 ] ) === 30.0 );
	t_check( 'fractional deposit rounds to cents (33.335% of 120 = 40.00)', Payment_Manager::amount_due( [ 'payment_amount' => 120.0, 'service_id' => 8 ] ) === 40.0 );
	t_check( 'unknown service falls back to full price', Payment_Manager::amount_due( [ 'payment_amount' => 90.0, 'service_id' => 999 ] ) === 90.0 );
	t_check( 'zero-priced booking dues nothing', Payment_Manager::amount_due( [ 'payment_amount' => 0, 'service_id' => 5 ] ) === 0.0 );

	$gateway = new class() implements \Bookingistic\Payments\Payment_Gateway {
		public function slug(): string {
			return 'stub';
		}
		public function label(): string {
			return 'Stub';
		}
		public function description(): string {
			return '';
		}
		public function is_available(): bool {
			return true;
		}
		public function is_enabled(): bool {
			return false;
		}
		public function boot(): void {
		}
	};
	t_check( 'gateway contract accepts a compliant implementation', $gateway instanceof \Bookingistic\Payments\Payment_Gateway );
	t_check( 'registry is empty without WooCommerce or filters', Payment_Manager::gateways() === [] );
}
