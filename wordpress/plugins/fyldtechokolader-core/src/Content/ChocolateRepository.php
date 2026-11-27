<?php
/**
 * Maps WordPress chocolate content to the stable API model.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Content;

use Fyldtechokolader\Core\Ratings\RatingRepository;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

final class ChocolateRepository {
	private RatingRepository $ratings;

	public function __construct( RatingRepository $ratings ) {
		$this->ratings = $ratings;
	}

	/**
	 * @param array<string, mixed> $filters Validated API filters.
	 * @return array{items: array<int, array<string, mixed>>, total: int, pages: int}
	 */
	public function find_all( array $filters ): array {
		$page     = (int) ( $filters['page'] ?? 1 );
		$per_page = (int) ( $filters['per_page'] ?? 12 );
		$orderby  = (string) ( $filters['orderby'] ?? 'date' );
		$order    = (string) ( $filters['order'] ?? 'DESC' );
		$args     = array(
			'post_type'      => ContentRegistrar::POST_TYPE,
			'post_status'    => 'publish',
			'paged'          => $page,
			'posts_per_page' => $per_page,
			'order'          => $order,
			'orderby'        => $this->map_orderby( $orderby ),
		);

		if ( ! empty( $filters['search'] ) ) {
			$args['s'] = (string) $filters['search'];
		}

		if ( 'produktionsdato' === $orderby ) {
			$args['meta_key'] = 'produktionsdato';
			$args['orderby']  = 'meta_value';
		}

		if ( ! empty( $filters['year'] ) ) {
			$year               = (string) (int) $filters['year'];
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => 'produktionsaar',
					'value'   => $year,
					'compare' => '=',
				),
				array(
					'key'     => 'arstal',
					'value'   => $year,
					'compare' => 'LIKE',
				),
			);
		}

		if ( ! empty( $filters['shellType'] ) && taxonomy_exists( ContentRegistrar::SHELL_TAXONOMY ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => ContentRegistrar::SHELL_TAXONOMY,
					'field'    => 'slug',
					'terms'    => (string) $filters['shellType'],
				),
			);
		}

		$query = new WP_Query( $args );
		$items = array_map(
			fn ( WP_Post $post ): array => $this->map( $post ),
			$query->posts
		);

		return array(
			'items' => $items,
			'total' => (int) $query->found_posts,
			'pages' => (int) $query->max_num_pages,
		);
	}

	public function find_by_slug( string $slug ): ?WP_Post {
		$posts = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => ContentRegistrar::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);

		return $posts ? $posts[0] : null;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function map( WP_Post $post ): array {
		$image_id    = get_post_thumbnail_id( $post );
		$image       = $image_id ? wp_get_attachment_image_src( $image_id, 'full' ) : false;
		$description = $this->first_meta( $post->ID, array( 'smagsbeskrivelse', 'indhold' ) );
		$badge       = $this->first_meta( $post->ID, array( 'badge', 'splash' ) );
		$year        = $this->production_year( $post->ID );
		$date        = $this->production_date( $post->ID );
		$shell_type  = $this->shell_type( $post->ID );
		$rating      = $this->ratings->aggregate( $post->ID );

		return array(
			'id'             => $post->ID,
			'slug'           => $post->post_name,
			'title'          => TextNormalizer::plain( get_the_title( $post ) ),
			'description'    => TextNormalizer::plain( $description ),
			'productionYear' => $year,
			'productionDate' => $date,
			'shellType'      => $shell_type,
			'badge'          => '' !== $badge ? $badge : null,
			'recipeHtml'     => wp_kses_post( do_blocks( $post->post_content ) ),
			'image'          => $image ? array(
				'id'     => $image_id,
				'src'    => $image[0],
				'alt'    => TextNormalizer::plain( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ),
				'width'  => (int) $image[1],
				'height' => (int) $image[2],
			) : null,
			'rating'         => array(
				'average' => $rating['average'],
				'count'   => $rating['count'],
			),
			'seo'            => $this->seo( $post, $image ? $image[0] : null ),
			'modifiedAt'     => get_post_modified_time( DATE_ATOM, true, $post ),
		);
	}

	/**
	 * @return array<int, array{slug: string, name: string, count: int}>
	 */
	public function shell_types(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => ContentRegistrar::SHELL_TAXONOMY,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		return array_map(
			static fn ( $term ): array => array(
				'slug'  => $term->slug,
				'name'  => TextNormalizer::plain( $term->name ),
				'count' => (int) $term->count,
			),
			$terms
		);
	}

	/**
	 * @return array<int, int>
	 */
	public function years(): array {
		$posts = get_posts(
			array(
				'post_type'      => ContentRegistrar::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		$years = array();

		foreach ( $posts as $post_id ) {
			$year = $this->production_year( (int) $post_id );
			if ( $year ) {
				$years[] = $year;
			}
		}

		$years = array_values( array_unique( $years ) );
		rsort( $years, SORT_NUMERIC );

		return $years;
	}

	private function map_orderby( string $orderby ): string {
		return match ( $orderby ) {
			'title'    => 'title',
			'modified' => 'modified',
			default    => 'date',
		};
	}

	private function first_meta( int $post_id, array $keys ): string {
		foreach ( $keys as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
				return trim( (string) $value );
			}
		}

		return '';
	}

	private function production_year( int $post_id ): ?int {
		$value = $this->first_meta( $post_id, array( 'produktionsaar', 'arstal' ) );

		if ( preg_match( '/^(19|20)\d{2}/', $value, $matches ) ) {
			return (int) $matches[0];
		}

		return null;
	}

	private function production_date( int $post_id ): ?string {
		$value = $this->first_meta( $post_id, array( 'produktionsdato' ) );

		if ( preg_match( '/^(\d{4})-?(\d{2})-?(\d{2})$/', $value, $parts ) ) {
			return sprintf( '%s-%s-%s', $parts[1], $parts[2], $parts[3] );
		}

		return null;
	}

	/**
	 * @return array{slug: string, name: string}|null
	 */
	private function shell_type( int $post_id ): ?array {
		$terms = get_the_terms( $post_id, ContentRegistrar::SHELL_TAXONOMY );

		if ( is_array( $terms ) && $terms ) {
			return array(
				'slug' => $terms[0]->slug,
				'name' => TextNormalizer::plain( $terms[0]->name ),
			);
		}

		$legacy = $this->first_meta( $post_id, array( 'skald' ) );
		if ( '' === $legacy ) {
			return null;
		}

		return array(
			'slug' => sanitize_title( $legacy ),
			'name' => TextNormalizer::plain( $legacy ),
		);
	}

	/**
	 * @return array{title: string, description: string, image: string|null, canonical: string, noindex: bool, nofollow: bool, source: string}
	 */
	private function seo( WP_Post $post, ?string $fallback_image ): array {
		return SeoMetadata::for_post( $post, $fallback_image );
	}
}
