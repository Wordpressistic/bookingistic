<?php
/**
 * Elementor widget: Bookingistic booking form.
 *
 * Live-controls for service, template and calendar view — renders through the
 * same Booking_Form pipeline as the shortcode (identical DOM + behavior).
 *
 * @package Bookingistic
 */

namespace Bookingistic\Integrations\Elementor;

use Bookingistic\Database\Service_Repository;
use Bookingistic\Frontend\Booking_Form;
use Bookingistic\Frontend\Template_Loader;
use Bookingistic\Database\Staff_Repository;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

class Bookingistic_Widget extends \Elementor\Widget_Base {

	public function get_name(): string {
		return 'bookingistic_form';
	}

	public function get_title(): string {
		return __( 'Bookingistic Form', 'bookingistic' );
	}

	public function get_icon(): string {
		return 'eicon-calendar';
	}

	public function get_categories(): array {
		return [ 'bookingistic', 'general' ];
	}

	public function get_keywords(): array {
		return [ 'booking', 'appointment', 'calendar', 'schedule', 'bookingistic' ];
	}

	protected function register_controls(): void {
		$this->start_controls_section(
			'section_content',
			[ 'label' => __( 'Booking Form', 'bookingistic' ) ]
		);

		$services     = Service_Repository::all( [ 'status' => 'active' ] );
		$service_opts = [ '0' => __( 'First active service (auto)', 'bookingistic' ) ];
		foreach ( $services as $svc ) {
			$service_opts[ (string) $svc['id'] ] = sprintf( '%s (#%d)', $svc['name'], $svc['id'] );
		}

		$this->add_control(
			'service_id',
			[
				'label'   => __( 'Service', 'bookingistic' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '0',
				'options' => $service_opts,
			]
		);

		$template_opts = [ '' => __( '— Site default —', 'bookingistic' ) ];
		foreach ( Template_Loader::form_templates() as $key => $meta ) {
			$template_opts[ $key ] = $meta['label'];
		}

		$this->add_control(
			'template',
			[
				'label'   => __( 'Template', 'bookingistic' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => $template_opts,
			]
		);

		$calendar_opts = [ '' => __( '— Site default —', 'bookingistic' ) ];
		foreach ( Template_Loader::calendar_views() as $key => $label ) {
			$calendar_opts[ $key ] = $label;
		}

		$this->add_control(
			'calendar_view',
			[
				'label'   => __( 'Calendar view', 'bookingistic' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => $calendar_opts,
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();

		$service_id = (int) ( $settings['service_id'] ?? 0 );
		$service    = $service_id > 0 ? Service_Repository::find( $service_id ) : null;

		if ( ! $service ) {
			$services = Service_Repository::all( [ 'status' => 'active' ] );
			$service  = $services[0] ?? null;
		}

		if ( ! $service || $service['status'] !== 'active' ) {
			if ( \Elementor\Plugin::instance()->editor->is_edit_mode() ) {
				echo '<div class="bookingistic-error">' . esc_html__( 'No active booking service found. Create one under Bookingistic → Services.', 'bookingistic' ) . '</div>';
			}
			return;
		}

		$staff = Staff_Repository::for_service( (int) $service['id'] );

		echo Booking_Form::render(
			$service,
			$staff,
			0,
			'',
			[
				'template' => sanitize_key( $settings['template'] ?? '' ),
				'calendar' => sanitize_key( $settings['calendar_view'] ?? '' ),
			]
		); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside template pipeline.
	}
}
