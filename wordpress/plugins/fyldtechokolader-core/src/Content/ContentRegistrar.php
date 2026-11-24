<?php
/**
 * Registers the plugin-owned content model.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Content;

defined( 'ABSPATH' ) || exit;

final class ContentRegistrar {
	public const POST_TYPE      = 'chokolade';
	public const SHELL_TAXONOMY = 'chokolade_skaltype';

	public function register(): void {
		$this->register_post_type();
		$this->register_shell_taxonomy();
	}

	private function register_post_type(): void {
		if ( post_type_exists( self::POST_TYPE ) ) {
			return;
		}

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Chokolader', 'fyldtechokolader-core' ),
					'singular_name' => __( 'Chokolade', 'fyldtechokolader-core' ),
					'add_new_item'  => __( 'Tilføj chokolade', 'fyldtechokolader-core' ),
					'edit_item'     => __( 'Rediger chokolade', 'fyldtechokolader-core' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'chokolade' ),
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
				'menu_icon'    => 'dashicons-heart',
			)
		);
	}

	private function register_shell_taxonomy(): void {
		if ( taxonomy_exists( self::SHELL_TAXONOMY ) ) {
			return;
		}

		register_taxonomy(
			self::SHELL_TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Skaltyper', 'fyldtechokolader-core' ),
					'singular_name' => __( 'Skaltype', 'fyldtechokolader-core' ),
				),
				'public'            => true,
				'hierarchical'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'skaltype' ),
			)
		);
	}
}
