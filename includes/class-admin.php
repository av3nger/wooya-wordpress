<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wooya.ru
 * @since      2.0.0
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
	 * @since  2.0.0
	 * @access private
	 * @var    string $plugin_name  The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since  2.0.0
	 * @access private
	 * @var    string $version  The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 2.0.0
	 * @param string $plugin_name  The name of this plugin.
	 * @param string $version      The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 2.0.0
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
			[],
			$this->version,
			'all'
		);

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 2.0.0
	 *
	 * @param string $hook  Page from where it is called.
	 */
	public function enqueue_scripts( $hook ) {

		// Run only on plugin pages.
		if ( 'toplevel_page_wooya' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-i18n',
			WOOYA_URL . 'admin/js/wooya-i18n.min.js',
			[],
			$this->version,
			true
		);

		wp_enqueue_script(
			$this->plugin_name,
			WOOYA_URL . 'admin/js/wooya-app.min.js',
			[ 'jquery', $this->plugin_name . '-i18n' ],
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name,
			'ajax_strings',
			[
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'api_nonce' => wp_create_nonce( 'wp_rest' ),
				'api_url'   => rest_url( $this->plugin_name . '/v1/' ),
				'errors'    => [
					'error_500' => __( 'Error generating file', 'market-exporter' ),
					'error_501' => __(
						'Currently only the following currency is supported: Russian Ruble (RUB), Ukrainian Hryvnia(UAH),
						Tenge (KZT), US Dollar (USD) and Euro (EUR).',
						'market-exporter'
					),
					'link_501'  => admin_url( 'admin.php?page=wc-settings' ),
					'error_502' => __( 'No shipping methods are available.', 'market-exporter' ),
					'link_502'  => admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
					'error_503' => __( 'Unable to find any products in WooCommerce.', 'market-exporter' ),
					'link_503'  => admin_url( 'post-new.php?post_type=product' ),
				],
			]
		);

		wp_add_inline_script(
			$this->plugin_name,
			'wooyaI18n.setLocaleData( ' . wp_json_encode( $this->get_locale_data( 'market-exporter' ) ) . ', "wooya" );',
			'before'
		);

	}

	/**
	 * Returns Jed-formatted localization data.
	 *
	 * @since 2.0.0
	 *
	 * @param  string $domain Translation domain.
	 *
	 * @return array
	 */
	private function get_locale_data( $domain ) {

		$translations = get_translations_for_domain( $domain );

		$locale = [
			'' => [
				'domain' => $domain,
				'lang'   => is_admin() ? get_user_locale() : get_locale(),
			],
		];

		if ( ! empty( $translations->headers['Plural-Forms'] ) ) {
			$locale['']['plural_forms'] = $translations->headers['Plural-Forms'];
		}

		foreach ( $translations->entries as $msgid => $entry ) {
			$locale[ $msgid ] = $entry->translations;
		}

		return $locale;

	}

	/**
	 * Register menu point.
	 *
	 * @since 2.0.0
	 */
	public function register_menu() {

		add_menu_page(
			__( 'Market Exporter', 'market-exporter' ),
			__( 'Market Exporter', 'market-exporter' ),
			'manage_options',
			$this->plugin_name,
			[ $this, 'render_page' ]
		);

	}

	/**
	 * Add Setings link to plugin in plugins list.
	 *
	 * @since  0.0.5
	 * @param  array $links Links for the current plugin.
	 * @return array $links New links array for the current plugin.
	 */
	public function plugin_add_settings_link( $links ) {

		$settings_link = '<a href="' . admin_url( 'admin.php?page=' . $this->plugin_name ) . '">' . __( 'Settings', 'market-exporter' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;

	}

	/**
	 * Generate file on update.
	 *
	 * @since 1.0.0
	 * @used-by \Wooya\Includes\Core::define_admin_hooks()
	 */
	public function generate_file_on_update() {

		$options = get_option( 'wooya_settings' );

		if ( ! isset( $options['misc']['update_on_change'] ) || ! $options['misc']['update_on_change'] ) {
			return;
		}

		$doing_cron = get_option( 'market_exporter_doing_cron' );

		// Already doing cron, exit.
		if ( isset( $doing_cron ) && $doing_cron ) {
			return;
		}

		update_option( 'market_exporter_doing_cron', true );
		wp_schedule_single_event( time(), 'market_exporter_cron' );

	}

	/**
	 * Display plugins page.
	 *
	 * @since 2.0.0
	 */
	public function render_page() {

		?>
		<div class="wrap wooya-wrapper" id="wooya_pages">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="wooya-description">
				<p>
					<?php esc_html_e( 'This plugin is used to generate a valid YML file for exporting your products in WooCommerce to Yandex Market.', 'market-exporter' ); ?>
				</p>

				<p>
					<?php esc_html_e( 'Please be patient while the YML file is generated. This can take a while if your server is slow or if you have many products in WooCommerce. Do not navigate away from this page until this script is done or the YML file will not be created. You will be notified via this page when the process is completed.', 'market-exporter' ); ?>
				</p>
			</div>

			<div class="wooya-version">
				<?php
				/* translators: version number */
				printf( esc_html__( 'Version: %s', 'market-exporter' ), esc_html( $this->version ) );
				?>
			</div>

			<div id="wooya_components"></div>
		</div>
		<?php

	}

}
