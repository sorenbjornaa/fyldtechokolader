<?php
/**
 * Parses historical Elementor form values without WordPress dependencies.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Migration;

use Fyldtechokolader\Core\Ratings\Score;
use InvalidArgumentException;

final class LegacySubmissionParser {
	/**
	 * @param array<int, array{key: string, value: string}> $values Elementor values for one submission.
	 * @return array{score: int|null, chocolate_id: int|null, status: string}
	 */
	public function parse( array $values ): array {
		$score        = null;
		$chocolate_id = null;

		foreach ( $values as $field ) {
			$key   = trim( (string) $field['key'] );
			$value = trim( (string) $field['value'] );

			if ( in_array( $key, array( 'rating', 'rading' ), true ) ) {
				try {
					$score = Score::from_mixed( $value );
				} catch ( InvalidArgumentException ) {
					// Keep an earlier valid value if a malformed duplicate field exists.
				}
			}

			if ( in_array( $key, array( 'post_id', 'field_56e245c' ), true ) && ctype_digit( $value ) ) {
				$chocolate_id = (int) $value;
			}

			if ( ctype_digit( $key ) && ctype_digit( $value ) && (int) $key === (int) $value ) {
				$chocolate_id = (int) $value;
			}
		}

		$status = match ( true ) {
			null !== $score && null !== $chocolate_id => 'candidate',
			null !== $score                           => 'missing_chocolate',
			null !== $chocolate_id                    => 'missing_score',
			default                                  => 'incomplete',
		};

		return array(
			'score'        => $score,
			'chocolate_id' => $chocolate_id,
			'status'       => $status,
		);
	}
}
