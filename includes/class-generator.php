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

use WC_Product_Variation;
use WP_Query;

/**
 * Generate the YML file.
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class Generator extends Attributes {

	/**
	 * Generator instance.
	 *
	 * @since  2.0.0
	 * @access private
	 * @var    Generator|null $instance
	 */
	private static $instance = null;

	/**
	 * Products to export per query.
	 *
	 * @since 2.0.0
	 */
	const PRODUCTS_PER_QUERY = 200;

	/**
	 * Get plugin instance.
	 *
	 * @since  2.0.0
	 * @return Generator.
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Generate YML file from cron or during product update.
	 *
	 * @since 2.0.0
	 */
	public function cron_generate_yml() {

		$step    = 0;
		$steps   = 0;
		$running = $this->is_running();

		if ( ! $running && ! get_option( 'market_exporter_doing_cron' ) ) {
			// Set start of running cron.
			update_option( 'market_exporter_doing_cron', true );
		}

		if ( $running ) {
			$step  = $running[0];
			$steps = $running[1];
		}

		$response = $this->run_step( $step, $steps );

		// There are some steps left - schedule another run.
		if ( isset( $response['step'] ) && isset( $response['steps'] ) && isset( $response['finish'] ) && ! $response['finish'] ) {
			set_transient( 'wooya-generating-yml', [ $response['step'], $response['steps'] ], MINUTE_IN_SECONDS * 5 );
			wp_schedule_single_event( time(), 'market_exporter_cron' );
			return;
		}

		// Cron finished.
		if ( isset( $this->settings['misc'] ) && isset( $this->settings['misc']['cron'] ) ) {
			Helper::update_cron_schedule( $this->settings['misc']['cron'] );
		} else {
			Helper::update_cron_schedule( 'disabled' );
		}

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

		$filesystem = new FS( 'market-exporter' );
		$filesystem->rename( $this->settings['misc']['file_date'] );

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

		$currency = $this->check_currency();
		if ( ! $currency ) {
			$this->stop();
			return [ 'code' => 501 ];
		}

		// Init the scan (this is the first step).
		if ( 0 === $step && 0 === $total ) {
			set_transient( 'wooya-generating-yml', true, MINUTE_IN_SECONDS * 5 );

			$query = $this->check_products( self::PRODUCTS_PER_QUERY, self::PRODUCTS_PER_QUERY * $step );
			if ( 0 === $query->found_posts ) {
				$this->stop();
				return [ 'code' => 503 ];
			}

			// Create file.
			$filesystem->write_file( $this->yml_header( $currency ), $this->settings['misc']['file_date'], true, true );

			$total = 1;
			if ( self::PRODUCTS_PER_QUERY < $query->found_posts ) {
				$total = absint( $query->found_posts / self::PRODUCTS_PER_QUERY );
				if ( $query->found_posts % self::PRODUCTS_PER_QUERY ) {
					$total++;
				}
			}
		}

		$this->populate_categories();
		$query = $this->check_products( self::PRODUCTS_PER_QUERY, self::PRODUCTS_PER_QUERY * $step );

		$file_path = $filesystem->write_file( $this->yml_offers( $currency, $query ), $this->settings['misc']['file_date'], true );

		$step++;
		if ( $step === $total ) {
			$file_path = $filesystem->write_file( $this->yml_footer(), $this->settings['misc']['file_date'], true );
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
	 * @return WP_Query      Return products.
	 */
	private function check_products( $per_page = -1, $offset = 0 ) {

		$args = [
			'posts_per_page' => $per_page,
			'offset'         => $offset,
			'post_type'      => [ 'product' ],
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'     => '_price',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				],
				[
					'key'   => '_stock_status',
					'value' => 'instock',
				],
			],
			'orderby'        => 'ID',
			'order'          => 'DESC',
		];

		// Support for backorders.
		if ( isset( $this->settings['offer']['backorders'] ) && true === $this->settings['offer']['backorders'] ) {
			array_pop( $args['meta_query'] );
			$args['meta_query'][] = [
				'relation' => 'OR',
				[
					'key'   => '_stock_status',
					'value' => 'instock',
				],
				[
					'key'   => '_stock_status',
					'value' => 'onbackorder',
				],
			];
		}

		// If in options some specific categories are defined for export only.
		if ( isset( $this->settings['offer']['include_cat'] ) && ! empty( $this->settings['offer']['include_cat'] ) ) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => array_column( $this->settings['offer']['include_cat'], 'value' ),
				],
			];
		}

		return new WP_Query( $args );

	}

	/**
	 * Get categories array, which we will use this later on to determine the best category for the product.
	 *
	 * @since 2.1.0
	 */
	private function populate_categories() {
		$args = [
			'taxonomy' => 'product_cat',
			'orderby'  => 'term_id',
		];

		// Maybe we need to include only selected categories?
		if ( isset( $this->settings['offer']['include_cat'] ) ) {
			$args['include'] = array_column( $this->settings['offer']['include_cat'], 'value' );
		}

		foreach ( get_categories( $args ) as $category ) {
			// .
			$this->categories[ $category->cat_ID ] = [
				'parent' => $category->parent,
			];
		}
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

		$args = [
			'taxonomy' => 'product_cat',
			'orderby'  => 'term_id',
		];

		// Maybe we need to include only selected categories?
		if ( isset( $this->settings['offer']['include_cat'] ) ) {
			$args['include'] = array_column( $this->settings['offer']['include_cat'], 'value' );
		}

		foreach ( get_categories( $args ) as $category ) {
			if ( 0 === $category->parent ) {
				$yml .= '      <category id="' . $category->cat_ID . '">' . wp_strip_all_tags( $category->name ) . '</category>' . PHP_EOL;
			} else {
				$yml .= '      <category id="' . $category->cat_ID . '" parentId="' . $category->parent . '">' . wp_strip_all_tags( $category->name ) . '</category>' . PHP_EOL;
			}
		}

		$yml .= '    </categories>' . PHP_EOL;
		$yml .= $this->get_delivery_options();
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
	 * @param  string   $currency  Currency abbreviation.
	 * @param  WP_Query $query     Query.
	 * @return string
	 */
	private function yml_offers( $currency, WP_Query $query ) {

		global $product, $offer;

		$yml = '';

		while ( $query->have_posts() ) {
			$query->the_post();
			$product = wc_get_product( $query->post->ID );
			if ( apply_filters( 'me_exclude_post', false, $query->post, $product ) ) {
				continue;
			}
			// We use a separate variable for offer because we will be rewriting it for variable products.
			$offer = $product;
			/**
			 * By default we set $variation_count to 1.
			 * That means that there is at least one product available.
			 * Variation products will have more than 1 count.
			 */
			$variations      = [];
			$variation_count = 1;
			if ( $product->is_type( 'variable' ) ) {
				$variations      = $product->get_available_variations();
				$variation_count = count( $variations );
			}
			while ( $variation_count > 0 ) {
				$variation_count --;
				// If variable product, get product id from $variations array.
				$offer_id = ( ( $product->is_type( 'variable' ) ) ? $variations[ $variation_count ]['variation_id'] : $product->get_id() );
				if ( $product->is_type( 'variable' ) ) :
					// This has to work but we need to think of a way to save the initial offer variable.
					$offer = new WC_Product_Variation( $offer_id );
				endif;

				/**
				 * We need to get typePrefix and model early to decide if the product is a vendor.model type or not.
				 *
				 * @since 2.0.4
				 */
				$vendor_model_type = $model = $type_prefix = false;

				// typePrefix.
				if ( isset( $this->settings['offer']['typePrefix'] ) && 'disabled' !== $this->settings['offer']['typePrefix'] ) {
					$type_prefix = $product->get_attribute( 'pa_' . $this->settings['offer']['typePrefix'] );
					if ( $type_prefix ) {
						$vendor_model_type = true;
					}
				}

				// Model.
				if ( isset( $this->settings['offer']['model'] ) && 'disabled' !== $this->settings['offer']['model'] ) {
					$model = $product->get_attribute( 'pa_' . $this->settings['offer']['model'] );
					if ( $model ) {
						$vendor_model_type = true;
					}
				}

				// NOTE: Below this point we start using $offer instead of $product.
				$yml .= $this->get_offer( $offer_id, $vendor_model_type );

				if ( $variations && isset( $this->settings['offer']['group_id'] ) && $this->settings['offer']['group_id'] ) {
					$yml .= $this->add_child( 'group_id', $product->get_id() );
				}

				$yml .= $this->add_child( 'url', htmlspecialchars( get_permalink( $offer->get_id() ) ), self::ME_OFFER_ATTRIBUTE_SPACING );
				$yml .= $this->get_price();
				$yml .= $this->add_child( 'currencyId', $currency, self::ME_OFFER_ATTRIBUTE_SPACING );
				$yml .= $this->get_category_id();
				$yml .= $this->get_delivery_options( false );
				$yml .= $this->get_images();

				// Store.
				if ( isset( $this->settings['delivery']['store'] ) && 'disabled' !== $this->settings['delivery']['store'] ) {
					$yml .= $this->add_child( 'store', $this->settings['delivery']['store'] );
				}
				// Pickup.
				if ( isset( $this->settings['delivery']['pickup'] ) && 'disabled' !== $this->settings['delivery']['pickup'] ) {
					$yml .= $this->add_child( 'pickup', $this->settings['delivery']['pickup'] );
				}
				// Delivery.
				if ( isset( $this->settings['delivery']['delivery'] ) && 'disabled' !== $this->settings['delivery']['delivery'] ) {
					$yml .= $this->add_child( 'delivery', $this->settings['delivery']['delivery'] );
				}

				if ( ! $vendor_model_type ) {
					$yml .= $this->add_child( 'name', $this->clean( $offer->get_title() ) );
				}

				// typePrefix.
				if ( $type_prefix ) {
					$yml .= $this->add_child( 'typePrefix', wp_strip_all_tags( $type_prefix ) );
				}

				// Vendor.
				if ( isset( $this->settings['offer']['vendor'] ) && 'disabled' !== $this->settings['offer']['vendor'] ) {
					$vendor = $product->get_attribute( 'pa_' . $this->settings['offer']['vendor'] );
					if ( $vendor ) {
						$yml .= $this->add_child( 'vendor', wp_strip_all_tags( $vendor ) );
					}
				}

				// Model.
				if ( $vendor_model_type ) {
					$model = $model ? wp_strip_all_tags( $model ) : $this->clean( $offer->get_title() );
					$yml  .= $this->add_child( 'model', $model );
				}

				// Vendor code.
				if ( isset( $this->settings['offer']['vendorCode'] ) && 'disabled' !== $this->settings['offer']['vendorCode'] ) {
					$vendor_code = $product->get_attribute( 'pa_' . $this->settings['offer']['vendorCode'] );
					if ( $vendor_code ) {
						$yml .= $this->add_child( 'vendorCode', wp_strip_all_tags( $vendor_code ) );
					} else {
						$yml .= $this->add_child( 'vendorCode', $offer->get_sku() );
					}
				}

				// Description.
				$description = $this->get_description( $this->settings['misc']['description'] );
				if ( $description ) {
					$yml .= '        <description><![CDATA[' . $description . ']]></description>' . PHP_EOL;
				}

				// Sales notes.
				$sales = get_post_custom_values( 'me_sales_notes', $product->get_id() );
				if ( isset( $sales ) ) {
					$yml .= $this->add_child( 'sales_notes', $sales[0] );
				} elseif ( isset( $this->settings['offer']['sales_notes'] ) && strlen( $this->settings['offer']['sales_notes'] ) > 0 ) {
					$yml .= $this->add_child( 'sales_notes', wp_strip_all_tags( $this->settings['offer']['sales_notes'] ) );
				}

				// Manufacturer warranty.
				if ( isset( $this->settings['offer']['warranty'] ) && 'disabled' !== $this->settings['offer']['warranty'] ) {
					$warranty = $offer->get_attribute( 'pa_' . $this->settings['offer']['warranty'] );
					if ( $warranty ) {
						$yml .= $this->add_child( 'manufacturer_warranty', wp_strip_all_tags( $warranty ) );
					}
				}
				// Country of origin.
				if ( isset( $this->settings['offer']['origin'] ) && 'disabled' !== $this->settings['offer']['origin'] ) {
					$origin = $offer->get_attribute( 'pa_' . $this->settings['offer']['origin'] );
					if ( $origin ) {
						$yml .= $this->add_child( 'country_of_origin', wp_strip_all_tags( $origin ) );
					}
				}

				$yml .= $this->get_dimensions();
				$yml .= $this->get_stock_quantity();
				$yml .= $this->get_params();

				// Downloadable.
				if ( $product->is_downloadable() ) {
					$yml .= $this->add_child( 'downloadable', true );
				}
				$yml .= $this->add_child( 'offer', null, self::ME_OFFER_SPACING, true );
			}
		}

		return $yml;

	}

	/**
	 * Replace characters that are not allowed in the YML file.
	 *
	 * @since  0.3.0
	 * @param  string $string String to clean.
	 * @return mixed
	 */
	private function clean( $string ) {

		$string = str_replace( '"', '&quot;', $string );
		$string = str_replace( '&', '&amp;', $string );
		$string = str_replace( '>', '&gt;', $string );
		$string = str_replace( '<', '&lt;', $string );
		$string = str_replace( '\'', '&apos;', $string );

		return $string;

	}

}
