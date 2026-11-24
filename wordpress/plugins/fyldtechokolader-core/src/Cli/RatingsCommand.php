<?php
/**
 * WP-CLI interface for controlled rating migration.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Cli;

use Fyldtechokolader\Core\Migration\LegacyRatingImporter;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

final class RatingsCommand {
	private LegacyRatingImporter $importer;

	public function __construct( LegacyRatingImporter $importer ) {
		$this->importer = $importer;
	}

	/**
	 * Audits or imports the historical Elementor ratings.
	 *
	 * ## OPTIONS
	 *
	 * [--commit]
	 * : Write the safe ratings. Without this flag, the command is a dry-run.
	 *
	 * ## EXAMPLES
	 *
	 *     wp fyldtechokolader ratings import-legacy
	 *     wp fyldtechokolader ratings import-legacy --commit
	 *
	 * @param array<int, string>   $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Named arguments.
	 */
	public function import_legacy( array $args, array $assoc_args ): void {
		unset( $args );
		$commit = isset( $assoc_args['commit'] );

		if ( $commit ) {
			\WP_CLI::confirm( 'Importer de sikkert identificerede historiske ratings?' );
		}

		try {
			$report = $this->importer->run( $commit );
		} catch ( RuntimeException $exception ) {
			\WP_CLI::error( $exception->getMessage() );
			return;
		}

		\WP_CLI::line( wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

		if ( ! $report['matches_expectation'] ) {
			\WP_CLI::warning( 'Dry-run matcher ikke de forventede 206 sikre ratings. Import må ikke godkendes endnu.' );
		}

		if ( $commit ) {
			\WP_CLI::success( sprintf( '%d ratings importeret; %d fandtes allerede.', $report['inserted'], $report['already_imported'] ) );
		} else {
			\WP_CLI::success( 'Dry-run færdig. Ingen data er ændret.' );
		}
	}
}
