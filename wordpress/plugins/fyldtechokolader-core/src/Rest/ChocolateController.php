<?php
/**
 * Public chocolate REST endpoints.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Rest;

use Fyldtechokolader\Core\Content\ChocolateRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

final class ChocolateController {
	public const NAMESPACE = 'fyldtechokolader/v1';

	private ChocolateRepository $chocolates;

	public function __construct( ChocolateRepository $chocolates ) {
		$this->chocolates = $chocolates;
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/chokolader',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'index' ),
				'permission_callback' => '__return_true',
				'args'                => $this->collection_args(),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/chokolader/(?P<slug>[a-z0-9-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'show' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'slug' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_title',
					),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/skaltype',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => fn (): array => $this->chocolates->shell_types(),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/aar',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => fn (): array => $this->chocolates->years(),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function index( WP_REST_Request $request ): WP_REST_Response {
		$result   = $this->chocolates->find_all(
			array(
				'page'      => $request->get_param( 'page' ),
				'per_page'  => $request->get_param( 'per_page' ),
				'search'    => $request->get_param( 'search' ),
				'year'      => $request->get_param( 'aar' ),
				'shellType' => $request->get_param( 'skaltype' ),
				'orderby'   => $request->get_param( 'orderby' ),
				'order'     => $request->get_param( 'order' ),
			)
		);
		$response = new WP_REST_Response( $result['items'], 200 );
		$response->header( 'X-WP-Total', (string) $result['total'] );
		$response->header( 'X-WP-TotalPages', (string) $result['pages'] );

		return $response;
	}

	public function show( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post = $this->chocolates->find_by_slug( (string) $request->get_param( 'slug' ) );

		if ( ! $post ) {
			return new WP_Error(
				'fyldtechokolader_not_found',
				'Chokoladen findes ikke.',
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response( $this->chocolates->map( $post ), 200 );
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function collection_args(): array {
		return array(
			'page'     => array(
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => static fn ( $value ): bool => (int) $value >= 1,
			),
			'per_page' => array(
				'default'           => 12,
				'sanitize_callback' => 'absint',
				'validate_callback' => static fn ( $value ): bool => (int) $value >= 1 && (int) $value <= 100,
			),
			'search'   => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'aar'      => array(
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'validate_callback' => static fn ( $value ): bool => 0 === (int) $value || ( (int) $value >= 1900 && (int) $value <= 2100 ),
			),
			'skaltype' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_title',
			),
			'orderby'  => array(
				'default'           => 'date',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => static fn ( $value ): bool => in_array( $value, array( 'date', 'title', 'modified', 'produktionsdato' ), true ),
			),
			'order'    => array(
				'default'           => 'DESC',
				'sanitize_callback' => static fn ( $value ): string => strtoupper( sanitize_key( $value ) ),
				'validate_callback' => static fn ( $value ): bool => in_array( strtoupper( (string) $value ), array( 'ASC', 'DESC' ), true ),
			),
		);
	}
}
