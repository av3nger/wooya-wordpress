<?php
/**
 * Available elements for the YML file.
 *
 * @link       https://wooya.ru
 * @since      1.0.0
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
class YML_Elements {

	/**
	 * Get all elements combined into a single array.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_elements() {

		$elements['shop']  = self::get_shop_elements();
		$elements['offer'] = self::get_offer_elements();

		return $elements;

	}

	/**
	 * Get shop elements.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private static function get_shop_elements() {

		$elements = array();

		$elements['name'] = array(
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
		);

		$elements['company'] = array(
			'type'        => 'text',
			'default'     => '',
			'max_length'  => 0,
			'required'    => true,
			'description' => __(
				'Полное наименование компании, владеющей магазином. Не публикуется, используется для внутренней идентификации.',
				'wooya'
			),
		);

		$elements['url'] = array(
			'type'        => 'text',
			'default'     => get_site_url(),
			'max_length'  => 0,
			'required'    => false,
			'description' => __(
				'URL главной страницы магазина. Максимум 50 символов. Допускаются кириллические ссылки. Элемент обязателен
				при размещении по модели «Переход на сайт».',
				'wooya'
			),
		);

		$elements['platform'] = array(
			'type'        => 'text',
			'default'     => __( 'WordPress', 'wooya' ),
			'max_length'  => 0,
			'required'    => false,
			'description' => __( 'Система управления контентом, на основе которой работает магазин (CMS).', 'wooya' ),
		);

		$elements['version'] = array(
			'type'        => 'text',
			'default'     => get_bloginfo( 'version' ),
			'max_length'  => 0,
			'required'    => false,
			'description' => __( 'Версия CMS.', 'wooya' ),
		);

		$elements['agency'] = array(
			'type'        => 'text',
			'default'     => '',
			'max_length'  => 0,
			'required'    => false,
			'description' => __(
				'Наименование агентства, которое оказывает техническую поддержку магазину и отвечает
			за работоспособность сайта.',
				'wooya'
			),
		);

		$elements['email'] = array(
			'type'        => 'text',
			'default'     => get_bloginfo( 'admin_email' ),
			'max_length'  => 0,
			'required'    => false,
			'description' => __( 'Контактный адрес разработчиков CMS или агентства, осуществляющего техподдержку.', 'wooya' ),
		);

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
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private static function get_offer_elements() {

		$elements = array();

		$attributes = self::get_attributes_array();

		$elements['model'] = array(
			'type'        => 'select',
			'default'     => 'disabled',
			'description' => __( 'Модель товара.', 'wooya' ),
			'values'      => $attributes,
		);

		$elements['vendor'] = array(
			'type'        => 'select',
			'default'     => 'color',
			'description' => __( 'Название производителя.', 'wooya' ),
			'values'      => $attributes,
		);

		$elements['vendorCode'] = array(
			'type'        => 'select',
			'default'     => 'model',
			'description' => __( 'Код производителя для данного товара.', 'wooya' ),
			'values'      => $attributes,
		);

		$elements['backorders'] = array(
			'type'        => 'checkbox',
			'default'     => true,
			'description' => __( 'Если активна, то товары, доступные для предзаказа, будут экспортированы в YML.', 'wooya' ),
		);

		return $elements;

	}

	/**
	 * Get custom attributes.
	 *
	 * Used on WooCommerce settings page. It lets the user choose which of the custom attributes to use for various settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private static function get_attributes_array() {

		if ( ! $attributes = wp_cache_get( 'wooya_attributes' ) ) {
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

	// TODO: general settings
	// TODO: get_promo_elements

}
