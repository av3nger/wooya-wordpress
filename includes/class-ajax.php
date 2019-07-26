<?php
/**
 * The ajax-specific functionality of the plugin.
 *
 * @link       https://wooya.ru
 * @since      2.0.0
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 */

namespace Wooya\Includes;

/**
 * Class Ajax
 */
class Ajax {

	/**
	 * Ajax constructor.
	 */
	public function __construct() {

		add_action( 'wp_ajax_me_settings', [ $this, 'get_settings' ] );
		add_action( 'wp_ajax_me_elements', [ $this, 'get_combined_elements' ] );
		add_action( 'wp_ajax_me_files', [ $this, 'get_files' ] );

	}

	/**
	 * Get settings.
	 *
	 * @return array
	 */
	public function get_settings() {

		$current_settings = get_option( 'wooya_settings' );

		$elements = Elements::get_elements();

		if ( ! isset( $current_settings['delivery'] ) ) {
			foreach ( $elements['delivery'] as $element => $data ) {
				$current_settings['delivery'][ $element ] = $data['default'];
			}
		}

		if ( ! isset( $current_settings['misc'] ) ) {
			foreach ( $elements['misc'] as $element => $data ) {
				$current_settings['misc'][ $element ] = $data['default'];
			}
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			wp_send_json_success( $current_settings );
		}

		return $current_settings;

	}

	/**
	 * Get array of all elements.
	 *
	 * @since 2.0.0
	 */
	public function get_combined_elements() {

		$elements = Elements::get_elements();
		wp_send_json_success( $elements );

	}

	/**
	 * Get YML elements array.
	 */
	public function get_elements() {

		$method = "get_{$request['type']}_elements";

		if ( ! method_exists( __NAMESPACE__ . '\\Elements', $method ) ) {
			wp_send_json_error(
				printf(
					/* translators: %s: method name */
					esc_html__( 'Method %s not found.', 'market-exporter' ),
					esc_html( $method )
				),
				'method-not-found'
			);
		}

		$elements = call_user_func( [ __NAMESPACE__ . '\\Elements', $method ] );

		wp_send_json_success( $elements );

	}

	/**
	 * Get YML files.
	 *
	 * @since 2.0.0
	 */
	public function get_files() {

		$filesystem = new FS( 'market-exporter' );
		$upload_dir = wp_upload_dir();

		$result = [
			'files' => $filesystem->get_files(),
			'url'   => trailingslashit( $upload_dir['baseurl'] ) . trailingslashit( 'market-exporter' ),
		];

		wp_send_json_success( $result );

	}

}
