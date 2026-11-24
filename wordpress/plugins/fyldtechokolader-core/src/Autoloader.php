<?php
/**
 * Minimal PSR-4-style autoloader for the plugin.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core;

defined( 'ABSPATH' ) || exit;

final class Autoloader {
	private const PREFIX = 'Fyldtechokolader\\Core\\';

	public static function register(): void {
		spl_autoload_register( array( self::class, 'autoload' ) );
	}

	private static function autoload( string $class_name ): void {
		if ( ! str_starts_with( $class_name, self::PREFIX ) ) {
			return;
		}

		$relative_class = substr( $class_name, strlen( self::PREFIX ) );
		$file           = FYLDTECHOKOLADER_CORE_PATH . 'src/' . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
