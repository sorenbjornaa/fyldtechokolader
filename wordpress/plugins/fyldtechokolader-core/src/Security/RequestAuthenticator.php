<?php
/**
 * HMAC authentication for server-to-server rating requests.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Security;

use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

final class RequestAuthenticator {
	private const MAX_CLOCK_SKEW = 300;

	public function authorize( WP_REST_Request $request ): true|WP_Error {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( ! defined( 'FYLDTECHOKOLADER_API_SECRET' ) || strlen( FYLDTECHOKOLADER_API_SECRET ) < 32 ) {
			return new WP_Error(
				'fyldtechokolader_secret_missing',
				'Ratingintegrationen er ikke konfigureret.',
				array( 'status' => 503 )
			);
		}

		$timestamp = $request->get_header( 'x-fyldtechokolader-timestamp' );
		$signature = $request->get_header( 'x-fyldtechokolader-signature' );

		if ( ! ctype_digit( $timestamp ) || 64 !== strlen( $signature ) ) {
			return $this->unauthorized();
		}

		if ( abs( time() - (int) $timestamp ) > self::MAX_CLOCK_SKEW ) {
			return $this->unauthorized();
		}

		$canonical = implode(
			"\n",
			array(
				$timestamp,
				strtoupper( $request->get_method() ),
				$request->get_route(),
				hash( 'sha256', $request->get_body() ),
			)
		);
		$expected  = hash_hmac( 'sha256', $canonical, FYLDTECHOKOLADER_API_SECRET );

		return hash_equals( $expected, strtolower( $signature ) ) ? true : $this->unauthorized();
	}

	private function unauthorized(): WP_Error {
		return new WP_Error(
			'fyldtechokolader_unauthorized',
			'Requesten kunne ikke godkendes.',
			array( 'status' => 401 )
		);
	}
}
