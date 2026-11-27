<?php
/**
 * Plugin Name:       Fyldtechokolader Core
 * Plugin URI:        https://github.com/sorenbjornaa/fyldtechokolader
 * Description:       Datamodel, ratings, legacy-import og REST API til Fyldtechokolader.
 * Version:           0.1.7
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Author:            Soren Bjornaa
 * License:           GPL-2.0-or-later
 * Text Domain:       fyldtechokolader-core
 *
 * @package FyldtechokoladerCore
 */

defined( 'ABSPATH' ) || exit;

define( 'FYLDTECHOKOLADER_CORE_VERSION', '0.1.7' );
define( 'FYLDTECHOKOLADER_CORE_FILE', __FILE__ );
define( 'FYLDTECHOKOLADER_CORE_PATH', plugin_dir_path( __FILE__ ) );

require_once FYLDTECHOKOLADER_CORE_PATH . 'src/Autoloader.php';

Fyldtechokolader\Core\Autoloader::register();

register_activation_hook(
	__FILE__,
	static function (): void {
		$registrar = new Fyldtechokolader\Core\Content\ContentRegistrar();
		$registrar->register();

		$installer = new Fyldtechokolader\Core\Infrastructure\Installer();
		$installer->activate();
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		$plugin = new Fyldtechokolader\Core\Plugin();
		$plugin->boot();
	}
);
