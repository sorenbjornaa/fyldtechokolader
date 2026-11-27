<?php
/**
 * Maps Yoast metadata to the project's public content contract.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Content;

use WP_Post;

defined( 'ABSPATH' ) || exit;

final class SeoMetadata {
	public static function for_post( WP_Post $post, ?string $fallback_image = null ): array {
		$yoast       = function_exists( 'YoastSEO' ) ? YoastSEO()->meta->for_post( $post->ID ) : null;
		$title       = TextNormalizer::plain( (string) ( $yoast->title ?? '' ) );
		$description = TextNormalizer::plain( (string) ( $yoast->description ?? '' ) );
		$images      = (array) ( $yoast->open_graph_images ?? array() );
		$image       = esc_url_raw( (string) ( $images[0]['url'] ?? get_post_meta( $post->ID, '_yoast_wpseo_opengraph-image', true ) ) );
		$saved_title = (string) get_post_meta( $post->ID, '_yoast_wpseo_title', true );
		$saved_desc  = (string) get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
		$index       = (string) get_post_meta( $post->ID, '_yoast_wpseo_meta-robots-noindex', true );
		$follow      = (string) get_post_meta( $post->ID, '_yoast_wpseo_meta-robots-nofollow', true );
		$robots      = (array) ( $yoast->robots ?? array() );
		// The CMS itself is out of the index; that site-wide choice must not index-block Next.js.
		$type_noindex = class_exists( '\WPSEO_Options' ) && \WPSEO_Options::get( 'noindex-' . $post->post_type, false );
		$noindex      = '1' === $index || ( '2' !== $index && $type_noindex );
		if ( (bool) get_option( 'blog_public' ) ) {
			$noindex = $noindex || in_array( 'noindex', $robots, true );
		}

		return array(
			'title'       => '' !== $title ? $title : TextNormalizer::plain( '' !== $saved_title ? $saved_title : get_the_title( $post ) ),
			'description' => '' !== $description ? $description : TextNormalizer::plain( '' !== $saved_desc ? $saved_desc : $post->post_excerpt ),
			'image'       => '' !== $image ? $image : $fallback_image,
			'canonical'   => esc_url_raw( (string) get_post_meta( $post->ID, '_yoast_wpseo_canonical', true ) ),
			'noindex'     => $noindex,
			'nofollow'    => '1' === $follow || ( (bool) get_option( 'blog_public' ) && in_array( 'nofollow', $robots, true ) ),
			'source'      => $title ? 'yoast' : 'wordpress',
		);
	}
}
