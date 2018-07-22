<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wooya.ru
 * @since      1.0.0
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 */

namespace Wooya\Includes;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string $plugin_name  The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string $version  The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name  The name of this plugin.
	 * @param string $version      The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook  Page from where it is called.
	 */
	public function enqueue_styles( $hook ) {

		// Run only on plugin pages.
		if ( 'toplevel_page_wooya' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name,
			WOOYA_URL . 'admin/css/wooya-app.min.css',
			array(),
			$this->version,
			'all'
		);

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook  Page from where it is called.
	 */
	public function enqueue_scripts( $hook ) {

		// Run only on plugin pages.
		if ( 'toplevel_page_wooya' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name,
			WOOYA_URL . 'admin/js/wooya-app.min.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script( $this->plugin_name, 'ajax_strings', array(
			'ajax_url'  => admin_url( 'admin-ajax.php' ),
		) );

	}

	/**
	 * Register menu point.
	 *
	 * @since 1.0.0
	 */
	public function register_menu() {

		add_menu_page(
			__( 'WooYa', 'wooya' ),
			__( 'WooYa', 'wooya' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'render_page' )
		);

	}

	/**
	 * Display plugins page.
	 *
	 * @since 1.0.0
	 */
	public function render_page() {

		?>
		<div class="wrap wooya-wrapper" id="wooya_pages">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="wooya-version">
				<?php /* translators: version number */
				printf( esc_html__( 'Version: %s', 'wooya' ), esc_html( $this->version ) ); ?>
			</div>

			<div id="wooya_components"></div>
		</div>
		<?php

	}

}
