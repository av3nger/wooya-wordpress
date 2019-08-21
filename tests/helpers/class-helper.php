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

/**
 * Class Helper
 */
class Helper {

	/**
	 * Allow to override private/protected methods.
	 *
	 * @since 2.0.6
	 *
	 * @param object $object       Reference to the object.
	 * @param string $method_name  Method to call.
	 * @param array  $args         Array of objects to pass to the method.
	 *
	 * @return mixed  Method results.
	 * @throws ReflectionException  Exception.
	 */
	public function call_private_method( &$object, $method_name, array $args = array() ) {

		$reflection = new ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $args );

	}

}
