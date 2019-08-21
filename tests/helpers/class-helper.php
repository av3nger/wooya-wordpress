<?php
/**
 * General helper class.
 *
 * @since 2.0.6
 * @package Wooya\Tests\Helpers
 */

namespace Wooya\Tests\Helpers;

use ReflectionClass;
use ReflectionException;
use WP_Error;

/**
 * Class Helper
 */
class Helper {

	/**
	 * Read a private property of an object.
	 *
	 * @since 2.0.6
	 *
	 * @param object $object    Object with the property.
	 * @param string $property  Property name.
	 *
	 * @return mixed|WP_Error
	 */
	public function read_private_property( &$object, $property ) {

		try {
			$reflection = new ReflectionClass( get_class( $object ) );
			$property   = $reflection->getProperty( $property );

			$property->setAccessible( true );
			return $property->getValue( $object );
		} catch ( ReflectionException $e ) {
			return new WP_Error( 'error', $e->getMessage() );
		}

	}

	/**
	 * Allow to override private properties.
	 *
	 * @since 2.0.6
	 *
	 * @param object $object    Object with the property.
	 * @param string $property  Property name.
	 * @param mixed  $value     Value to set.
	 *
	 * @return WP_Error
	 */
	public function set_private_property( &$object, $property, $value ) {

		try {
			$reflection = new ReflectionClass( get_class( $object ) );
			$property   = $reflection->getProperty( $property );

			$property->setAccessible( true );
			$property->setValue( $object, $value );
		} catch ( ReflectionException $e ) {
			return new WP_Error( 'error', $e->getMessage() );
		}

	}

	/**
	 * Allow to override private/protected methods.
	 *
	 * @since 2.0.6
	 *
	 * @param object $object       Reference to the object.
	 * @param string $method_name  Method to call.
	 * @param array  $args         Array of objects to pass to the method.
	 *
	 * @return mixed|WP_Error  Method results.
	 */
	public function call_private_method( &$object, $method_name, array $args = array() ) {

		try {
			$reflection = new ReflectionClass( get_class( $object ) );
			$method     = $reflection->getMethod( $method_name );

			$method->setAccessible( true );
			return $method->invokeArgs( $object, $args );
		} catch ( ReflectionException $e ) {
			return new WP_Error( 'error', $e->getMessage() );
		}

	}

}
