<?php
/**
 * Slot generator — turn working-hours + service duration into candidate slot starts.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Booking;

use Bookingistic\Helpers\Date_Time;

defined( 'ABSPATH' ) || exit;

class Slot_Generator {

	/**
	 * @param string             $date            Date in YYYY-MM-DD (in $tz).
	 * @param string             $work_start_hhmm e.g. '09:00'.
	 * @param string             $work_end_hhmm   e.g. '17:00'.
	 * @param int                $duration_min    Service duration in minutes.
	 * @param int                $buffer_before   Buffer before each slot.
	 * @param int                $buffer_after    Buffer after each slot.
	 * @param int                $interval_min    Slot interval (e.g. 15).
	 * @param \DateTimeZone      $tz              Timezone the working hours are expressed in.
	 *
	 * @return array<int,array{start_local:string,end_local:string,start_utc:string,end_utc:string}>
	 */
	public static function for_day(
		string $date,
		string $work_start_hhmm,
		string $work_end_hhmm,
		int $duration_min,
		int $buffer_before,
		int $buffer_after,
		int $interval_min,
		\DateTimeZone $tz
	): array {
		$slots         = [];
		$total_minutes = max( 1, $duration_min + $buffer_before + $buffer_after );

		try {
			$cursor = new \DateTimeImmutable( $date . ' ' . $work_start_hhmm, $tz );
			$end    = new \DateTimeImmutable( $date . ' ' . $work_end_hhmm, $tz );
		} catch ( \Throwable $e ) {
			return [];
		}

		while ( true ) {
			$slot_start = $cursor->modify( '+' . $buffer_before . ' minutes' );
			$slot_end   = $slot_start->modify( '+' . $duration_min . ' minutes' );
			$bound_end  = $slot_end->modify( '+' . $buffer_after . ' minutes' );

			if ( $bound_end > $end ) {
				break;
			}

			$start_utc = $slot_start->setTimezone( Date_Time::utc() );
			$end_utc   = $slot_end->setTimezone( Date_Time::utc() );

			$slots[] = [
				'start_local' => $slot_start->format( 'Y-m-d H:i' ),
				'end_local'   => $slot_end->format( 'Y-m-d H:i' ),
				'start_utc'   => $start_utc->format( 'Y-m-d H:i:s' ),
				'end_utc'     => $end_utc->format( 'Y-m-d H:i:s' ),
			];

			$cursor = $cursor->modify( '+' . max( 1, $interval_min ) . ' minutes' );
		}

		return $slots;
	}
}
