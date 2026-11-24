<?php
/**
 * Persistence for individual ratings and aggregates.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Ratings;

use RuntimeException;
use wpdb;

defined( 'ABSPATH' ) || exit;

final class RatingRepository {
	private wpdb $database;
	private string $table_name;

	public function __construct( wpdb $database ) {
		$this->database   = $database;
		$this->table_name = $database->prefix . 'fyld_ratings';
	}

	/**
	 * Creates or updates one browser's rating for a chocolate.
	 *
	 * @return array{created: bool, score: int}
	 */
	public function save( int $chocolate_id, string $voter_hash, int $score ): array {
		$existing_id = $this->database->get_var(
			$this->database->prepare(
				"SELECT id FROM {$this->table_name} WHERE chokolade_id = %d AND voter_hash = %s",
				$chocolate_id,
				$voter_hash
			)
		);
		$now         = current_time( 'mysql', true );

		if ( $existing_id ) {
			$result = $this->database->update(
				$this->table_name,
				array(
					'score'      => $score,
					'updated_at' => $now,
				),
				array( 'id' => (int) $existing_id ),
				array( '%d', '%s' ),
				array( '%d' )
			);

			if ( false === $result ) {
				throw new RuntimeException( 'Ratingen kunne ikke opdateres.' );
			}

			return array(
				'created' => false,
				'score'   => $score,
			);
		}

		$result = $this->database->insert(
			$this->table_name,
			array(
				'chokolade_id' => $chocolate_id,
				'voter_hash'   => $voter_hash,
				'score'        => $score,
				'source'       => 'web',
				'created_at'   => $now,
				'updated_at'   => $now,
			),
			array( '%d', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			throw new RuntimeException( 'Ratingen kunne ikke gemmes.' );
		}

		return array(
			'created' => true,
			'score'   => $score,
		);
	}

	/**
	 * Inserts one historical rating without changing an existing import.
	 *
	 * @return 'inserted'|'existing'
	 */
	public function insert_legacy( int $submission_id, int $chocolate_id, string $voter_hash, int $score ): string {
		$existing = $this->database->get_var(
			$this->database->prepare(
				"SELECT id FROM {$this->table_name} WHERE legacy_submission_id = %d",
				$submission_id
			)
		);

		if ( $existing ) {
			return 'existing';
		}

		$now    = current_time( 'mysql', true );
		$result = $this->database->insert(
			$this->table_name,
			array(
				'chokolade_id'         => $chocolate_id,
				'voter_hash'           => $voter_hash,
				'score'                => $score,
				'source'               => 'legacy',
				'legacy_submission_id' => $submission_id,
				'created_at'           => $now,
				'updated_at'           => $now,
			),
			array( '%d', '%s', '%d', '%s', '%d', '%s', '%s' )
		);

		if ( false === $result ) {
			throw new RuntimeException( 'En historisk rating kunne ikke importeres.' );
		}

		return 'inserted';
	}

	/**
	 * @return array{average: float|null, count: int, distribution: array<int, int>}
	 */
	public function aggregate( int $chocolate_id ): array {
		$rows = $this->database->get_results(
			$this->database->prepare(
				"SELECT score, COUNT(*) AS rating_count FROM {$this->table_name} WHERE chokolade_id = %d GROUP BY score ORDER BY score ASC",
				$chocolate_id
			),
			ARRAY_A
		);

		$distribution = array_fill( Score::MIN, Score::MAX, 0 );
		$count        = 0;
		$total        = 0;

		foreach ( $rows as $row ) {
			$score                  = (int) $row['score'];
			$rating_count           = (int) $row['rating_count'];
			$distribution[ $score ] = $rating_count;
			$count                 += $rating_count;
			$total                 += $score * $rating_count;
		}

		return array(
			'average'      => $count > 0 ? round( $total / $count, 2 ) : null,
			'count'        => $count,
			'distribution' => $distribution,
		);
	}

	public function user_score( int $chocolate_id, string $voter_hash ): ?int {
		$value = $this->database->get_var(
			$this->database->prepare(
				"SELECT score FROM {$this->table_name} WHERE chokolade_id = %d AND voter_hash = %s",
				$chocolate_id,
				$voter_hash
			)
		);

		return null === $value ? null : (int) $value;
	}

	/**
	 * @return array<int, array{chokolade_id: int, average: float, count: int}>
	 */
	public function overview(): array {
		$rows = $this->database->get_results(
			"SELECT chokolade_id, ROUND(AVG(score), 2) AS average, COUNT(*) AS rating_count FROM {$this->table_name} GROUP BY chokolade_id ORDER BY rating_count DESC",
			ARRAY_A
		);

		return array_map(
			static fn ( array $row ): array => array(
				'chokolade_id' => (int) $row['chokolade_id'],
				'average'      => (float) $row['average'],
				'count'        => (int) $row['rating_count'],
			),
			$rows
		);
	}
}
