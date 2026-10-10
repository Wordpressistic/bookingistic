<?php
/**
 * Legacy alias — the original single booking form template.
 *
 * Since 2.2 the form is rendered through includes/Frontend/Template_Loader.php
 * with four designs (form-classic/modern/split/minimal.php). This file is kept
 * so themes that overrode templates/frontend/booking-form.php keep working.
 *
 * @package Bookingistic
 */

defined( 'ABSPATH' ) || exit;

require __DIR__ . '/form-classic.php';
