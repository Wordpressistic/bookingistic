<?php
/**
 * Thin wrapper that exposes Email_Log_Repository under the Email namespace
 * for callers that group logger calls with sender calls.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Email;

use Bookingistic\Database\Email_Log_Repository;

defined( 'ABSPATH' ) || exit;

class Email_Logger {

	public static function recent( int $limit = 50 ): array {
		return Email_Log_Repository::recent( $limit );
	}
}
