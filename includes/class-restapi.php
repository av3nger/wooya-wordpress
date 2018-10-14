<?php
/**
 * Register WP REST API endpoints
 *
 * @link       https://wooya.ru
 * @since      1.0.0
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 */

namespace Wooya\Includes;

use Wooya\App;

/**
 * Register WP REST API endpoints.
 *
 * This singleton class defines and registers all endpoints needed for React components.
 *
 * @since      1.0.0
 * @package    Wooya
 * @subpackage Wooya/Includes
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class RestAPI extends \WP_REST_Controller {

	/**
	 * Class instance.
	 *
	 * @since 1.0.0
	 * @var   RestAPI|null $instance
	 */
	private static $instance = null;

	/**
	 * API version.
	 *
	 * @since 1.0.0
	 * @var   string $version
	 */
	protected $version = '1';

	/**
	 * Get class instance.
	 *
	 * @since  1.0.0
	 * @return RestAPI|null
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {

		$slug      = App::get_instance()->core->get_plugin_name();
		$namespace = $slug . '/v' . $this->version;

		register_rest_route(
			$namespace,
			'/settings/',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => function() {
						return get_option( 'wooya_settings' );
					},
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				),
			)
		);

		register_rest_route(
			$namespace,
			'/elements/',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_combined_elements' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			$namespace,
			'/elements/(?P<type>[-\w]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_elements' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

	}

	/**
	 * Update settings.
	 *
	 * @param \WP_REST_Request $request  Request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_settings( \WP_REST_Request $request ) {

		$params = $request->get_params();

		$error_data = array(
			'status' => 500,
		);

		if ( ! isset( $params['items'] ) || ! isset( $params['action'] ) ) {
			// No valid action - return error.
			return new \WP_Error(
				'update-error',
				__( 'Either action or items are not defined', 'wooya' ),
				$error_data
			);
		}

		$updated  = false;
		$settings = get_option( 'wooya_settings' );
		$items    = array_map( 'sanitize_text_field', wp_unslash( $params['items'] ) );

		// Remove item from settings array.
		if ( 'remove' === $params['action'] ) {
			foreach ( $params['items'] as $type => $data ) {
				foreach ( $data as $item ) {
					if ( isset( $settings[ $type ][ $item ] ) ) {
						unset( $settings[ $type ][ $item ] );
					}
				}
			}

			$updated = true;
		}

		// Add item to settings array.
		if ( 'add' === $params['action'] ) {
			$elements = YML_Elements::get_elements();

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
			if ( isset( $settings[ $items['type'] ][ $items['name'] ] ) ) {
				$settings[ $items['type'] ][ $items['name'] ] = $items['value'];
			}

			$updated = true;
		}

		if ( $updated ) {
			update_option( 'wooya_settings', $settings );
			return new \WP_REST_Response( true, 200 );
		}

		// No valid action - return error.
		return new \WP_Error(
			'update-error',
			__( 'Unable to update the settings', 'wooya' ),
			$error_data
		);

	}

	/**
	 * Get YML elements array.
	 *
	 * @param \WP_REST_Request $request  Request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_elements( \WP_REST_Request $request ) {

		$method = "get_{$request['type']}_elements";

		if ( ! method_exists( __NAMESPACE__ . '\\YML_Elements', $method ) ) {
			return new \WP_Error(
				'method-not-found',
				printf(
					/* translators: %s: method name */
					esc_html__( 'Method %s not found.', 'wooya' ),
					esc_html( $method )
				)
			);
		}

		$elements = call_user_func( array( __NAMESPACE__ . '\\YML_Elements', $method ) );

		return new \WP_REST_Response( $elements, 200 );

	}

	/**
	 * Get array of all elements.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request  Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_combined_elements( \WP_REST_Request $request ) {

		$elements = YML_Elements::get_elements();
		return new \WP_REST_Response( $elements, 200 );

	}

}
