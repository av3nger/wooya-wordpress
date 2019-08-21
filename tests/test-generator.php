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
	 * Change product stock status.
	 *
	 * @since 2.0.6
	 * @param string $status  Stock status. Accepts: intstock, outofstock, onbackorder.
	 */
	private function set_stock( $status ) {

		$this->product->set_stock_status( $status );
		$this->product->save();

	}

	/**
	 * Test the offer header.
	 *
	 * @since 2.0.6
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

	/**
	 * Test various product availability options.
	 *
	 * @since 2.0.6
	 * @covers \Wooya\Includes\Generator::check_products
	 */
	public function test_check_products() {

		$settings = $this->helper->read_private_property( $this->generator, 'settings' );

		$this->set_stock( 'instock' );
		$results = $this->helper->call_private_method( $this->generator, 'check_products' );
		$this->assertEquals( 1, $results->found_posts );

		$this->set_stock( 'outofstock' );
		$results = $this->helper->call_private_method( $this->generator, 'check_products' );
		$this->assertEquals( 0, $results->found_posts );

		$this->set_stock( 'onbackorder' );
		$results = $this->helper->call_private_method( $this->generator, 'check_products' );
		$this->assertEquals( 0, $results->found_posts );

		$settings['offer']['backorders'] = true;
		$this->helper->set_private_property( $this->generator, 'settings', $settings );

		$results = $this->helper->call_private_method( $this->generator, 'check_products' );
		$this->assertEquals( 1, $results->found_posts );

		$this->set_stock( 'outofstock' );
		$results = $this->helper->call_private_method( $this->generator, 'check_products' );
		$this->assertEquals( 0, $results->found_posts );

		$this->set_stock( 'instock' );
		$results = $this->helper->call_private_method( $this->generator, 'check_products' );
		$this->assertEquals( 1, $results->found_posts );

	}

	/**
	 * Test the add_child method.
	 *
	 * @since 2.0.6
	 * @covers \Wooya\Includes\Generator::add_child
	 */
	public function test_add_child() {

		// Basic tag with value and closing tag.
		$args    = [ 'name', 'value' ];
		$results = $this->helper->call_private_method( $this->generator, 'add_child', $args );
		$this->assertEquals( '<name>value</name>', trim( $results ) );

		// Tag without value (should never happen).
		$args    = [ 'name', '' ];
		$results = $this->helper->call_private_method( $this->generator, 'add_child', $args );
		$this->assertEmpty( $results );

		// Opening tag, $value = null, for example, <offer>.
		$args    = [ 'offer', null ];
		$results = $this->helper->call_private_method( $this->generator, 'add_child', $args );
		$this->assertEquals( '<offer>', trim( $results ) );

		// Closing tag, $closing = true, $value empty or null , for example, </offer>.
		$args    = [ 'offer', '', 0, true ];
		$results = $this->helper->call_private_method( $this->generator, 'add_child', $args );
		$this->assertEquals( '</offer>', trim( $results ) );

		// Self closing tag, $has_children = false, for example, <currency />.
		$args    = [ 'currency', null, 0, false, false ];
		$results = $this->helper->call_private_method( $this->generator, 'add_child', $args );
		$this->assertEquals( '<currency />', trim( $results ) );

	}

}
