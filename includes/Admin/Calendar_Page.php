<?php
/**
 * Admin calendar page.
 *
 * Renders the shell — all interactivity (view switch, event fetch, modal,
 * filter handling) lives in assets/admin/js/admin.js and pulls JSON from
 * /wp-json/bookingistic/v1/calendar/events.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Admin;

use Bookingistic\Database\Service_Repository;
use Bookingistic\Database\Staff_Repository;

defined( 'ABSPATH' ) || exit;

class Calendar_Page {

	public static function render(): void {
		$services = Service_Repository::all();
		$staff    = Staff_Repository::all( [ 'status' => 'active' ] );
		include BOOKINGISTIC_DIR . 'templates/admin/calendar.php';
	}
}
