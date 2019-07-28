<?php
/**
 * Helper functionality for the plugin.
 *
 * @link       https://wooya.ru
 * @since      2.0.0
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 */

namespace Wooya\Includes;

/**
 * Helper class that has misc helper functions used throughout the plugin.
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class Helper {

	/**
	 * Performs sanitation of user input.
	 *
	 * @since 2.0.0
	 *
	 * @param string|array $input  Input values to sanitize.
	 *
	 * @return string|array
	 */
	public function sanitize_input_value( $input ) {

		if ( is_string( $input ) ) {
			return sanitize_text_field( $input );
		}

		if ( is_array( $input ) ) {
			foreach ( $input as &$value ) {
				$value = array_map( 'sanitize_text_field', $value );
			}

			return $input;
		}

		if ( is_bool( $input ) ) {
			return (bool) $input;
		}

		return sanitize_key( $input );

	}

	/**
	 * Check WooCommerce version.
	 *
	 * Used to check what code to use. Older version of WooCommerce (prior to 3.0.0) use some older functions
	 * that are deprecated in newer versions.
	 *
	 * @since  0.4.1
	 * @param  string $version WooCommerce version.
	 * @return bool
	 */
	public static function woo_latest_versions( $version = '3.0.0' ) {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$woo_installed = get_plugins( '/woocommerce' );
		$woo_version   = $woo_installed['woocommerce.php']['Version'];

		if ( version_compare( $woo_version, $version, '>=' ) ) {
			return true;
		}

		return false;

	}

}
