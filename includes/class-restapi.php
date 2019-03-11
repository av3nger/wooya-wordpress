<?php
/**
 * Register WP REST API endpoints
 *
 * @link       https://wooya.ru
 * @since      2.0.0
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
 * @since      2.0.0
 * @package    Wooya
 * @subpackage Wooya/Includes
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class RestAPI extends \WP_REST_Controller {

	/**
	 * Class instance.
	 *
	 * @since 2.0.0
	 * @var   RestAPI|null $instance
	 */
	private static $instance = null;

	/**
	 * API version.
	 *
	 * @since 2.0.0
	 * @var   string $version
	 */
	protected $version = '1';

	/**
	 * Get class instance.
	 *
	 * @since  2.0.0
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
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_settings' ],
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				],
			]
		);

		register_rest_route(
			$namespace,
			'/elements/',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_combined_elements' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			$namespace,
			'/elements/(?P<type>[-\w]+)',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_elements' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			$namespace,
			'/generate/',
			[
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => function () {
						return new \WP_REST_Response( Generator::get_instance()->init(), 200 );
					},
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				],
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'generate_yml_step' ],
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				],
			]
		);

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

		return $current_settings;
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

		$error_data = [
			'status' => 500,
		];

		if ( ! isset( $params['items'] ) || ! isset( $params['action'] ) ) {
			// No valid action - return error.
			return new \WP_Error(
				'update-error',
				__( 'Either action or items are not defined', 'wooya' ),
				$error_data
			);
		}

		$updated  = false;
		$settings = $this->get_settings();
		$items    = array_map( [ $this, 'sanitize_input_value' ], wp_unslash( $params['items'] ) );

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

		if ( ! method_exists( __NAMESPACE__ . '\\Elements', $method ) ) {
			return new \WP_Error(
				'method-not-found',
				printf(
					/* translators: %s: method name */
					esc_html__( 'Method %s not found.', 'wooya' ),
					esc_html( $method )
				)
			);
		}

		$elements = call_user_func( [ __NAMESPACE__ . '\\Elements', $method ] );

		return new \WP_REST_Response( $elements, 200 );

	}

	/**
	 * Get array of all elements.
	 *
	 * @since 2.0.0
	 *
	 * @return \WP_REST_Response
	 */
	public function get_combined_elements() {

		$elements = Elements::get_elements();
		return new \WP_REST_Response( $elements, 200 );

	}

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
	 * Generate YML step.
	 *
	 * @since 2.0.0
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function generate_yml_step() {

		$generator = Generator::get_instance();

		if ( ! $generator->is_running() ) {
			$generator->stop();
			return new \WP_REST_Response( [ 'finish' => true ], 200 );
		}

		$status = $generator->run_step();

		return new \WP_REST_Response( $status, 200 );

	}

}
