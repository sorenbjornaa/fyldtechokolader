<?php
/**
 * Plugin installation and database migrations.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Infrastructure;

use Fyldtechokolader\Core\Content\ContentRegistrar;
use Fyldtechokolader\Core\Migration\LegacyContentMapper;

defined( 'ABSPATH' ) || exit;

final class Installer {
	private const SCHEMA_VERSION = '1';

	public function activate(): void {
		$this->create_rating_table();
		$this->seed_shell_types();
		update_option( 'fyldtechokolader_core_schema_version', self::SCHEMA_VERSION, false );
		flush_rewrite_rules();
	}

	private function create_rating_table(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = $wpdb->prefix . 'fyld_ratings';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			chokolade_id bigint(20) unsigned NOT NULL,
			voter_hash char(64) NOT NULL,
			score tinyint(3) unsigned NOT NULL,
			source varchar(20) NOT NULL DEFAULT 'web',
			legacy_submission_id bigint(20) unsigned DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY chocolate_voter (chokolade_id,voter_hash),
			UNIQUE KEY legacy_submission (legacy_submission_id),
			KEY chokolade_id (chokolade_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	private function seed_shell_types(): void {
		$terms          = LegacyContentMapper::shell_types();
		$terms['andet'] = 'Andet';

		foreach ( $terms as $slug => $name ) {
			if ( ! term_exists( $slug, ContentRegistrar::SHELL_TAXONOMY ) ) {
				wp_insert_term( $name, ContentRegistrar::SHELL_TAXONOMY, array( 'slug' => $slug ) );
			}
		}
	}
}
