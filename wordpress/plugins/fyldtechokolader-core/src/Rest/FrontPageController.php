<?php
/**
 * Public front-page REST endpoint.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Rest;

use Fyldtechokolader\Core\Content\TextNormalizer;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

final class FrontPageController {
	public function register_routes(): void {
		register_rest_route(
			ChocolateController::NAMESPACE,
			'/forside',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'show' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function show(): WP_REST_Response|WP_Error {
		$page_id = (int) get_option( 'page_on_front' );
		$page    = 'page' === get_option( 'show_on_front' ) && $page_id > 0 ? get_post( $page_id ) : null;
		if ( ! $page || 'page' !== $page->post_type || 'publish' !== $page->post_status || '' !== $page->post_password ) {
			return new WP_Error( 'ft_front_page_not_found', 'Ingen offentlig forside fundet.', array( 'status' => 404 ) );
		}
		$image_id = (int) get_post_meta( $page_id, 'ft_hero_image', true );
		$image    = $image_id > 0 ? wp_get_attachment_image_url( $image_id, 'full' ) : false;
		$text     = static fn ( string $name ): string => TextNormalizer::plain( (string) get_post_meta( $page_id, 'ft_' . $name, true ) );

		return new WP_REST_Response(
			array(
				'hero'         => array(
					'eyebrow'     => $text( 'hero_eyebrow' ),
					'heading'     => $text( 'hero_heading' ),
					'description' => $text( 'hero_description' ),
					'buttonLabel' => $text( 'hero_button_label' ),
					'image'       => $image ? array(
						'src' => $image,
						'alt' => TextNormalizer::plain( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ),
					) : null,
				),
				'introduction' => array(
					'eyebrow'     => $text( 'introduction_eyebrow' ),
					'heading'     => $text( 'introduction_heading' ),
					'description' => $text( 'introduction_description' ),
				),
			)
		);
	}
}
