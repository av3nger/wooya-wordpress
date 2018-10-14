<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wooya.ru
 * @since      1.0.0
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 */

namespace Wooya\Includes;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wooya
 * @subpackage Wooya/Includes
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class Core {

	/**
	 * The admin class that holds all plugin functionality.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Admin     $admin  Maintains and registers all admin functionality.
	 */
	protected $admin;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $plugin_name  The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since  1.0.0
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
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( defined( 'WOOYA_VERSION' ) ) {
			$this->version = WOOYA_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wooya';

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->load_dependencies();
		$this->define_admin_hooks();

	}

	/**
	 * Autoloader.
	 *
	 * @since 1.0.0
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
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {
		$this->admin = new Admin( $this->get_plugin_name(), $this->get_version() );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {

		// Define the locale for this plugin for internationalization.
		add_action( 'admin_init', array( $this, 'load_plugin_textdomain' ) );
		// Add admin menu.
		add_action( 'admin_menu', array( $this->admin, 'register_menu' ) );
		// Styles and scripts.
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_scripts' ) );

		// Add REST API endpoints.
		add_action( 'rest_api_init', array( RestAPI::get_instance(), 'register_routes' ) );

	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since  1.0.0
	 * @return string  The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the admin functionality of the plugin.
	 *
	 * @since  1.0.0
	 * @return Admin  Orchestrates the admin functionality of the plugin.
	 */
	public function get_admin() {
		return $this->admin;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  1.0.0
	 * @return string  The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wooya',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages'
		);

	}

}
