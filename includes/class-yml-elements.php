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
	 * Get header elements.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_header_elements() {

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

		return $elements;

	}

}
