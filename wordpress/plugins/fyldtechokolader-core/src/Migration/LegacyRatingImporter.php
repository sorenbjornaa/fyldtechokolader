<?php
/**
 * Idempotent importer for legacy Elementor ratings.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Migration;

use Fyldtechokolader\Core\Content\ContentRegistrar;
use Fyldtechokolader\Core\Content\TextNormalizer;
use Fyldtechokolader\Core\Ratings\RatingRepository;
use RuntimeException;
use Throwable;
use wpdb;

defined( 'ABSPATH' ) || exit;

final class LegacyRatingImporter {
	public const EXPECTED_SAFE_RATINGS = 206;

	private wpdb $database;
	private RatingRepository $ratings;
	private LegacySubmissionParser $parser;

	public function __construct( wpdb $database, RatingRepository $ratings ) {
		$this->database = $database;
		$this->ratings  = $ratings;
		$this->parser   = new LegacySubmissionParser();
	}

	/**
	 * @return array<string, mixed>
	 */
	public function run( bool $commit = false ): array {
		$values_table = $this->database->prefix . 'e_submissions_values';

		if ( ! $this->table_exists( $values_table ) ) {
			throw new RuntimeException( 'Elementors submission-værditabel blev ikke fundet.' );
		}

		$rows = $this->database->get_results(
			"SELECT submission_id, `key`, `value` FROM {$values_table} ORDER BY submission_id ASC, id ASC",
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			throw new RuntimeException( 'Elementors historiske ratings kunne ikke læses.' );
		}

		$submissions = array();
		foreach ( $rows as $row ) {
			$submission_id                   = (int) $row['submission_id'];
			$submissions[ $submission_id ][] = array(
				'key'   => (string) $row['key'],
				'value' => (string) $row['value'],
			);
		}

		$report     = array(
			'mode'                => $commit ? 'commit' : 'dry-run',
			'submissions'         => count( $submissions ),
			'valid_scores'        => 0,
			'safe_candidates'     => 0,
			'missing_chocolate'   => 0,
			'missing_score'       => 0,
			'incomplete'          => 0,
			'invalid_chocolate'   => 0,
			'inserted'            => 0,
			'already_imported'    => 0,
			'expected_safe'       => self::EXPECTED_SAFE_RATINGS,
			'matches_expectation' => false,
			'by_chocolate'        => array(),
		);
		$candidates = array();

		foreach ( $submissions as $submission_id => $values ) {
			$parsed = $this->parser->parse( $values );

			if ( null !== $parsed['score'] ) {
				++$report['valid_scores'];
			}

			if ( 'candidate' !== $parsed['status'] ) {
				++$report[ $parsed['status'] ];
				continue;
			}

			$chocolate = get_post( $parsed['chocolate_id'] );
			if ( ! $chocolate || ContentRegistrar::POST_TYPE !== $chocolate->post_type ) {
				++$report['invalid_chocolate'];
				continue;
			}

			++$report['safe_candidates'];
			$chocolate_id = (int) $parsed['chocolate_id'];
			if ( ! isset( $report['by_chocolate'][ $chocolate_id ] ) ) {
				$report['by_chocolate'][ $chocolate_id ] = array(
					'title'       => TextNormalizer::plain( get_the_title( $chocolate_id ) ),
					'count'       => 0,
					'score_total' => 0,
				);
			}
			++$report['by_chocolate'][ $chocolate_id ]['count'];
			$report['by_chocolate'][ $chocolate_id ]['score_total'] += (int) $parsed['score'];

			$candidates[] = array(
				'submission_id' => (int) $submission_id,
				'chocolate_id'  => $chocolate_id,
				'score'         => (int) $parsed['score'],
			);
		}

		$report['matches_expectation'] = self::EXPECTED_SAFE_RATINGS === $report['safe_candidates'];
		foreach ( $report['by_chocolate'] as &$chocolate_report ) {
			$chocolate_report['average'] = round( $chocolate_report['score_total'] / $chocolate_report['count'], 2 );
			unset( $chocolate_report['score_total'] );
		}
		unset( $chocolate_report );
		ksort( $report['by_chocolate'], SORT_NUMERIC );

		if ( $commit && ! $report['matches_expectation'] ) {
			throw new RuntimeException( 'Importen blev afbrudt, fordi kontrollen ikke fandt præcis 206 sikre ratings.' );
		}

		if ( $commit ) {
			if ( false === $this->database->query( 'START TRANSACTION' ) ) {
				throw new RuntimeException( 'Databasetransaktionen kunne ikke startes.' );
			}

			try {
				foreach ( $candidates as $candidate ) {
					$voter_hash = hash_hmac( 'sha256', 'legacy:' . $candidate['submission_id'], wp_salt( 'auth' ) );
					$status     = $this->ratings->insert_legacy(
						$candidate['submission_id'],
						$candidate['chocolate_id'],
						$voter_hash,
						$candidate['score']
					);

					if ( 'inserted' === $status ) {
						++$report['inserted'];
					} else {
						++$report['already_imported'];
					}
				}

				if ( false === $this->database->query( 'COMMIT' ) ) {
					throw new RuntimeException( 'Databasetransaktionen kunne ikke afsluttes.' );
				}
			} catch ( Throwable $exception ) {
				$this->database->query( 'ROLLBACK' );
				throw new RuntimeException( 'Importen blev rullet tilbage uden delvise ændringer.', 0, $exception );
			}
		}

		return $report;
	}

	private function table_exists( string $table_name ): bool {
		$found = $this->database->get_var(
			$this->database->prepare( 'SHOW TABLES LIKE %s', $this->database->esc_like( $table_name ) )
		);

		return $table_name === $found;
	}
}
