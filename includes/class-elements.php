<?php
/**
 * Available elements for the YML file.
 *
 * @link       https://wooya.ru
 * @since      2.0.0
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 */

namespace Wooya\Includes;

/**
 * Available elements for the YML file.
 *
 * All the available elements that can be used in the configuration.
 * Warning! Description field is used as tooltip text, and new lines (or \n) are considered as new <p> element.
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class Elements {

	/**
	 * Get all elements combined into a single array.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public static function get_elements() {

		$elements['shop']     = self::get_shop_elements();
		$elements['offer']    = self::get_offer_elements();
		$elements['delivery'] = self::get_delivery_option_elements();
		$elements['misc']     = self::get_misc_elements();

		return $elements;

	}

	/**
	 * Get shop elements.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private static function get_shop_elements() {

		$elements = [];

		$elements['name'] = [
			'type'        => 'text',
			'default'     => get_bloginfo( 'name' ),
			'max_length'  => 20,
			'required'    => true,
			'description' => __(
				'Короткое название магазина, не более 20 символов. В названии нельзя использовать слова, не имеющие
				отношения к наименованию магазина, например «лучший», «дешевый», указывать номер телефона и т. п. Название
				магазина должно совпадать с фактическим названием магазина, которое публикуется на сайте. При несоблюдении
				этого требования наименование Яндекс.Маркет может самостоятельно изменить название без уведомления магазина.',
				'wooya'
			),
		];

		$elements['company'] = [
			'type'        => 'text',
			'default'     => get_bloginfo( 'name' ),
			'max_length'  => 0,
			'required'    => true,
			'description' => __(
				'Полное наименование компании, владеющей магазином. Не публикуется, используется для внутренней идентификации.',
				'wooya'
			),
		];

		$elements['url'] = [
			'type'        => 'text',
			'default'     => get_site_url(),
			'max_length'  => 0,
			'required'    => false,
			'description' => __(
				'URL главной страницы магазина. Максимум 50 символов. Допускаются кириллические ссылки. Элемент обязателен
				при размещении по модели «Переход на сайт».',
				'wooya'
			),
		];

		$elements['platform'] = [
			'type'        => 'text',
			'default'     => __( 'WordPress', 'wooya' ),
			'max_length'  => 0,
			'required'    => false,
			'description' => __( 'Система управления контентом, на основе которой работает магазин (CMS).', 'wooya' ),
		];

		$elements['version'] = [
			'type'        => 'text',
			'default'     => get_bloginfo( 'version' ),
			'max_length'  => 0,
			'required'    => false,
			'description' => __( 'Версия CMS.', 'wooya' ),
		];

		$elements['agency'] = [
			'type'        => 'text',
			'default'     => '',
			'max_length'  => 0,
			'required'    => false,
			'description' => __(
				'Наименование агентства, которое оказывает техническую поддержку магазину и отвечает
			за работоспособность сайта.',
				'wooya'
			),
		];

		$elements['email'] = [
			'type'        => 'text',
			'default'     => get_bloginfo( 'admin_email' ),
			'max_length'  => 0,
			'required'    => false,
			'description' => __( 'Контактный адрес разработчиков CMS или агентства, осуществляющего техподдержку.', 'wooya' ),
		];

		/**
		 * Other elements:
		 *
		 * - currencies
		 * - categories
		 * - delivery-options
		 * - enable_auto_discounts
		 * - offers
		 * - promos
		 * - gifts
		 */

		return $elements;

	}

	/**
	 * Get offer elements.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private static function get_offer_elements() {

		$elements = [];

		$attributes = self::get_attributes_array();

		$elements['model'] = [
			'type'        => 'select',
			'default'     => 'disabled',
			'required'    => false,
			'description' => __( 'Модель товара.', 'wooya' ),
			'values'      => $attributes,
		];

		$elements['vendor'] = [
			'type'        => 'select',
			'default'     => 'disabled',
			'required'    => false,
			'description' => __( 'Название производителя.', 'wooya' ),
			'values'      => $attributes,
		];

		$elements['typePrefix'] = [
			'type'        => 'select',
			'default'     => 'disabled',
			'required'    => false,
			'description' => __( 'Тип или категорию товара.', 'wooya' ),
			'values'      => $attributes,
		];

		$elements['vendorCode'] = [
			'type'        => 'select',
			'default'     => 'disabled',
			'required'    => false,
			'description' => __( 'Код производителя для данного товара.', 'wooya' ),
			'values'      => $attributes,
		];

		$elements['backorders'] = [
			'type'        => 'checkbox',
			'default'     => true,
			'required'    => false,
			'description' => __( 'If enabled products that are available for backorder will be exported to YML.', 'wooya' ),
		];

		$elements['include_cat'] = [
			'type'        => 'multiselect',
			'default'     => '',
			'required'    => false,
			'description' => __(
				'Only selected categories will be included in the export file. Hold down the control (ctrl) button on
				Windows or command (cmd) on Mac to select multiple options. If nothing is selected - all the categories
				will be exported.',
				'wooya'
			),
			'values'      => self::get_categories_array(),
		];

		$elements['sales_notes'] = [
			'type'        => 'text',
			'default'     => '',
			'required'    => false,
			'description' => __( 'Элемент sales_notes позволяет передать условия продажи товара. Not longer than 50 characters.', 'wooya' ),
			'max_length'  => 50,
		];

		$elements['warranty'] = [
			'type'        => 'select',
			'default'     => 'disabled',
			'required'    => false,
			'description' => __( 'Define if manufacturer warranty is available for selected product. Available values: true of false.', 'wooya' ),
			'values'      => $attributes,
		];

		$elements['origin'] = [
			'type'        => 'select',
			'default'     => 'disabled',
			'required'    => false,
			'description' => sprintf(
				/* translators: %s: link to naming rules */
				__( 'Define country of origin for a product. See %1$sthis link%2$s for a list of available values.', 'wooya' ),
				'<a href="http://partner.market.yandex.ru/pages/help/Countries.pdf" target="_blank">',
				'</a>'
			),
			'values'      => $attributes,
		];

		$elements['size'] = [
			'type'        => 'checkbox',
			'default'     => true,
			'required'    => false,
			'description' => __( 'If enabled weight and size data from WooCommerce will be exported to Weight and Dimensions elements.', 'wooya' ),
		];

		$elements['params'] = [
			'type'        => 'multiselect',
			'default'     => '',
			'required'    => false,
			'description' => __(
				'Selected attributes will be exported as a parameters. Hold down the control (ctrl) button on
				Windows or command (cmd) on Mac to select multiple options.',
				'wooya'
			),
			'values'      => self::get_categories_array(),
		];

		$elements['image_count'] = [
			'type'        => 'text',
			'default'     => '5',
			'max_length'  => 2,
			'required'    => false,
			'description' => __( 'Images per product. Not more than 10 images.', 'wooya' ),
		];

		$elements['stock_quantity'] = [
			'type'        => 'checkbox',
			'default'     => true,
			'required'    => false,
			'description' => __( 'Adds the number of available products in stock.', 'wooya' ),
		];

		return $elements;

	}

	/**
	 * Get deliver option settings.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private static function get_delivery_option_elements() {

		$elements = [];

		$select_options = [
			'disabled' => __( 'Disabled', 'market-exporter' ),
			'true'     => __( 'true', 'market-exporter' ),
			'false'    => __( 'false', 'market-exporter' ),
		];

		$elements['delivery'] = [
			'type'        => 'select',
			'default'     => 'disabled',
			'required'    => false,
			'description' => __(
				"Use the delivery element to indicate the possibility of delivery to the buyer's address in the
				home region of the store.",
				'wooya'
			),
			'values'      => $select_options,
		];

		$elements['pickup'] = [
			'type'        => 'select',
			'default'     => 'disabled',
			'required'    => false,
			'description' => __(
				'Use the pickup element to indicate the possibility of receiving goods at the issuance point.',
				'wooya'
			),
			'values'      => $select_options,
		];

		$elements['store'] = [
			'type'        => 'select',
			'default'     => 'disabled',
			'required'    => false,
			'description' => __(
				'Use the store element to indicate the possibility of buying without a preliminary order at the point of sale.',
				'wooya'
			),
			'values'      => $select_options,
		];

		$elements['delivery_options'] = [
			'type'        => 'checkbox',
			'default'     => false,
			'required'    => false,
			'description' => __( 'Use delivery-options parameters defined below. Global options.', 'wooya' ),
		];

		$elements['cost'] = [
			'type'        => 'text',
			'default'     => '',
			'placeholder' => '100',
			'depends_on'  => 'delivery_options',
			'max_length'  => 0,
			'required'    => false,
			'description' => __(
				'Delivery-options cost element. Used to indicate the price of delivery. Use maximum value if cost is
				differs for different locations.',
				'wooya'
			),
		];

		$elements['days'] = [
			'type'        => 'text',
			'default'     => '',
			'placeholder' => __( '0, 1, 2, 3-5, etc', 'wooya' ),
			'depends_on'  => 'delivery_options',
			'max_length'  => 0,
			'required'    => false,
			'description' => __(
				'Delivery-options days element. Either a value or a range for the actual days it takes to deliver a product.',
				'wooya'
			),
		];

		$elements['order_before'] = [
			'type'        => 'text',
			'default'     => '',
			'placeholder' => '0-24',
			'depends_on'  => 'delivery_options',
			'max_length'  => 0,
			'required'    => false,
			'description' => __(
				'Delivery-options order-before element. Accepts values from 0 to 24. If the order is made before this
				time, delivery will be on time.',
				'wooya'
			),
		];

		return $elements;

	}

	/**
	 * Get misc settings.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private static function get_misc_elements() {

		$elements = [];

		$elements['file_date'] = [
			'type'        => 'checkbox',
			'default'     => false,
			'required'    => false,
			'description' => __(
				'Add date to YML file name. If enabled YML file will have current date at the end:
				ym-export-yyyy-mm-dd.yml.',
				'wooya'
			),
		];

		$elements['cron'] = [
			'type'        => 'select',
			'default'     => 'disabled',
			'required'    => false,
			'description' => __( 'Auto generate file at the selected interval.', 'wooya' ),
			'values'      => [
				'disabled'   => __( 'Disabled', 'market-exporter' ),
				'hourly'     => __( 'Every hour', 'market-exporter' ),
				'twicedaily' => __( 'Twice a day', 'market-exporter' ),
				'daily'      => __( 'Daily', 'market-exporter' ),
			],
		];

		$elements['description'] = [
			'type'        => 'select',
			'default'     => 'disabled',
			'required'    => false,
			'description' => __(
				'Product description. Specify the way the description is exported. Default is to try and get the
				product description, if empty - get short description.',
				'wooya'
			),
			'values'      => [
				'default' => __( 'Default', 'market-exporter' ),
				'long'    => __( 'Only description', 'market-exporter' ),
				'short'   => __( 'Only short description', 'market-exporter' ),
			],
		];

		$elements['update_on_change'] = [
			'type'        => 'checkbox',
			'default'     => false,
			'required'    => false,
			'description' => __( 'Regenerate file on product create/update', 'wooya' ),
		];

		return $elements;

	}

	/**
	 * Get custom attributes.
	 *
	 * Used on WooCommerce settings page. It lets the user choose which of the custom attributes to use for various settings.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private static function get_attributes_array() {

		$attributes = wp_cache_get( 'wooya_attributes' );
		if ( ! $attributes ) {
			global $wpdb;

			$attributes = $wpdb->get_results(
				"SELECT attribute_name AS attr_key, attribute_label AS attr_value
					FROM {$wpdb->prefix}woocommerce_attribute_taxonomies",
				ARRAY_N
			); // Db call ok.

			wp_cache_set( 'wooya_attributes', $attributes );
		}

		$attributes_array['disabled'] = __( 'Disabled', 'wooya' );

		foreach ( $attributes as $attribute ) {
			$attributes_array[ $attribute[0] ] = $attribute[1];
		}

		return $attributes_array;

	}

	/**
	 * Get product categories.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private static function get_categories_array() {

		$categories = [];

		$args = [
			'hide_empty' => 0,
			'parent'     => 0,
			'taxonomy'   => 'product_cat',
		];

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return $categories;
		}

		foreach ( $terms as $category ) {
			$categories[ $category->term_id ] = $category->name;

			$subcategories = self::get_cats_from_array( $category->term_id, [] );
			if ( $subcategories ) {
				$categories = array_merge( $categories, $subcategories );
			}
		}

		return $categories;

	}

	/**
	 * Recursive function to populate a list with sub categories.
	 *
	 * @since   2.0.0
	 *
	 * @access  private
	 *
	 * @param  int   $cat_id        Category ID.
	 * @param  array $select_array  Array of selected category IDs.
	 *
	 * @return array|bool
	 */
	private static function get_cats_from_array( $cat_id, $select_array ) {

		static $tabs = 0;
		$tabs++;

		$subcategories = get_terms(
			[
				'hide_empty' => 0,
				'parent'     => $cat_id,
				'taxonomy'   => 'product_cat',
			]
		);

		if ( empty( $subcategories ) ) {
			return false;
		}

		$categories = [];

		foreach ( $subcategories as $subcategory ) {
			$categories[ $subcategory->term_id ] = str_repeat( '-', $tabs ) . ' ' . $subcategory->name;
			self::get_cats_from_array( $subcategory->term_id, $select_array );
			$tabs--;
		}

		return $categories;

	}

}
