<?php
/**
 * Outgoing webhook helper.
 *
 * Signs the body with HMAC-SHA256 and posts it as JSON via wp_remote_post.
 * Recipients can verify via the X-Bookingistic-Signature header.
 *
 * @package Bookingistic
 */

namespace Bookingistic\Helpers;

defined( 'ABSPATH' ) || exit;

class Webhook {

	public static function post( string $url, array $body, string $secret = '', array $extra_headers = [] ): array {
		$payload = wp_json_encode( $body );
		$headers = array_merge(
			[
				'Content-Type'        => 'application/json',
				'User-Agent'          => 'Bookingistic/' . BOOKINGISTIC_VERSION,
				'X-Bookingistic-Event' => $body['event'] ?? '',
			],
			$extra_headers
		);
		if ( $secret ) {
			$headers['X-Bookingistic-Signature'] = 'sha256=' . hash_hmac( 'sha256', $payload, $secret );
		}

		$res = wp_remote_post(
			$url,
			[
				'timeout' => 8,
				'headers' => $headers,
				'body'    => $payload,
			]
		);

		if ( is_wp_error( $res ) ) {
			return [ 'ok' => false, 'error' => $res->get_error_message(), 'code' => 0 ];
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		return [
			'ok'   => $code >= 200 && $code < 300,
			'code' => $code,
			'body' => wp_remote_retrieve_body( $res ),
		];
	}
}
