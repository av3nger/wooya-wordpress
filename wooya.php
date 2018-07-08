<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines the plugin class.
 *
 * @link              https://wooya.ru
 * @since             1.0.0
 * @package           Wooya
 *
 * @wordpress-plugin
 * Plugin Name:       WooYa
 * Plugin URI:        https://wooya.ru
 * Description:       WooYa integration suite.
 * Version:           1.0.0
 * Author:            vCore
 * Author URI:        https://vcore.ru/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wooya
 * Domain Path:       /languages
 */

namespace Wooya;

use Wooya\Includes\Core;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'WOOYA_VERSION', '1.0.0' );

/**
 * The main plugin class.
 *
 * @since      1.0.0
 * @package    Wooya
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class App {

	/**
	 * Plugin instance.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    App|null $instance
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @since  1.0.0
	 * @return App;
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

	/**
	 * App constructor.
	 *
	 * @since 1.9.0
	 */
	private function __construct() {

		$this->includes();
		new Core();

	}

	/**
	 * Load required plugin files.
	 *
	 * @since 1.0.0
	 */
	private function includes() {

		/**
		 * The core plugin class that is used to define internationalization,
		 * admin-specific hooks, and public-facing site hooks.
		 *
		 * @noinspection PhpIncludeInspection
		 */
		require plugin_dir_path( __FILE__ ) . 'includes/class-core.php';

		/* @noinspection PhpIncludeInspection */
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-activator.php';

	}

}

register_activation_hook( 'includes/class-activator.php', array( 'Wooya\Includes\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Wooya\Includes\Activator', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Wooya\App', 'get_instance' ) );
