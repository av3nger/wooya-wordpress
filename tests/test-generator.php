<?php
/**
 * Generator test case: class GeneratorTest
 *
 * @package Wooya\Tests
 */

namespace Wooya\Tests;

use WC_Helper_Product;
use WC_Product;
use Wooya\Includes\Generator;
use Wooya\Tests\Helpers\Helper;
use WP_UnitTestCase;

/**
 * Class GeneratorTest
 *
 * @covers Generator
 */
class GeneratorTest extends WP_UnitTestCase {

	/**
	 * Market Exporter Generator instance.
	 *
	 * @var Generator
	 */
	protected $generator;

	/**
	 * Helper instance.
	 *
	 * @var Helper
	 */
	protected $helper;

	/**
	 * Product instance.
	 *
	 * @var WC_Product
	 */
	protected $product;

	/**
	 * Set up method.
	 */
	public function setUp() {

		parent::setUp();
		$this->generator = new Generator();
		$this->helper    = new Helper();
		$this->product   = WC_Helper_Product::create_simple_product();

	}

	/**
	 * A single example test.
	 *
	 * @covers \Wooya\Includes\Generator::get_offer
	 */
	public function test_get_offer() {

		global $offer;

		$offer = $this->product;
		$id    = $offer->get_id();

		$args    = [ $id, false ];
		$results = $this->helper->call_private_method( $this->generator, 'get_offer', $args );
		$this->assertEquals( "<offer id=\"{$id}\" available=\"true\">", trim( $results ) );

		$args    = [ $id, true ];
		$results = $this->helper->call_private_method( $this->generator, 'get_offer', $args );
		$this->assertEquals( "<offer id=\"{$id}\" type=\"vendor.model\" available=\"true\">", trim( $results ) );

	}

}
