<?php
/**
 * PHPUnit bootstrap file
 *
 * @since 2.0.6
 * @package Market_Exporter
 */

namespace Wooya\Tests;

use WC_Install;
use Wooya\App;

/**
 * Class ME_Unit_Test_Bootstrap
 */
class ME_Unit_Test_Bootstrap {

	/**
	 * Class instance.
	 *
	 * @var ME_Unit_Test_Bootstrap instance
	 */
	protected static $instance = null;

	/**
	 * Directory where wordpress-tests-lib is installed.
	 *
	 * @var string $wp_tests_dir
	 */
	public $wp_tests_dir;

	/**
	 * Testing directory.
	 *
	 * @var string $tests_dir
	 */
	public $tests_dir;

	/**
	 * Plugin directory.
	 *
	 * @var string $plugin_dir
	 */
	public $plugin_dir;

	/**
	 * Get the single class instance.
	 *
	 * @since 2.0.6
	 * @return ME_Unit_Test_Bootstrap
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * ME_Unit_Test_Bootstrap constructor.
	 *
	 * @since 2.0.6
	 */
	private function __construct() {

		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( $this->tests_dir );
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' );

		if ( ! $this->wp_tests_dir ) {
			$this->wp_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
		}

		if ( ! file_exists( $this->wp_tests_dir . '/includes/functions.php' ) ) {
			echo "Could not find $this->wp_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
			exit( 1 );
		}

		// Give access to tests_add_filter() function.
		/* @noinspection PhpIncludeInspection */
		require_once $this->wp_tests_dir . '/includes/functions.php';

		// Load WC.
		tests_add_filter( 'muplugins_loaded', array( $this, 'load_plugins' ) );

		// Install WC.
		tests_add_filter( 'setup_theme', array( $this, 'install_wc' ) );

		// Load the WP testing environment.
		/* @noinspection PhpIncludeInspection */
		require_once $this->wp_tests_dir . '/includes/bootstrap.php';

		// Load WC testing framework.
		$this->includes();

		spl_autoload_register( [ App::get_instance()->core, 'autoload' ] );

	}

	/**
	 * Load WooCommerce.
	 *
	 * @since 2.0.6
	 */
	public function load_plugins() {

		define( 'WC_TAX_ROUNDING_MODE', 'auto' );
		define( 'WC_USE_TRANSACTIONS', false );

		/* @noinspection PhpIncludeInspection */
		require_once dirname( $this->plugin_dir ) . '/woocommerce/woocommerce.php';
		/* @noinspection PhpIncludeInspection */
		require_once dirname( $this->plugin_dir ) . '/market-exporter/market-exporter.php';

	}

	/**
	 * Install WooCommerce after the test environment and WC have been loaded.
	 *
	 * @since 2.0.6
	 */
	public function install_wc() {

		// Clean existing install first.
		define( 'WP_UNINSTALL_PLUGIN', true );
		define( 'WC_REMOVE_ALL_DATA', true );

		/* @noinspection PhpIncludeInspection */
		include dirname( $this->plugin_dir ) . '/woocommerce/uninstall.php';

		WC_Install::install();

		// Reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374.
		if ( version_compare( $GLOBALS['wp_version'], '4.7', '<' ) ) {
			$GLOBALS['wp_roles']->reinit();
		} else {
			$GLOBALS['wp_roles'] = null; // WPCS: override ok.
			wp_roles();
		}

		echo esc_html( 'Installing WooCommerce...' . PHP_EOL );

	}

	/**
	 * Load WC-specific test cases and factories.
	 *
	 * @since 2.0.6
	 */
	public function includes() {

		/* @noinspection PhpIncludeInspection */
		require_once $this->tests_dir . '/helpers/class-wc-helper-product.php';

	}

}

ME_Unit_Test_Bootstrap::instance();
