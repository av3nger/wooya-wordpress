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
	 * @return int
	 */
	public function init() {

		$currency = $this->check_currency();
		if ( ! $currency ) {
			return 501;
		}

		$query = $this->check_products();
		if ( ! $query ) {
			return 503;
		}

		set_transient( 'wooya-generating-yml', true, MINUTE_IN_SECONDS * 5 );
		update_option( 'wooya-progress-step', 0 );

		return 200;

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
