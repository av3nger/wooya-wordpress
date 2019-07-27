<?php
/**
 * An abstract layer for REST API and AJAX controllers.
 *
 * @link       https://wooya.ru
 * @since      2.0.0
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 */

namespace Wooya\Includes;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Response;

/**
 * Class Abstract_API extends WP_REST_Controller
 */
abstract class Abstract_API extends WP_REST_Controller {

	/**
	 * Get settings.
	 *
	 * @return array
	 */
	protected function settings() {

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

		return $current_settings;

	}

	/**
	 * Manage settings.
	 *
	 * TODO: add validation.
	 *
	 * @param array $params  Request parameters.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	protected function update_settings( $params ) {

		$error_data = [
			'status' => 500,
		];

		if ( ! isset( $params['items'] ) || ! isset( $params['action'] ) ) {
			// No valid action - return error.
			return new WP_Error(
				'update-error',
				__( 'Either action or items are not defined', 'market-exporter' ),
				$error_data
			);
		}

		$updated  = false;
		$settings = $this->settings();
		$items    = array_map( [ new Helper(), 'sanitize_input_value' ], wp_unslash( $params['items'] ) );

		// Remove item from settings array.
		if ( 'remove' === $params['action'] ) {
			foreach ( $params['items'] as $type => $data ) {
				foreach ( $data as $item ) {
					if ( array_key_exists( $item, $settings[ $type ] ) ) {
						unset( $settings[ $type ][ $item ] );
					}
				}
			}

			$updated = true;
		}

		// Add item to settings array.
		if ( 'add' === $params['action'] ) {
			$elements = Elements::get_elements();

			foreach ( $params['items'] as $type => $data ) {
				foreach ( $data as $item ) {
					// No such setting exists.
					if ( ! isset( $elements[ $type ][ $item ] ) ) {
						continue;
					}

					$settings[ $type ][ $item ] = $elements[ $type ][ $item ]['default'];
				}
			}

			$updated = true;
		}

		// Save setting value.
		if ( 'save' === $params['action'] ) {
			if ( array_key_exists( $items['name'], $settings[ $items['type'] ] ) ) {
				$settings[ $items['type'] ][ $items['name'] ] = $items['value'];
			}

			$updated = true;
		}

		if ( $updated ) {
			update_option( 'wooya_settings', $settings );
			return new WP_REST_Response( true, 200 );
		}

		// No valid action - return error.
		return new WP_Error(
			'update-error',
			__( 'Unable to update the settings', 'market-exporter' ),
			$error_data
		);

	}

	/**
	 * Generate YML step.
	 *
	 * @since 2.0.0
	 *
	 * @param array $params  Request parameters.
	 *
	 * @return array|WP_Error
	 */
	protected function generate_step( $params ) {

		if ( ! isset( $params['step'] ) || ! isset( $params['steps'] ) ) {
			// No valid action - return error.
			return new WP_Error(
				'generation-error',
				__( 'Error determining steps or progress during generation', 'market-exporter' ),
				[ 'status' => 500 ]
			);
		}

		$generator = Generator::get_instance();

		return $generator->run_step( $params['step'], $params['steps'] );

	}

	/**
	 * Get YML files.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_files() {

		$filesystem = new FS( 'market-exporter' );
		$upload_dir = wp_upload_dir();

		return [
			'files' => $filesystem->get_files(),
			'url'   => trailingslashit( $upload_dir['baseurl'] ) . trailingslashit( 'market-exporter' ),
		];

	}

	/**
	 * Remove selected files.
	 *
	 * @param array $params  Request parameters.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	protected function remove_files( $params ) {

		$error_data = [
			'status' => 500,
		];

		if ( ! isset( $params['files'] ) ) {
			// No valid action - return error.
			return new WP_Error(
				'remove-error',
				__( 'No files selected', 'market-exporter' ),
				$error_data
			);
		}

		$filesystem = new FS( 'market-exporter' );

		$status = $filesystem->delete_files( $params['files'] );

		if ( ! $status ) {
			return new WP_Error(
				'remove-error',
				__( 'Error removing files', 'market-exporter' ),
				$error_data
			);
		}

		return new WP_REST_Response( true, 200 );

	}

}
