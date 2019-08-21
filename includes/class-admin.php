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
		if ( 'toplevel_page_market-exporter' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name,
			WOOYA_URL . 'admin/css/market-exporter.min.css',
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
		if ( 'toplevel_page_market-exporter' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-i18n',
			WOOYA_URL . 'admin/js/market-exporter-i18n.min.js',
			[],
			$this->version,
			true
		);

		wp_enqueue_script(
			$this->plugin_name,
			WOOYA_URL . 'admin/js/market-exporter.min.js',
			[ 'jquery', $this->plugin_name . '-i18n' ],
			$this->version,
			true
		);

		$rest_api_active = ! has_filter( 'rest_enabled', '__return_false' ) && has_action( 'init', 'rest_api_init' );
		wp_localize_script(
			$this->plugin_name,
			'ajax_strings',
			[
				'rest_api'  => apply_filters( 'me_use_rest_endpoints', $rest_api_active ),
				'api_nonce' => wp_create_nonce( 'wp_rest' ),
				'api_url'   => rest_url( $this->plugin_name . '/v1/' ),
				'errors'    => [
					'error_500' => __( 'Error generating file', 'market-exporter' ),
					'error_501' => __(
						'Currently only the following currency is supported: Russian Ruble (RUB), Ukrainian Hryvnia(UAH), Tenge (KZT), US Dollar (USD) and Euro (EUR).',
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
			'wooyaI18n.setLocaleData( ' . wp_json_encode( $this->get_locale_data( 'market-exporter' ) ) . ', "market-exporter" );',
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
			[ $this, 'render_page' ],
			$this->render_icon()
		);

	}

	/**
	 * Render menu icon.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	private function render_icon() {
		ob_start();
		?>
		<svg id="svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="400" height="400" viewBox="0, 0, 400,400">
			<g id="svgg">
				<path id="path0" d="M287.045 20.175 C 286.218 44.941,286.305 47.883,288.831 80.702 C 290.739 105.500,290.083 104.941,285.737 78.070 C 282.927 60.697,277.976 64.437,275.287 85.965 C 273.961 96.579,271.585 105.263,270.008 105.263 C 268.430 105.263,266.056 108.816,264.733 113.158 C 261.517 123.706,224.806 197.306,220.541 201.754 C 218.691 203.684,219.629 199.393,222.624 192.218 C 240.573 149.225,170.267 81.419,91.241 65.505 C 61.219 59.459,57.001 60.666,71.936 71.028 C 111.874 98.737,151.175 158.567,165.035 212.759 C 174.123 248.295,174.520 246.701,154.299 255.840 C 110.681 275.553,61.726 319.139,46.269 352.021 C 38.412 368.736,35.699 400.000,42.105 400.000 C 44.035 400.000,45.614 396.957,45.614 393.237 C 45.614 373.778,82.602 319.337,111.636 296.062 C 126.436 284.197,125.977 284.971,107.018 303.842 C 89.313 321.464,62.222 361.404,67.973 361.404 C 68.933 361.404,78.110 353.079,88.368 342.905 C 133.011 298.623,239.861 263.211,328.947 263.172 C 360.227 263.158,370.439 259.270,354.623 253.396 C 350.893 252.011,344.130 245.351,339.592 238.596 C 328.825 222.568,311.232 217.561,294.685 225.816 C 279.120 233.580,279.175 233.679,289.363 216.295 C 323.152 158.639,328.739 77.901,302.920 20.371 C 291.425 -5.245,287.896 -5.289,287.045 20.175 " stroke="none" fill="#000000" fill-rule="evenodd"></path>
			</g>
		</svg>
		<?php
		$svg = ob_get_clean();

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
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
