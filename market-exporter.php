<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines the plugin class.
 *
 * @link              https://wooya.ru
 * @since             2.0.0
 * @package           Wooya
 *
 * @wordpress-plugin
 * Plugin Name:       Market Exporter
 * Plugin URI:        https://wooya.ru
 * Description:       Market Exporter integration suite.
 * Version:           2.0.6-beta.2
 * Author:            Anton Vanyukov
 * Author URI:        https://vanyukov.su/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       market-exporter
 * Domain Path:       /languages
 */

namespace Wooya;

use Exception;
use Wooya\Includes\Core;
use Wooya\Includes\Freemius;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'WOOYA_VERSION', '2.0.6-beta.2' );
define( 'WOOYA_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOOYA_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOYA_BASENAME', plugin_basename( __FILE__ ) );

/* @noinspection PhpIncludeInspection */
require_once WOOYA_PATH . 'includes/class-freemius.php';
new Freemius();

/**
 * The main plugin class.
 *
 * @since      2.0.0
 * @package    Wooya
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class App {

	/**
	 * Plugin instance.
	 *
	 * @since  2.0.0
	 * @access private
	 * @var    App|null $instance
	 */
	private static $instance = null;

	/**
	 * Core instance.
	 *
	 * @since 2.0.0
	 * @var Core $core
	 */
	public $core;

	/**
	 * Get plugin instance.
	 *
	 * @since  2.0.0
	 * @return App;
	 * @throws Exception  Autoload exception.
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * App constructor.
	 *
	 * @since 1.9.0
	 * @throws Exception  Autoload exception.
	 */
	private function __construct() {

		$this->includes();
		Includes\Activator::activate();
		$this->core = new Core();

	}

	/**
	 * Load required plugin files.
	 *
	 * @since 2.0.0
	 */
	private function includes() {

		/**
		 * The core plugin class that is used to define internationalization,
		 * admin-specific hooks, and public-facing site hooks.
		 *
		 * @noinspection PhpIncludeInspection
		 */
		require WOOYA_PATH . 'includes/class-core.php';

		/* @noinspection PhpIncludeInspection */
		require_once WOOYA_PATH . 'includes/class-activator.php';

	}

}

register_deactivation_hook( __FILE__, [ 'Wooya\Includes\Activator', 'deactivate' ] );

add_action( 'plugins_loaded', [ 'Wooya\App', 'get_instance' ] );
