<?php
/**
 * Rating use cases and domain validation.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Ratings;

use Fyldtechokolader\Core\Content\ContentRegistrar;
use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

final class RatingService {
	private RatingRepository $ratings;

	public function __construct( RatingRepository $ratings ) {
		$this->ratings = $ratings;
	}

	/**
	 * @return array{average: float|null, count: int, distribution: array<int, int>, userScore: int, created: bool}
	 */
	public function rate( int $chocolate_id, mixed $raw_score, string $voter_token ): array {
		$this->assert_public_chocolate( $chocolate_id );

		$score      = Score::from_mixed( $raw_score );
		$voter_hash = $this->hash_voter_token( $voter_token );
		$saved      = $this->ratings->save( $chocolate_id, $voter_hash, $score );
		$aggregate  = $this->ratings->aggregate( $chocolate_id );

		return array_merge(
			$aggregate,
			array(
				'userScore' => $score,
				'created'   => $saved['created'],
			)
		);
	}

	/**
	 * @return array{average: float|null, count: int, distribution: array<int, int>}
	 */
	public function aggregate( int $chocolate_id ): array {
		$this->assert_public_chocolate( $chocolate_id );

		return $this->ratings->aggregate( $chocolate_id );
	}

	public function hash_voter_token( string $voter_token ): string {
		$length = strlen( $voter_token );

		if ( $length < 16 || $length > 255 || ! preg_match( '/^[A-Za-z0-9_-]+$/', $voter_token ) ) {
			throw new InvalidArgumentException( 'Den anonyme browseridentitet er ugyldig.' );
		}

		return hash_hmac( 'sha256', $voter_token, wp_salt( 'auth' ) );
	}

	private function assert_public_chocolate( int $chocolate_id ): void {
		$post = get_post( $chocolate_id );

		if ( ! $post || ContentRegistrar::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			throw new InvalidArgumentException( 'Chokoladen findes ikke.' );
		}
	}
}
