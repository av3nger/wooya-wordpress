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
	const PRODUCTS_PER_QUERY = 200;

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

		$currency = $this->check_currency();
		if ( ! $currency ) {
			return [ 'code' => 501 ];
		}

		// Init the scan (this is the first step).
		if ( 0 === $step && 0 === $total ) {
			set_transient( 'wooya-generating-yml', true, MINUTE_IN_SECONDS * 5 );

			$query = $this->check_products();
			if ( 0 === $query->found_posts ) {
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
					'key'   => '_backorders',
					'value' => 'yes',
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

		// Settings for delivery-options.
		if ( isset( $this->settings['delivery_options'] ) && $this->settings['offer']['delivery_options'] ) {
			$yml .= '    <delivery-options>' . PHP_EOL;
			$cost = $this->settings['delivery']['cost'];
			$days = $this->settings['delivery']['days'];
			if ( isset( $this->settings['delivery']['order_before'] ) && ! empty( $this->settings['delivery']['order_before'] ) ) {
				$yml .= '        <option cost="' . $cost . '" days="' . $days . '" order-before="' . $this->settings['delivery']['order_before'] . '"/>';
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
				// NOTE: Below this point we start using $offer instead of $product.
				// This is used for detecting if typePrefix is set. If it is, we need to add type="vendor.model" to
				// offer and remove the name attribute.
				$type_prefix_set = false;
				if ( isset( $this->settings['offer']['type_prefix'] ) && 'not_set' !== $this->settings['offer']['type_prefix'] ) {
					$type_prefix = $product->get_attribute( 'pa_' . $this->settings['offer']['type_prefix'] );
					if ( $type_prefix ) {
						$type_prefix_set = true;
					}
				}
				$yml .= '      <offer id="' . $offer_id . '"' . ( ( $type_prefix_set ) ? ' type="vendor.model"' : '' ) . ' available="' . ( ( $offer->is_in_stock() ) ? 'true' : 'false' ) . '">' . PHP_EOL;
				$yml .= '        <url>' . htmlspecialchars( get_permalink( $offer->get_id() ) ) . '</url>' . PHP_EOL;
				// Price.
				if ( $offer->get_sale_price() && ( $offer->get_sale_price() < $offer->get_regular_price() ) ) {
					$yml .= '        <price>' . $offer->get_sale_price() . '</price>' . PHP_EOL;
					$yml .= '        <oldprice>' . $offer->get_regular_price() . '</oldprice>' . PHP_EOL;
				} else {
					$yml .= '        <price>' . apply_filters( 'me_product_price', $offer->get_regular_price(), $offer->get_id() ) . '</price>' . PHP_EOL;
				}
				$yml .= '        <currencyId>' . $currency . '</currencyId>' . PHP_EOL;
				// Category.
				// Not using $offer_id, because variable products inherit category from parent.
				$categories = get_the_terms( $product->get_id(), 'product_cat' );
				// TODO: display error message if category is not set for product.
				if ( $categories ) {
					$category = array_shift( $categories );
					$yml     .= '        <categoryId>' . $category->term_id . '</categoryId>' . PHP_EOL;
				}
				// Delivery-options.
				if ( isset( $this->settings['delivery']['delivery_options'] ) && $this->settings['delivery']['delivery_options'] ) {
					$cost         = get_post_custom_values( 'me_do_cost', $product->get_id() );
					$days         = get_post_custom_values( 'me_do_days', $product->get_id() );
					$order_before = get_post_custom_values( 'me_do_order_before', $product->get_id() );
					if ( isset( $cost ) || isset( $days ) || isset( $order_before ) ) {
						$cost         = isset( $cost ) ? $cost[0] : $this->settings['delivery']['cost'];
						$days         = isset( $days ) ? $days[0] : $this->settings['delivery']['days'];
						$order_before = isset( $order_before ) ? $order_before[0] : '';

						$yml .= '        <delivery-options>' . PHP_EOL;
						if ( isset( $order_before ) && ! empty( $order_before ) ) {
							$yml .= '        <option cost="' . $cost . '" days="' . $days . '" order-before="' . $order_before . '"/>';
						} else {
							$yml .= '        <option cost="' . $cost . '" days="' . $days . '"/>';
						}
						$yml .= '        </delivery-options>' . PHP_EOL;
					}
				}
				// Get images.
				$main_image = get_the_post_thumbnail_url( $offer->get_id(), 'full' );
				// If no image found for product, it's probably a variation without an image, get the image from parent.
				if ( ! $main_image ) {
					$main_image = get_the_post_thumbnail_url( $product->get_id(), 'full' );
				}
				if ( false !== $main_image && strlen( utf8_decode( $main_image ) ) <= 512 ) {
					$yml .= '        <picture>' . esc_url( $main_image ) . '</picture>' . PHP_EOL;
				}
				if ( Helper::woo_latest_versions() ) {
					$attachment_ids = $product->get_gallery_image_ids();
				} else {
					$attachment_ids = $product->get_gallery_attachment_ids();
				}
				// Each product can have max 10 images, one was added on top.
				if ( count( $attachment_ids ) > 9 ) {
					$attachment_ids = array_slice( $attachment_ids, 0, 9 );
				}
				if ( 1 < $this->settings['offer']['image_count'] ) {
					$exported = 1;
					while ( $exported < $this->settings['offer']['image_count'] ) {
						if ( ! isset( $attachment_ids[ $exported - 1 ] ) ) {
							break;
						}
						$image = wp_get_attachment_url( $attachment_ids[ $exported - 1 ] );
						if ( false !== $image && strlen( utf8_decode( $image ) ) <= 512 && $image !== $main_image ) {
							$yml .= '        <picture>' . esc_url( $image ) . '</picture>' . PHP_EOL;
						}
						$exported ++;
					}
				}
				// Store.
				if ( isset( $this->settings['delivery']['store'] ) && 'disabled' !== $this->settings['delivery']['store'] ) {
					$yml .= '        <store>' . $this->settings['delivery']['store'] . '</store>' . PHP_EOL;
				}
				// Pickup.
				if ( isset( $this->settings['delivery']['pickup'] ) && 'disabled' !== $this->settings['delivery']['pickup'] ) {
					$yml .= '        <pickup>' . $this->settings['delivery']['pickup'] . '</pickup>' . PHP_EOL;
				}
				// Delivery.
				if ( isset( $this->settings['delivery']['delivery'] ) && 'disabled' !== $this->settings['delivery']['delivery'] ) {
					$yml .= '        <delivery>' . $this->settings['delivery']['delivery'] . '</delivery>' . PHP_EOL;
				}

				$yml .= '        <name>' . $this->clean( $offer->get_title() ) . '</name>' . PHP_EOL;

				// type_prefix.
				if ( $type_prefix_set ) {
					$yml .= '        <typePrefix>' . wp_strip_all_tags( $type_prefix ) . '</typePrefix>' . PHP_EOL;
				}
				// Vendor.
				if ( isset( $this->settings['offer']['vendor'] ) && 'not_set' !== $this->settings['offer']['vendor'] ) {
					$vendor = $offer->get_attribute( 'pa_' . $this->settings['offer']['vendor'] );
					if ( $vendor ) {
						$yml .= '        <vendor>' . wp_strip_all_tags( $vendor ) . '</vendor>' . PHP_EOL;
					}
				}
				// Model.
				if ( isset( $this->settings['offer']['model'] ) && 'not_set' !== $this->settings['offer']['model'] ) {
					$model = $product->get_attribute( 'pa_' . $this->settings['offer']['model'] );
					if ( $model ) {
						$yml .= '        <model>' . wp_strip_all_tags( $model ) . '</model>' . PHP_EOL;
					}
				}
				// Vendor code.
				if ( $offer->get_sku() ) {
					$yml .= '        <vendorCode>' . $offer->get_sku() . '</vendorCode>' . PHP_EOL;
				}
				// Description.
				$description = $this->get_description( $this->settings['misc']['description'] );
				if ( $description ) {
					$yml .= '        <description><![CDATA[' . $description . ']]></description>' . PHP_EOL;
				}
				// Sales notes.
				$sales = get_post_custom_values( 'me_sales_notes', $product->get_id() );
				if ( isset( $sales ) ) {
					$yml .= '        <sales_notes>' . $sales[0] . '</sales_notes>' . PHP_EOL;
				} elseif ( strlen( $this->settings['offer']['sales_notes'] ) > 0 ) {
					$yml .= '        <sales_notes>' . wp_strip_all_tags( $this->settings['offer']['sales_notes'] ) . '</sales_notes>' . PHP_EOL;
				}
				// Manufacturer warranty.
				if ( isset( $this->settings['offer']['warranty'] ) && 'not_set' !== $this->settings['offer']['warranty'] ) {
					$warranty = $offer->get_attribute( 'pa_' . $this->settings['offer']['warranty'] );
					if ( $warranty ) {
						$yml .= '        <manufacturer_warranty>' . wp_strip_all_tags( $warranty ) . '</manufacturer_warranty>' . PHP_EOL;
					}
				}
				// Country of origin.
				if ( isset( $this->settings['offer']['origin'] ) && 'not_set' !== $this->settings['offer']['origin'] ) {
					$origin = $offer->get_attribute( 'pa_' . $this->settings['offer']['origin'] );
					if ( $origin ) {
						$yml .= '        <country_of_origin>' . wp_strip_all_tags( $origin ) . '</country_of_origin>' . PHP_EOL;
					}
				}
				// Params: size and weight.
				// TODO: refactor. Too many nested if...else statements.
				if ( isset( $this->settings['offer']['size'] ) && $this->settings['offer']['size'] ) {
					$weight_unit = esc_attr( get_option( 'woocommerce_weight_unit' ) );
					if ( $offer->has_weight() && 'kg' === $weight_unit ) {
						$yml .= '        <weight>' . $offer->get_weight() . '</weight>' . PHP_EOL;
					}
					$size_unit = esc_attr( get_option( 'woocommerce_dimension_unit' ) );
					if ( $offer->has_dimensions() ) {
						if ( Helper::woo_latest_versions() ) {
							// WooCommerce version 3.0 and higher.
							$dimensions = $offer->get_dimensions( false );
						} else {
							// WooCommerce 2.6 and lower.
							$dimensions = [
								'length' => $offer->get_length(),
								'width'  => $offer->get_width(),
								'height' => $offer->get_height(),
							];
						}

						switch ( $size_unit ) {
							case 'm':
								$dimensions = [
									'length' => $dimensions['length'] * 100,
									'width'  => $dimensions['width'] * 100,
									'height' => $dimensions['height'] * 100,
								];
								break;
							case 'mm':
								$dimensions = [
									'length' => $dimensions['length'] / 10,
									'width'  => $dimensions['width'] / 10,
									'height' => $dimensions['height'] / 10,
								];
								break;
							case 'in':
								$dimensions = [
									'length' => $dimensions['length'] * 2.54,
									'width'  => $dimensions['width'] * 2.54,
									'height' => $dimensions['height'] * 2.54,
								];
								break;
							case 'yd':
								$dimensions = [
									'length' => $dimensions['length'] * 91.44,
									'width'  => $dimensions['width'] * 91.44,
									'height' => $dimensions['height'] * 91.44,
								];
								break;
							case 'cm':
							case 'default':
								// Nothing to do.
								break;
						}

						$dimensions = implode( '/', $dimensions );

						$yml .= '        <dimensions>' . $dimensions . '</dimensions>' . PHP_EOL;
					}
				}

				// Params: stock_quantity.
				if ( isset( $this->settings['offer']['stock_quantity'] ) && $this->settings['offer']['stock_quantity'] ) {
					// Compatibility for WC versions from 2.5.x to 3.0+.
					if ( method_exists( $product, 'get_manage_stock' ) ) {
						$stock_status = $product->get_manage_stock(); // For version 3.0+.
					} else {
						$stock_status = $product->manage_stock; // Older than version 3.0.
					}

					if ( $stock_status ) {
						// Compatibility for WC versions from 2.5.x to 3.0+.
						if ( method_exists( $product, 'get_stock_quantity' ) ) {
							$stock_quqntity = $product->get_stock_quantity(); // For version 3.0+.
						} else {
							$stock_quqntity = $product->stock_quqntity; // Older than version 3.0.
						}
						if ( isset( $stock_quqntity ) && 0 < $stock_quqntity ) {
							$yml .= '        <stock_quantity>' . absint( $stock_quqntity ) . '</stock_quantity>' . PHP_EOL;
						}
					}
				}

				// Params: selected parameters.
				if ( isset( $this->settings['offer']['params'] ) && ! empty( $this->settings['offer']['params'] ) ) {
					$attributes = $product->get_attributes();
					foreach ( $this->settings['offer']['params'] as $param ) {
						// Encode the name, because cyrillic letters won't work in array_key_exists.
						$encoded = strtolower( rawurlencode( $param['value'] ) );
						if ( ! array_key_exists( 'pa_' . $encoded, $attributes ) ) {
							continue;
						}

						// See https://wordpress.org/support/topic/атрибуты-вариантивного-товара/#post-9607195.
						$param_value = $offer->get_attribute( $param['value'] );
						if ( empty( $param_value ) ) {
							$param_value = $product->get_attribute( $param['value'] );
						}

						$yml .= $this->generate_param( $param['value'], $param_value );
					}
				} else {
					$attributes = $product->get_attributes();
					/**
					 * Parameter.
					 *
					 * @var \WC_Product_Attribute|array $param
					 */
					foreach ( $attributes as $param ) {
						if ( Helper::woo_latest_versions() ) {
							$taxonomy = wc_attribute_taxonomy_name_by_id( $param->get_id() );
						} else {
							$taxonomy = $param['name'];
						}

						if ( isset( $param['variation'] ) && true === $param['variation'] || isset( $param['is_variation'] ) && 1 === $param['is_variation'] ) {
							$param_value = $offer->get_attribute( $taxonomy );
						} else {
							$param_value = $product->get_attribute( $taxonomy );
						}

						$yml .= $this->generate_param( $taxonomy, $param_value );
					}
				}

				// Downloadable.
				if ( $product->is_downloadable() ) {
					$yml .= '        <downloadable>true</downloadable>';
				}
				$yml .= '      </offer>' . PHP_EOL;
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

	/**
	 * Get product description.
	 *
	 * @since   1.0.0
	 * @used-by ME_WC::yml_offers()
	 * @param   string $type  Description type. Accepts: default, long, short.
	 * @return  string
	 */
	private function get_description( $type = 'default' ) {

		global $product, $offer;

		$description = '';

		switch ( $type ) {
			case 'default':
				if ( Helper::woo_latest_versions() ) {
					// Try to get variation description.
					$description = $offer->get_description();
					// If not there - get product description.
					if ( empty( $description ) ) {
						$description = $product->get_description();
					}
				} else {
					if ( $product->is_type( 'variable' ) && ! $offer->get_variation_description() ) {
						$description = $offer->get_variation_description();
					} else {
						$description = $offer->post->post_content;
					}
				}
				break;
			case 'long':
				// Get product description.
				if ( Helper::woo_latest_versions() ) {
					$description = $product->get_description();
				} else {
					$description = $offer->post->post_content;
				}
				break;
			case 'short':
				// Get product short description.
				if ( Helper::woo_latest_versions() ) {
					$description = $product->get_short_description();
				} else {
					$description = $offer->post->post_excerpt;
				}
				break;
		}

		// Leave in only allowed html tags.
		$description = strip_tags( strip_shortcodes( $description ), '<h3><ul><li><p>' );
		$description = html_entity_decode( $description, ENT_COMPAT, 'UTF-8' );

		// Cannot be longer then 3000 characters.
		// This causes an error on many installs
		// $description = substr( $description, 0, 2999 );.
		return $description;

	}

	/**
	 * Generate <param> attribute.
	 *
	 * @since 2.0.0
	 *
	 * @param string $taxomnomy  Taxonomy label.
	 * @param string $value      Taxonomy value.
	 *
	 * @return bool|string
	 */
	private function generate_param( $taxomnomy, $value ) {
		// Skip if empty value (when cyrillic letter are used in attribute slug).
		if ( ! isset( $value ) || empty( $value ) ) {
			return false;
		}

		$yml    = '';
		$params = explode( ',', $value );
		foreach ( $params as $single_param ) {
			$param_name  = apply_filters( 'me_param_name', wc_attribute_label( $taxomnomy ) );
			$param_value = apply_filters( 'me_param_value', trim( $single_param ) );
			$param_unit  = apply_filters( 'me_param_unit', $param_name, false );

			$yml .= '        <param name="' . $param_name . '" ' . $param_unit . '>' . $param_value . '</param>' . PHP_EOL;
		}

		return $yml;
	}

}
