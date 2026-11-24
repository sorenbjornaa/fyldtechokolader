<?php
/**
 * Dependency-free smoke tests for pure domain code.
 */

declare(strict_types=1);

use Fyldtechokolader\Core\Migration\LegacySubmissionParser;
use Fyldtechokolader\Core\Ratings\Score;
use Fyldtechokolader\Core\Rest\ChocolateController;
use Fyldtechokolader\Core\Content\TextNormalizer;

$root = dirname( __DIR__ );
define( 'ABSPATH', $root . '/' );
require_once $root . '/src/Ratings/Score.php';
require_once $root . '/src/Migration/LegacySubmissionParser.php';
require_once $root . '/src/Rest/ChocolateController.php';
require_once $root . '/src/Content/TextNormalizer.php';

$sanitize_title_argument_count = null;

function sanitize_title( mixed $value ): string {
	global $sanitize_title_argument_count;
	$sanitize_title_argument_count = func_num_args();

	return strtolower( trim( (string) $value ) );
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

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Alle domænetests bestod.\n";
