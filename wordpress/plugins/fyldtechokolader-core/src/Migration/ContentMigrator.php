<?php
/**
 * Controlled migration of legacy chocolate content.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Migration;

use Fyldtechokolader\Core\Content\ContentRegistrar;
use RuntimeException;
use Throwable;
use wpdb;

defined( 'ABSPATH' ) || exit;

final class ContentMigrator {
	private const EXPECTED_CHOCOLATES = 28;

	private wpdb $database;
	private LegacyContentMapper $mapper;

	public function __construct( wpdb $database ) {
		$this->database = $database;
		$this->mapper   = new LegacyContentMapper();
	}

	/**
	 * @return array<string, mixed>
	 */
	public function run( bool $commit ): array {
		$post_ids = get_posts(
			array(
				'post_type'      => ContentRegistrar::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		$rows     = array();
		$changes  = 0;

		foreach ( $post_ids as $post_id ) {
			$post_id = (int) $post_id;
			$mapped  = $this->mapper->map(
				$post_id,
				(string) get_post_meta( $post_id, 'arstal', true ),
				(string) get_post_meta( $post_id, 'skald', true )
			);
			$current = $this->current_values( $post_id );
			$changed = $this->has_changes( $current, $mapped );

			if ( $changed ) {
				++$changes;
			}

			$rows[] = array(
				'id'              => $post_id,
				'title'           => get_the_title( $post_id ),
				'legacy_date'     => (string) get_post_meta( $post_id, 'arstal', true ),
				'legacy_shell'    => (string) get_post_meta( $post_id, 'skald', true ),
				'production_year' => $mapped['production_year'],
				'production_date' => $mapped['production_date'],
				'shell_slug'      => $mapped['shell_slug'],
				'shell_name'      => $mapped['shell_name'],
				'changed'         => $changed,
			);
		}

		$matches_expectation = self::EXPECTED_CHOCOLATES === count( $rows );
		if ( $commit && ! $matches_expectation ) {
			throw new RuntimeException( 'Migreringen kræver præcis 28 publicerede chokolader.' );
		}

		if ( $commit ) {
			$this->commit( $rows );
		}

		return array(
			'mode'                => $commit ? 'commit' : 'dry-run',
			'chocolates'          => count( $rows ),
			'changes'             => $changes,
			'unchanged'           => count( $rows ) - $changes,
			'matches_expectation' => $matches_expectation,
			'rows'                => $rows,
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $rows Migration rows.
	 */
	private function commit( array $rows ): void {
		$term_ids = $this->ensure_terms();
		$this->database->query( 'START TRANSACTION' );

		try {
			foreach ( $rows as $row ) {
				$post_id = (int) $row['id'];
				update_post_meta( $post_id, 'produktionsaar', (int) $row['production_year'] );
				update_post_meta( $post_id, '_produktionsaar', 'field_fyldtechokolader_produktionsaar' );

				if ( null === $row['production_date'] ) {
					delete_post_meta( $post_id, 'produktionsdato' );
				} else {
					update_post_meta( $post_id, 'produktionsdato', str_replace( '-', '', (string) $row['production_date'] ) );
					update_post_meta( $post_id, '_produktionsdato', 'field_fyldtechokolader_produktionsdato' );
				}

				$result = wp_set_object_terms(
					$post_id,
					array( $term_ids[ (string) $row['shell_slug'] ] ),
					ContentRegistrar::SHELL_TAXONOMY,
					false
				);

				if ( is_wp_error( $result ) ) {
					throw new RuntimeException( $result->get_error_message() );
				}
			}

			$this->database->query( 'COMMIT' );
			update_option( 'fyldtechokolader_content_migration_version', '1', false );
		} catch ( Throwable $exception ) {
			$this->database->query( 'ROLLBACK' );

			foreach ( $rows as $row ) {
				clean_post_cache( (int) $row['id'] );
			}

			throw $exception;
		}
	}

	/**
	 * @return array<string, int>
	 */
	private function ensure_terms(): array {
		$term_ids = array();

		foreach ( LegacyContentMapper::shell_types() as $slug => $name ) {
			$term = term_exists( $slug, ContentRegistrar::SHELL_TAXONOMY );

			if ( ! $term ) {
				$term = wp_insert_term( $name, ContentRegistrar::SHELL_TAXONOMY, array( 'slug' => $slug ) );
			}

			if ( is_wp_error( $term ) ) {
				throw new RuntimeException( $term->get_error_message() );
			}

			$term_ids[ $slug ] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
		}

		return $term_ids;
	}

	/**
	 * @return array{production_year: int|null, production_date: string|null, shell_slug: string|null}
	 */
	private function current_values( int $post_id ): array {
		$terms = get_the_terms( $post_id, ContentRegistrar::SHELL_TAXONOMY );

		$year = (int) get_post_meta( $post_id, 'produktionsaar', true );
		$date = (string) get_post_meta( $post_id, 'produktionsdato', true );

		if ( preg_match( '/^(\d{4})-?(\d{2})-?(\d{2})$/', $date, $parts ) ) {
			$date = sprintf( '%s-%s-%s', $parts[1], $parts[2], $parts[3] );
		}

		return array(
			'production_year' => 0 !== $year ? $year : null,
			'production_date' => '' !== $date ? $date : null,
			'shell_slug'      => is_array( $terms ) && $terms ? $terms[0]->slug : null,
		);
	}

	/**
	 * @param array<string, mixed> $current Current values.
	 * @param array<string, mixed> $mapped  Mapped values.
	 */
	private function has_changes( array $current, array $mapped ): bool {
		return $current['production_year'] !== $mapped['production_year']
			|| $current['production_date'] !== $mapped['production_date']
			|| $current['shell_slug'] !== $mapped['shell_slug'];
	}
}
