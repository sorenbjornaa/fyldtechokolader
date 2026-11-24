<?php
/**
 * Rating score validation.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Ratings;

use InvalidArgumentException;

final class Score {
	public const MIN = 1;
	public const MAX = 7;

	public static function from_mixed( mixed $value ): int {
		if ( is_int( $value ) ) {
			$score = $value;
		} elseif ( is_string( $value ) && preg_match( '/^[1-7]$/', $value ) ) {
			$score = (int) $value;
		} else {
			throw new InvalidArgumentException( 'Rating skal være et heltal fra 1 til 7.' );
		}

		if ( $score < self::MIN || $score > self::MAX ) {
			throw new InvalidArgumentException( 'Rating skal være et heltal fra 1 til 7.' );
		}

		return $score;
	}
}
