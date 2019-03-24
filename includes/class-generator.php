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
	const PRODUCTS_PER_QUERY = 4;

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
	 * @param  int $step   Current step.
	 * @param  int $total  Total number of steps.
	 * @return array
	 */
	public function run_step( $step, $total ) {

		$filesystem = new FS( 'market-exporter' );

		// Init the scan (this is the first step).
		if ( 0 === $step && 0 === $total ) {
			set_transient( 'wooya-generating-yml', true, MINUTE_IN_SECONDS * 5 );

			$currency = $this->check_currency();
			if ( ! $currency ) {
				return [ 'code' => 501 ];
			}

			$query = $this->check_products();
			if ( 0 === $query->found_posts ) {
				return [ 'code' => 503 ];
			}

			// Create file.
			$filesystem->write_file( $this->yml_header( $currency ), $this->settings['file_date'], true, true );

			$total = 1;
			if ( self::PRODUCTS_PER_QUERY < $query->found_posts ) {
				$total = absint( $query->found_posts / self::PRODUCTS_PER_QUERY );
				if ( $query->found_posts % self::PRODUCTS_PER_QUERY ) {
					$total++;
				}
			}
		}

		$query = $this->check_products( self::PRODUCTS_PER_QUERY, self::PRODUCTS_PER_QUERY * $step );

		$file_path = $filesystem->write_file( $this->yml_offers( $currency, $query ), $this->settings['file_date'], true );

		$step++;
		if ( $step === $total ) {
			$file_path = $filesystem->write_file( $this->yml_footer(), $this->settings['file_date'], true );
			$this->stop();
		}

		return [
			'finish' => $step === $total,
			'step'   => $step,
			'steps'  => $total,
			'file'   => $file_path,
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
	 * @param  int $per_page  Products to show per page.
	 * @param  int $offset    Offset by number of products.
	 * @return \WP_Query      Return products.
	 */
	private function check_products( $per_page = -1, $offset = 0 ) {

		$args = array(
			'posts_per_page' => $per_page,
			'offset'         => $offset,
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

		return new \WP_Query( $args );

	}

	/**
	 * Generate YML header.
	 *
	 * @since  0.3.0
	 * @param  string $currency  Currency abbreviation.
	 *
	 * @return string
	 */
	private function yml_header( $currency ) {

		$yml  = '<?xml version="1.0" encoding="' . get_bloginfo( 'charset' ) . '"?>' . PHP_EOL;
		$yml .= '<!DOCTYPE yml_catalog SYSTEM "shops.dtd">' . PHP_EOL;
		$yml .= '<yml_catalog date="' . current_time( 'Y-m-d H:i' ) . '">' . PHP_EOL;
		$yml .= '  <shop>' . PHP_EOL;
		$yml .= '    <name>' . esc_html( $this->settings['shop']['name'] ) . '</name>' . PHP_EOL;
		$yml .= '    <company>' . esc_html( $this->settings['shop']['company'] ) . '</company>' . PHP_EOL;
		$yml .= '    <url>' . get_site_url() . '</url>' . PHP_EOL;
		$yml .= '    <currencies>' . PHP_EOL;

		if ( ( 'USD' === $currency ) || ( 'EUR' === $currency ) ) {
			$yml .= '      <currency id="RUR" rate="1"/>' . PHP_EOL;
			$yml .= '      <currency id="' . $currency . '" rate="СВ" />' . PHP_EOL;
		} else {
			$yml .= '      <currency id="' . $currency . '" rate="1" />' . PHP_EOL;
		}

		$yml .= '    </currencies>' . PHP_EOL;
		$yml .= '    <categories>' . PHP_EOL;

		$args = array(
			'taxonomy' => 'product_cat',
			'orderby'  => 'term_id',
		);

		// Maybe we need to include only selected categories?
		// TODO: fix.
		if ( isset( $this->settings['include_cat'] ) ) {
			$args['include'] = $this->settings['include_cat'];
		}

		foreach ( get_categories( $args ) as $category ) {
			if ( 0 === $category->parent ) {
				$yml .= '      <category id="' . $category->cat_ID . '">' . wp_strip_all_tags( $category->name ) . '</category>' . PHP_EOL;
			} else {
				$yml .= '      <category id="' . $category->cat_ID . '" parentId="' . $category->parent . '">' . wp_strip_all_tags( $category->name ) . '</category>' . PHP_EOL;
			}
		}

		$yml .= '    </categories>' . PHP_EOL;

		// Settings for delivery-options.
		// TODO: fix.
		if ( isset( $this->settings['delivery_options'] ) && $this->settings['delivery_options'] ) {
			$yml .= '    <delivery-options>' . PHP_EOL;
			$cost = $this->settings['cost'];
			$days = $this->settings['days'];
			if ( isset( $this->settings['order_before'] ) && ! empty( $this->settings['order_before'] ) ) {
				$yml .= '        <option cost="' . $cost . '" days="' . $days . '" order-before="' . $this->settings['order_before'] . '"/>';
			} else {
				$yml .= '        <option cost="' . $cost . '" days="' . $days . '"/>';
			}
			$yml .= '    </delivery-options>' . PHP_EOL;
		}

		$yml .= '    <offers>' . PHP_EOL;

		return $yml;

	}

	/**
	 * Generate YML footer.
	 *
	 * @since  0.3.0
	 * @return string
	 */
	private function yml_footer() {

		$yml  = '    </offers>' . PHP_EOL;
		$yml .= '  </shop>' . PHP_EOL;
		$yml .= '</yml_catalog>' . PHP_EOL;

		return $yml;

	}

	/**
	 * Generate YML body with offers.
	 *
	 * @since  0.3.0
	 * @param  string    $currency  Currency abbreviation.
	 * @param  \WP_Query $query     Query.
	 * @return string
	 */
	private function yml_offers( $currency, \WP_Query $query ) {
		return 'product' . PHP_EOL;
	}

}
