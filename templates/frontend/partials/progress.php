<?php
/**
 * Partial: step progress indicator ("Step 1 of 3" + 3-segment meter).
 * JS updates the text and the meter as the visitor moves between steps.
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="bookingistic-progress" data-bookingistic-progress>
	<span class="bookingistic-progress__text" data-bookingistic-progress-text>
		<?php
		/* translators: 1: current step number, 2: total number of steps. */
		echo esc_html( sprintf( __( 'Step %1$d of %2$d', 'bookingistic' ), 1, 3 ) );
		?>
	</span>
	<span class="bookingistic-progress__meter" aria-hidden="true"><i></i><i></i><i></i></span>
</div>
