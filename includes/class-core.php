<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wooya.ru
 * @since      2.0.0
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 */

namespace Wooya\Includes;

use Exception;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      2.0.0
 * @package    Wooya
 * @subpackage Wooya/Includes
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class Core {

	/**
	 * The admin class that holds all plugin functionality.
	 *
	 * @since  2.0.0
	 * @access protected
	 * @var    Admin     $admin  Maintains and registers all admin functionality.
	 */
	protected $admin;

	/**
	 * REST API and AJAX interface.
	 *
	 * @since 2.0.6
	 * @access protected
	 * @var    RestAPI    $api  REST API interface.
	 */
	protected $api;

	/**
	 * Generator engine.
	 *
	 * @since  2.0.6
	 * @access protected
	 * @var    Generator  $generator  Generates the YML file.
	 */
	protected $generator;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since  2.0.0
	 * @access protected
	 * @var    string    $plugin_name  The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since  2.0.0
	 * @access protected
	 * @var    string    $version  The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 2.0.0
	 * @throws Exception  Exception from autoload.
	 */
	public function __construct() {

		if ( defined( 'WOOYA_VERSION' ) ) {
			$this->version = WOOYA_VERSION;
		} else {
			$this->version = '2.0.0';
		}
		$this->plugin_name = 'market-exporter';

		// Check if plugin has WooCommerce installed and active.
		add_action( 'admin_init', [ $this, 'run_plugin' ] );
		if ( ! self::check_prerequisites() ) {
			add_action( 'admin_notices', [ $this, 'plugin_activation_message' ] );
			return;
		}

		$notice = get_option( 'market_exporter_notice_hide' );
		if ( 'true' !== $notice ) {
			add_action( 'admin_notices', [ $this, 'plugin_rate_message' ] );
		}

		spl_autoload_register( [ $this, 'autoload' ] );

		$this->load_dependencies();
		$this->define_admin_hooks();

	}

	/**
	 * Autoloader.
	 *
	 * @since 2.0.0
	 *
	 * @param string $class_name  Class name to autoload.
	 */
	public function autoload( $class_name ) {

		// Parse only Wooya dependencies.
		if ( 0 !== strpos( $class_name, 'Wooya' ) ) {
			return;
		}

		// Support for underscore in class names.
		$class_name = str_replace( '_', '-', $class_name );

		$class_parts = explode( '\\', $class_name );

		if ( ! $class_parts ) {
			return;
		}

		// Remove the Wooya part.
		array_shift( $class_parts );

		// Convert all to lower case.
		$class_parts = array_map( 'strtolower', $class_parts );

		// Prepend class- to last element.
		$index                 = count( $class_parts ) - 1;
		$class_parts[ $index ] = 'class-' . $class_parts[ $index ];

		// Build path.
		$filename = implode( '/', $class_parts );
		$file     = WOOYA_PATH . "{$filename}.php";

		if ( is_readable( $file ) ) {
			/* @noinspection PhpIncludeInspection */
			require_once $file;
		}

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - \Wooya\Includes\Admin. Defines all hooks for the admin area.
	 *
	 * @since  2.0.0
	 * @access private
	 */
	public function load_dependencies() {

		$this->admin = new Admin( $this->get_plugin_name(), $this->get_version() );

		$this->generator = Generator::get_instance();
		$this->generator->init();

		$this->api = new RestAPI( $this->generator );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since  2.0.0
	 * @access private
	 */
	private function define_admin_hooks() {

		// Define the locale for this plugin for internationalization.
		add_action( 'admin_init', [ $this, 'load_plugin_textdomain' ] );

		// Add admin menu.
		add_action( 'admin_menu', [ $this->get_admin(), 'register_menu' ] );
		// Add Settings link to plugin in plugins list.
		add_filter( 'plugin_action_links_' . WOOYA_BASENAME, [ $this->get_admin(), 'plugin_add_settings_link' ] );

		// Styles and scripts.
		add_action( 'admin_enqueue_scripts', [ $this->get_admin(), 'enqueue_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this->get_admin(), 'enqueue_scripts' ] );

		// Add REST API endpoints.
		add_action( 'rest_api_init', [ $this->api, 'register_routes' ] );

		// Add ajax support to dismiss notice.
		add_action( 'wp_ajax_dismiss_rate_notice', [ $this, 'dismiss_notice' ] );

		// Add cron support.
		add_action( 'market_exporter_cron', [ $this->generator, 'cron_generate_yml' ] );
		// Add support to update file on product update.
		add_action( 'woocommerce_update_product', [ $this->get_admin(), 'generate_file_on_update' ] );

	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since  2.0.0
	 * @return string  The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the admin functionality of the plugin.
	 *
	 * @since  2.0.0
	 * @return Admin  Orchestrates the admin functionality of the plugin.
	 */
	public function get_admin() {
		return $this->admin;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  2.0.0
	 * @return string  The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 2.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'market-exporter',
			false,
			dirname( dirname( WOOYA_BASENAME ) ) . '/languages'
		);

	}

	/**
	 * Is WooCommerce installed? Is it active? If not - don't activate the plugin.
	 *
	 * Checks the availability of WooCommerce. If not WooCommerce not available - we disable the Market Exporter.
	 * First we get a list of activated plugins. If our plugin is there - we suppress the "Activation successful" message.
	 * And deactivate the plugin. The error message is registered in define_admin_hooks().
	 *
	 * @since 0.0.1
	 */
	public function run_plugin() {

		if ( ! self::check_prerequisites() ) {
			$plugins = get_option( 'active_plugins' );

			if ( in_array( WOOYA_BASENAME, $plugins, true ) ) {
				unset( $_GET['activate'] ); // Input var ok.
				deactivate_plugins( WOOYA_PATH . 'market-exporter.php' );
			}
		}

	}

	/**
	 * Checks if WooCommerce is installed and active.
	 *
	 * Check if get_plugins() function exists. Needed for checks during __construct.
	 * Check if WooCommerce is installed using get_plugins().
	 * Check if WooCommerce is active using is_plugin_active().
	 *
	 * @since  0.0.1
	 * @return bool
	 */
	public static function check_prerequisites() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$woo_installed = get_plugins( '/woocommerce' );
		if ( empty( $woo_installed ) || ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Message to display if we did not find WooCommerce.
	 *
	 * @since 0.0.1
	 */
	public function plugin_activation_message() {

		?>
		<div class="error notice">
			<p>
				<?php
				printf(
					/* translators: %1$s: opening a tag, %2%s: closing a tag */
					esc_html__( 'The Market Exporter plugin requires %1$sWooCommerce%2$s to be installed and activated. Please check your configuration.', 'market-exporter' ),
					'<a href="https://wordpress.org/plugins/woocommerce/">',
					'</a>'
				);
				?>
			</p>
		</div>
		<?php

	}

	/**
	 * Rate the plugin message.
	 *
	 * @since 0.4.4
	 */
	public function plugin_rate_message() {

		if ( 'toplevel_page_wooya' !== get_current_screen()->id ) {
			return;
		}
		?>
		<div class="notice notice-success is-dismissible" id="rate-notice">
			<p>
				<?php
				printf(
					/* translators: %1$s: opening a tag, %2$s: closing a tag */
					esc_html__( 'Do you like the plugin? Please support the development by %1$swriting a review%2$s!', 'market-exporter' ),
					'<a href="https://wordpress.org/plugins/market-exporter/">',
					'</a>'
				);
				?>
			</p>
		</div>
		<?php

	}

	/**
	 * Dismiss notice to rate the plugin.
	 *
	 * @since 0.4.4
	 */
	public function dismiss_notice() {

		check_ajax_referer( 'wp_rest' );
		// If user clicks to ignore the notice, add that to their user meta.
		update_option( 'market_exporter_notice_hide', 'true' );
		wp_die(); // All ajax handlers die when finished.

	}

}
