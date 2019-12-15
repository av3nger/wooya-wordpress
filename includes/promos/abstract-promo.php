<?php
/**
 * Abstraction for Promo element.
 *
 * @link       https://wooya.ru
 * @since      2.1.0
 *
 * @package    Wooya
 * @subpackage Wooya\Includes\Promos
 */

namespace Wooya\Includes\Promos;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Promo class abstraction.
 *
 * Common functionality for all promo elements.
 *
 * @since      2.1.0
 * @package    Wooya
 * @subpackage Wooya\Includes\Promos
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
abstract class Promo {

	/**
	 * ID of the promotion.
	 *
	 * Must be unique for the entire price list. It may contain only numbers and Latin letters.
	 * The maximum length of id is 20 characters.
	 *
	 * @since 2.1.0
	 *
	 * @var string $id
	 */
	protected $id;

	/**
	 * Type of promo.
	 *
	 * @since 2.1.0
	 * @var string $type
	 */
	protected $type;

	/**
	 * Date and time when the promo starts.
	 *
	 * Allowed formats: YYYY-MM-DD hh:mm:ss or YYYY-MM-DD.
	 * The date and time are the same for all time zones.
	 *
	 * @since 2.1.0
	 * @var string $start_date
	 */
	protected $start_date;

	/**
	 * Date and time the promotion ends.
	 *
	 * Allowed formats: YYYY-MM-DD hh:mm:ss or YYYY-MM-DD.
	 * The date and time are the same for all time zones.
	 *
	 * @since 2.1.0
	 * @var string $end_date
	 */
	protected $end_date;

	/**
	 * Brief description of the promo.
	 *
	 * Maximum of 500 characters. You can use XHTML markup, but only as a CDATA block of character data.
	 *
	 * @since 2.1.0
	 * @var string $description
	 */
	protected $description;

	/**
	 * Link to the promotion description on the store's website.
	 *
	 * @since 2.1.0
	 * @var string $url
	 */
	protected $url;

	/**
	 * Items and/or categories object that the promotion applies to.
	 *
	 * Each product element corresponds to a single item or category.
	 *
	 * @since 2.1.0
	 * @var \stdClass $product
	 */
	protected $product;

}
