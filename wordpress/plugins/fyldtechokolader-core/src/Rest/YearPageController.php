<?php
/**
 * Serves the public landing pages assigned to a production year.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Rest;

use Fyldtechokolader\Core\Content\ContentRegistrar;
use Fyldtechokolader\Core\Content\SeoMetadata;
use Fyldtechokolader\Core\Content\TextNormalizer;
use WP_Query;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

final class YearPageController {
	public function register_routes(): void {
		register_rest_route(
			ChocolateController::NAMESPACE,
			'/aargange',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'index' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function index(): WP_REST_Response {
		$query = new WP_Query(
			array(
				'post_type'              => 'page',
				'post_status'            => 'publish',
				'has_password'           => false,
				'post_parent'            => 0,
				'post__not_in'           => array_filter( array( (int) get_option( 'page_on_front' ), (int) get_option( 'wp_page_for_privacy_policy' ) ) ),
				'posts_per_page'         => 100,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					'landing_year' => array(
						'key'     => ContentRegistrar::LANDING_YEAR_META,
						'value'   => array( 2000, 2100 ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					),
				),
				'orderby'                => array(
					'landing_year' => 'DESC',
					'date'         => 'DESC',
					'ID'           => 'DESC',
				),
			)
		);

		$pages = array();
		foreach ( $query->posts as $post ) {
			$year = (string) get_post_meta( $post->ID, ContentRegistrar::LANDING_YEAR_META, true );
			$slug = $post->post_name;
			if ( ! preg_match( '/^(20[0-9]{2}|2100)$/D', $year ) || in_array( $slug, array( '', '404', '500', 'api', 'chokolade', 'privatlivspolitik' ), true ) ) {
				continue;
			}

			$image_id = (int) get_post_meta( $post->ID, 'ft_landing_image', true );
			$image    = $image_id > 0 ? wp_get_attachment_image_url( $image_id, 'full' ) : false;
			$text     = static fn ( string $name ): string => TextNormalizer::plain( (string) get_post_meta( $post->ID, 'ft_landing_' . $name, true ) );
			$heading  = $text( 'heading' );
			$pages[]  = array(
				'slug'        => $slug,
				'year'        => (int) $year,
				'eyebrow'     => $text( 'eyebrow' ),
				'heading'     => '' !== $heading ? $heading : TextNormalizer::plain( get_the_title( $post ) ),
				'description' => $text( 'description' ),
				'image'       => $image ? array(
					'src' => $image,
					'alt' => TextNormalizer::plain( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ),
				) : null,
				'seo'         => SeoMetadata::for_post( $post, $image ? $image : null ),
			);
		}

		return new WP_REST_Response( $pages );
	}
}
