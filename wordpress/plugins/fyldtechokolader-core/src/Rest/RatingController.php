<?php
/**
 * Rating REST endpoints.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Rest;

use Fyldtechokolader\Core\Content\ContentRegistrar;
use Fyldtechokolader\Core\Infrastructure\RateLimiter;
use Fyldtechokolader\Core\Ratings\RatingService;
use Fyldtechokolader\Core\Security\RequestAuthenticator;
use InvalidArgumentException;
use RuntimeException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

final class RatingController {
	private RatingService $ratings;
	private RequestAuthenticator $authenticator;
	private RateLimiter $rate_limiter;

	public function __construct(
		RatingService $ratings,
		RequestAuthenticator $authenticator,
		RateLimiter $rate_limiter
	) {
		$this->ratings       = $ratings;
		$this->authenticator = $authenticator;
		$this->rate_limiter  = $rate_limiter;
	}

	public function register_routes(): void {
		register_rest_route(
			ChocolateController::NAMESPACE,
			'/chokolader/(?P<slug>[a-z0-9-]+)/rating',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this->authenticator, 'authorize' ),
					'args'                => array(
						'score'      => array(
							'required' => true,
							'type'     => 'integer',
							'minimum'  => 1,
							'maximum'  => 7,
						),
						'voterToken' => array(
							'required'          => true,
							'type'              => 'string',
							'minLength'         => 16,
							'maxLength'         => 255,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	public function show( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$chocolate = $this->find_chocolate( (string) $request->get_param( 'slug' ) );
		if ( is_wp_error( $chocolate ) ) {
			return $chocolate;
		}

		try {
			return new WP_REST_Response( $this->ratings->aggregate( $chocolate->ID ), 200 );
		} catch ( InvalidArgumentException $exception ) {
			return $this->domain_error( $exception );
		}
	}

	public function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$chocolate = $this->find_chocolate( (string) $request->get_param( 'slug' ) );
		if ( is_wp_error( $chocolate ) ) {
			return $chocolate;
		}

		try {
			$voter_token = (string) $request->get_param( 'voterToken' );
			$voter_hash  = $this->ratings->hash_voter_token( $voter_token );

			if ( ! $this->rate_limiter->consume( $chocolate->ID, $voter_hash ) ) {
				return new WP_Error(
					'fyldtechokolader_rate_limited',
					'Der er sendt for mange ratings. Prøv igen senere.',
					array( 'status' => 429 )
				);
			}

			$result = $this->ratings->rate(
				$chocolate->ID,
				$request->get_param( 'score' ),
				$voter_token
			);

			return new WP_REST_Response( $result, $result['created'] ? 201 : 200 );
		} catch ( InvalidArgumentException $exception ) {
			return $this->domain_error( $exception );
		} catch ( RuntimeException $exception ) {
			return new WP_Error(
				'fyldtechokolader_rating_failed',
				$exception->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	private function find_chocolate( string $slug ): \WP_Post|WP_Error {
		$posts = get_posts(
			array(
				'name'           => sanitize_title( $slug ),
				'post_type'      => ContentRegistrar::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);

		if ( ! $posts ) {
			return new WP_Error(
				'fyldtechokolader_not_found',
				'Chokoladen findes ikke.',
				array( 'status' => 404 )
			);
		}

		return $posts[0];
	}

	private function domain_error( InvalidArgumentException $exception ): WP_Error {
		return new WP_Error(
			'fyldtechokolader_invalid_rating',
			$exception->getMessage(),
			array( 'status' => 400 )
		);
	}
}
