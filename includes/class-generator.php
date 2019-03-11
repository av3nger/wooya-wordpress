<?php
/**
 * YML generator.
 *
 * @link       https://wooya.ru
 * @since      2.0.0
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 */

namespace Wooya\Includes;

/**
 * Generate the YML file.
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class Generator {

	/**
	 * Generator instance.
	 *
	 * @since  2.0.0
	 * @access private
	 * @var    Generator|null $instance
	 */
	private static $instance = null;

	/**
	 * Settings variable
	 *
	 * @access private
	 * @var mixed|void
	 */
	private $settings;

	/**
	 * Products to export per query.
	 *
	 * @since 2.0.0
	 */
	const PRODUCTS_PER_QUERY = 1;

	/**
	 * Get plugin instance.
	 *
	 * @since  2.0.0
	 * @return Generator;
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Constructor method.
	 *
	 * @since 0.3.0
	 * @since 2.0.0  Changed to private.
	 */
	private function __construct() {

		// Get plugin settings.
		$this->settings = get_option( 'wooya_settings' );

	}

	/**
	 * Init YML generation.
	 *
	 * @since  2.0.0
	 * @return array
	 */
	public function init() {

		$currency = $this->check_currency();
		if ( ! $currency ) {
			return [ 'code' => 501 ];
		}

		$query = $this->check_products();
		if ( ! $query ) {
			return [ 'code' => 503 ];
		}

		set_transient( 'wooya-generating-yml', true, MINUTE_IN_SECONDS * 5 );
		update_option( 'wooya-progress-step', 0 );

		$steps = $query->found_posts / self::PRODUCTS_PER_QUERY;

		return [
			'code'  => 200,
			'steps' => $steps,
		];

	}

	/**
	 * Check if YML generator is running.
	 *
	 * @since  2.0.0
	 * @return bool|int
	 */
	public function is_running() {

		return get_transient( 'wooya-generating-yml' );

	}

	/**
	 * Reset/halt generation process.
	 *
	 * @since 2.0.0
	 */
	public function stop() {

		delete_transient( 'wooya-generating-yml' );
		delete_option( 'wooya-progress-step' );

		// Remove cron lock.
		delete_option( 'market_exporter_doing_cron' );

	}

	/**
	 * Generate YML in batches.
	 *
	 * @since  2.0.0
	 * @return array
	 */
	public function run_step() {

		$yml          = '';
		$current_step = (int) get_option( 'wooya-progress-step' );
		$next_step    = absint( ++$current_step );
		$currency     = $this->check_currency();

		if ( 1 === $next_step ) {
			// Generate XML data.
			$yml .= $this->yml_header( $currency );
		}

		// Generate batch.
		$query = $this->check_products();
		$yml  .= $this->yml_offers( $currency, $query );

		if ( 'last_step' ) {
			$yml .= $this->yml_footer();
			$this->stop();
		} else {
			update_option( 'wooya-progress-step', $next_step );
		}

		// Create file.
		$filesystem = new FS( 'market-exporter' );
		$file_path  = $filesystem->write_file( $yml, $this->settings['file_date'] );

		return [
			'finish' => false,
			'step'   => $next_step,
		];

	}

	/**
	 * Check currency.
	 *
	 * Checks if the selected currency in WooCommerce is supported by Yandex Market.
	 * As of today it is allowed to list products in six currencies: RUB, UAH, BYR, KZT, USD and EUR.
	 * But! WooCommerce doesn't support BYR and KZT. And USD and EUR can be used only to export products.
	 * They will still be listed in RUB or UAH.
	 *
	 * @since  0.3.0
	 * @return string  Returns currency if it is supported, else false.
	 */
	private function check_currency() {

		$currency = get_woocommerce_currency();

		switch ( $currency ) {
			case 'RUB':
				return 'RUR';
			case 'BYR':
				return 'BYN';
			case 'UAH':
			case 'BYN':
			case 'USD':
			case 'EUR':
			case 'KZT':
				return $currency;
			default:
				return false;
		}

	}

	/**
	 * Check if any products ara available for export.
	 *
	 * @since  0.3.0
	 * @return bool|\WP_Query  Return products.
	 */
	private function check_products() {

		$args = array(
			'posts_per_page' => -1,
			'post_type'      => array( 'product' ),
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => '_price',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
				array(
					'key'   => '_stock_status',
					'value' => 'instock',
				),
			),
			'orderby'        => 'ID',
			'order'          => 'DESC',
		);

		// Support for backorders.
		if ( isset( $this->settings['backorders'] ) && true === $this->settings['backorders'] ) {
			array_pop( $args['meta_query'] );
			$args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key'   => '_stock_status',
					'value' => 'instock',
				),
				array(
					'key'   => '_backorders',
					'value' => 'yes',
				),
			);
		}

		// If in options some specific categories are defined for export only.
		if ( isset( $this->settings['include_cat'] ) && ! empty( $this->settings['include_cat'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $this->settings['include_cat'],
				),
			);
		}

		$query = new \WP_Query( $args );
		if ( 0 !== $query->found_posts ) {
			return $query;
		}

		return false;

	}

}
