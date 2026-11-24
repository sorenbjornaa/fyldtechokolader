<?php
/**
 * Short-lived abuse protection without storing IP addresses.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Infrastructure;

defined( 'ABSPATH' ) || exit;

final class RateLimiter {
	private const LIMIT  = 20;
	private const WINDOW = 600;

	public function consume( int $chocolate_id, string $voter_hash ): bool {
		$key   = 'fyld_rate_' . md5( $chocolate_id . ':' . $voter_hash );
		$count = (int) get_transient( $key );

		if ( $count >= self::LIMIT ) {
			return false;
		}

		set_transient( $key, $count + 1, self::WINDOW );

		return true;
	}
}
