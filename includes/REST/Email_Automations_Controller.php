<?php
/**
 * Email automations REST controller.
 *
 * GET    /email-automations
 * POST   /email-automations
 * GET    /email-automations/{id}
 * PATCH  /email-automations/{id}
 * DELETE /email-automations/{id}
 * POST   /email-automations/{id}/test  — send a rendered preview to the requester
 *
 * @package Bookingistic
 */

namespace Bookingistic\REST;

use Bookingistic\Database\Email_Automation_Repository;
use Bookingistic\Email\Email_Sender;
use Bookingistic\Email\Email_Template_Renderer;

defined( 'ABSPATH' ) || exit;

class Email_Automations_Controller {

	public static function register_routes(): void {
		$ns = BOOKINGISTIC_REST_NAMESPACE;

		register_rest_route(
			$ns,
			'/email-automations',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ self::class, 'index' ],
					'permission_callback' => [ REST_Controller::class, 'require_manage' ],
				],
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ self::class, 'create' ],
					'permission_callback' => [ REST_Controller::class, 'require_manage' ],
				],
			]
		);

		register_rest_route(
			$ns,
			'/email-automations/(?P<id>\d+)',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ self::class, 'show' ],
					'permission_callback' => [ REST_Controller::class, 'require_manage' ],
				],
				[
					'methods'             => [ 'PATCH', 'PUT' ],
					'callback'            => [ self::class, 'patch' ],
					'permission_callback' => [ REST_Controller::class, 'require_manage' ],
				],
				[
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => [ self::class, 'delete' ],
					'permission_callback' => [ REST_Controller::class, 'require_manage' ],
				],
			]
		);

		register_rest_route(
			$ns,
			'/email-automations/(?P<id>\d+)/test',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ self::class, 'test_send' ],
				'permission_callback' => [ REST_Controller::class, 'require_manage' ],
			]
		);
	}

	public static function index() {
		return rest_ensure_response( Email_Automation_Repository::all() );
	}

	public static function show( \WP_REST_Request $req ) {
		$rule = Email_Automation_Repository::find( (int) $req['id'] );
		if ( ! $rule ) {
			return new \WP_Error( 'not_found', __( 'Automation not found.', 'bookingistic' ), [ 'status' => 404 ] );
		}
		return rest_ensure_response( $rule );
	}

	public static function create( \WP_REST_Request $req ) {
		$payload = $req->get_json_params() ?: $req->get_params();
		$rule    = Email_Automation_Repository::create( $payload );
		if ( is_wp_error( $rule ) ) {
			return $rule;
		}
		return rest_ensure_response( $rule );
	}

	public static function patch( \WP_REST_Request $req ) {
		$payload = $req->get_json_params() ?: $req->get_params();
		$rule    = Email_Automation_Repository::update( (int) $req['id'], $payload );
		if ( is_wp_error( $rule ) ) {
			return $rule;
		}
		return rest_ensure_response( $rule );
	}

	public static function delete( \WP_REST_Request $req ) {
		$ok = Email_Automation_Repository::delete( (int) $req['id'] );
		return rest_ensure_response( [ 'deleted' => $ok ] );
	}

	public static function test_send( \WP_REST_Request $req ) {
		$rule = Email_Automation_Repository::find( (int) $req['id'] );
		if ( ! $rule ) {
			return new \WP_Error( 'not_found', __( 'Automation not found.', 'bookingistic' ), [ 'status' => 404 ] );
		}

		$to = sanitize_email( (string) $req->get_param( 'to' ) );
		if ( ! is_email( $to ) ) {
			$current = wp_get_current_user();
			$to      = $current && $current->user_email ? $current->user_email : get_option( 'admin_email' );
		}

		$vars    = Email_Template_Renderer::sample_variables();
		$subject = '[TEST] ' . Email_Template_Renderer::render_subject( (string) $rule['subject'], $vars );
		$body    = Email_Template_Renderer::render_body( (string) $rule['body'], $vars );

		$sent = Email_Sender::send(
			$to,
			$subject,
			$body,
			[ 'automation_id' => (int) $rule['id'] ]
		);

		return rest_ensure_response(
			[
				'sent'      => (bool) $sent,
				'to'        => $to,
				'subject'   => $subject,
			]
		);
	}
}
