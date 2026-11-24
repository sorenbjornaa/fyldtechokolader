<?php
/**
 * Plugin composition root.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core;

use Fyldtechokolader\Core\Admin\RatingsPage;
use Fyldtechokolader\Core\Cli\RatingsCommand;
use Fyldtechokolader\Core\Content\ChocolateRepository;
use Fyldtechokolader\Core\Content\ContentRegistrar;
use Fyldtechokolader\Core\Infrastructure\RateLimiter;
use Fyldtechokolader\Core\Migration\LegacyRatingImporter;
use Fyldtechokolader\Core\Ratings\RatingRepository;
use Fyldtechokolader\Core\Ratings\RatingService;
use Fyldtechokolader\Core\Rest\ChocolateController;
use Fyldtechokolader\Core\Rest\RatingController;
use Fyldtechokolader\Core\Security\RequestAuthenticator;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	public function boot(): void {
		global $wpdb;

		$content_registrar = new ContentRegistrar();
		$rating_repository = new RatingRepository( $wpdb );
		$rating_service    = new RatingService( $rating_repository );
		$chocolates        = new ChocolateRepository( $rating_repository );
		$legacy_importer   = new LegacyRatingImporter( $wpdb, $rating_repository );

		add_action( 'init', array( $content_registrar, 'register' ), 20 );
		add_action(
			'rest_api_init',
			static function () use ( $chocolates, $rating_service ): void {
				$chocolate_controller = new ChocolateController( $chocolates );
				$rating_controller    = new RatingController(
					$rating_service,
					new RequestAuthenticator(),
					new RateLimiter()
				);

				$chocolate_controller->register_routes();
				$rating_controller->register_routes();
			}
		);

		if ( is_admin() ) {
			$ratings_page = new RatingsPage( $rating_repository, $legacy_importer );
			add_action( 'admin_menu', array( $ratings_page, 'register' ) );
			add_action( 'admin_post_fyldtechokolader_legacy_dry_run', array( $ratings_page, 'handle_dry_run' ) );
			add_action( 'admin_post_fyldtechokolader_legacy_import', array( $ratings_page, 'handle_import' ) );
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'fyldtechokolader ratings', new RatingsCommand( $legacy_importer ) );
		}
	}
}
