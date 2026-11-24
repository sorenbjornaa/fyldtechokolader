<?php
/**
 * Dependency-free smoke tests for pure domain code.
 */

declare(strict_types=1);

use Fyldtechokolader\Core\Migration\LegacySubmissionParser;
use Fyldtechokolader\Core\Migration\LegacyContentMapper;
use Fyldtechokolader\Core\Ratings\Score;
use Fyldtechokolader\Core\Rest\ChocolateController;
use Fyldtechokolader\Core\Content\TextNormalizer;
use Fyldtechokolader\Core\Security\RequestAuthenticator;

class WP_Error {
	public function __construct(
		public string $code,
		public string $message,
		public array $data = array()
	) {}
}

class WP_REST_Request {
	public function __construct(
		private array $headers,
		private string $method,
		private string $route,
		private string $body
	) {}

	public function get_header( string $name ): string {
		return (string) ( $this->headers[ strtolower( $name ) ] ?? '' );
	}

	public function get_method(): string {
		return $this->method;
	}

	public function get_route(): string {
		return $this->route;
	}

	public function get_body(): string {
		return $this->body;
	}
}

$root = dirname( __DIR__ );
define( 'ABSPATH', $root . '/' );
require_once $root . '/src/Ratings/Score.php';
require_once $root . '/src/Migration/LegacySubmissionParser.php';
require_once $root . '/src/Migration/LegacyContentMapper.php';
require_once $root . '/src/Rest/ChocolateController.php';
require_once $root . '/src/Content/TextNormalizer.php';
require_once $root . '/src/Security/RequestAuthenticator.php';

$sanitize_title_argument_count = null;

function sanitize_title( mixed $value ): string {
	global $sanitize_title_argument_count;
	$sanitize_title_argument_count = func_num_args();

	return strtolower( trim( (string) $value ) );
}

$test_options = array();

function current_user_can( string $capability ): bool {
	return false;
}

function get_option( string $name, mixed $default = false ): mixed {
	global $test_options;

	return $test_options[ $name ] ?? $default;
}

$failures = array();

$assert_same = static function ( mixed $expected, mixed $actual, string $message ) use ( &$failures ): void {
	if ( $expected !== $actual ) {
		$failures[] = sprintf( '%s: forventede %s, fik %s', $message, var_export( $expected, true ), var_export( $actual, true ) );
	}
};

$assert_same( 1, Score::from_mixed( 1 ), 'Score accepterer minimum' );
$assert_same( 7, Score::from_mixed( '7' ), 'Score accepterer numerisk streng' );

foreach ( array( 0, 8, 4.5, '4.5', 'abc' ) as $invalid_score ) {
	$thrown = false;
	try {
		Score::from_mixed( $invalid_score );
	} catch ( InvalidArgumentException ) {
		$thrown = true;
	}
	$assert_same( true, $thrown, 'Score afviser ugyldig værdi' );
}

$parser = new LegacySubmissionParser();
$parsed = $parser->parse(
	array(
		array(
			'key'   => 'rating',
			'value' => '6',
		),
		array(
			'key'   => 'post_id',
			'value' => '467',
		),
	)
);
$assert_same( 'candidate', $parsed['status'], 'Parser finder sikker kandidat' );
$assert_same( 6, $parsed['score'], 'Parser finder score' );
$assert_same( 467, $parsed['chocolate_id'], 'Parser finder post-ID' );

$parsed_typo = $parser->parse(
	array(
		array(
			'key'   => 'rading',
			'value' => '5',
		),
		array(
			'key'   => '812',
			'value' => '812',
		),
	)
);
$assert_same( 'candidate', $parsed_typo['status'], 'Parser håndterer historiske feltnøgler' );

$missing = $parser->parse(
	array(
		array(
			'key'   => 'rating',
			'value' => '4',
		),
	)
);
$assert_same( 'missing_chocolate', $missing['status'], 'Parser isolerer ratings uden chokolade-ID' );

$controller = ( new ReflectionClass( ChocolateController::class ) )->newInstanceWithoutConstructor();
$method     = new ReflectionMethod( ChocolateController::class, 'collection_args' );
$arguments  = $method->invoke( $controller );
$slug       = $arguments['skaltype']['sanitize_callback']( '', new stdClass(), 'skaltype' );
$assert_same( '', $slug, 'Tomt skaltypefilter kan saniteres' );
$assert_same( 1, $sanitize_title_argument_count, 'WordPress-requesten sendes ikke som fallback til sanitize_title' );
$assert_same( 'Safran & Kardemomme', TextNormalizer::plain( 'Safran &amp; Kardemomme' ), 'HTML-entiteter normaliseres' );
$assert_same( 'Karamel – citron', TextNormalizer::plain( '<strong>Karamel</strong> &#8211; citron' ), 'HTML-tags fjernes fra API-tekst' );
$assert_same( 'Miso', TextNormalizer::plain( '&lt;em&gt;Miso&lt;/em&gt;' ), 'Kodede HTML-tags fjernes fra API-tekst' );

$content_mapper = new LegacyContentMapper();
$pistachio      = $content_mapper->map( 56, '20231216', 'Hvid Chokolade' );
$assert_same( 2023, $pistachio['production_year'], 'Produktionsår udledes fra legacy-dato' );
$assert_same( '2023-12-16', $pistachio['production_date'], 'Produktionsdato normaliseres' );
$assert_same( 'moerk', $pistachio['shell_slug'], 'Pistacie følger den godkendte mørke skal' );

$miso = $content_mapper->map( 109, '', 'Mørk chokolade' );
$assert_same( 2023, $miso['production_year'], 'Miso får det verificerede produktionsår' );
$assert_same( null, $miso['production_date'], 'Miso får ikke en opdigtet produktionsdato' );

$orange = $content_mapper->map( 111, '20231217', 'Kokusmælke chokolade' );
$assert_same( 'lys', $orange['shell_slug'], 'Kokosmælkechokolade mappes til lys efter beslutning' );

$caramel = $content_mapper->map( 475, '20251213', 'Karamel' );
$assert_same( 'uden-skal', $caramel['shell_slug'], 'Karamel uden skal får sin egen kategori' );

$saba = $content_mapper->map( 458, '', 'Lys Chokolade' );
$assert_same( 2025, $saba['production_year'], 'Saba og Balsamico får det verificerede produktionsår' );
$assert_same( null, $saba['production_date'], 'Saba og Balsamico får ikke en opdigtet produktionsdato' );

$shell_variants = array(
	'Hvid Chokolade'   => 'hvid',
	'hvid ckokolade'   => 'hvid',
	'Mørk Chokolade'   => 'moerk',
	'Mælke chokolade'  => 'lys',
	'Lys Chokolade'    => 'lys',
	'Dulce chokolade'  => 'dulcey',
);

foreach ( $shell_variants as $legacy_shell => $expected_slug ) {
	$mapped_shell = $content_mapper->map( 999, '20251213', $legacy_shell );
	$assert_same( $expected_slug, $mapped_shell['shell_slug'], 'Legacy-skaltypen normaliseres: ' . $legacy_shell );
}

$authenticator = new RequestAuthenticator();
$missing_auth  = $authenticator->authorize( new WP_REST_Request( array(), 'POST', '/fyldtechokolader/v1/chokolader/miso/rating', '{}' ) );
$assert_same( true, $missing_auth instanceof WP_Error, 'Manglende integrationshemmelighed afvises' );

$secret                                      = str_repeat( 'a', 64 );
$test_options['fyldtechokolader_api_secret'] = $secret;
$timestamp                                   = (string) time();
$route                                       = '/fyldtechokolader/v1/chokolader/miso/rating';
$body                                        = '{"score":4,"voterToken":"test-browser-token"}';
$canonical                                   = implode( "\n", array( $timestamp, 'POST', $route, hash( 'sha256', $body ) ) );
$signature                                   = hash_hmac( 'sha256', $canonical, $secret );
$request                                     = new WP_REST_Request(
	array(
		'x-fyldtechokolader-timestamp' => $timestamp,
		'x-fyldtechokolader-signature' => $signature,
	),
	'POST',
	$route,
	$body
);
$assert_same( true, $authenticator->authorize( $request ), 'HMAC kan bruge hemmeligheden fra WordPress-optionen' );

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Alle domænetests bestod.\n";
