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
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

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
class RestAPI extends WP_REST_Controller {

	/**
	 * API version.
	 *
	 * @since 2.0.0
	 * @var   string $version
	 */
	protected $version = '1';

	/**
	 * Used in REST API calls.
	 *
	 * @since 2.0.6
	 * @var WP_REST_Request $request  Request parameters
	 */
	protected $request = null;

	/**
	 * YML generator engine.
	 *
	 * @since 2.0.6
	 * @var Generator $generator
	 */
	private $generator;

	/**
	 * RestAPI constructor.
	 *
	 * @since 2.0.6
	 */
	public function __construct( Generator $generator ) {

		$this->generator = $generator;

		add_action( 'wp_ajax_me_settings', [ $this, 'get_settings' ] );
		add_action( 'wp_ajax_me_update_settings', [ $this, 'update_settings' ] );
		add_action( 'wp_ajax_me_elements', [ $this, 'get_elements' ] );
		add_action( 'wp_ajax_me_update_generate', [ $this, 'generate_yml_step' ] );
		add_action( 'wp_ajax_me_files', [ $this, 'get_files' ] );
		add_action( 'wp_ajax_me_update_files', [ $this, 'remove_files' ] );

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
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'check_permissions' ],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => function ( WP_REST_Request $request ) {
						$this->request = $request;
						$this->update_settings();
					},
					'permission_callback' => [ $this, 'check_permissions' ],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/elements/',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_combined_elements' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		register_rest_route(
			$namespace,
			'/elements/(?P<type>[-\w]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => function( WP_REST_Request $request ) {
					$this->request = $request;
					$this->get_elements();
				},
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		register_rest_route(
			$namespace,
			'/generate/',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => function( WP_REST_Request $request ) {
					return new WP_REST_Response( $this->generate_step( $request->get_params() ), 200 );
				},
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		register_rest_route(
			$namespace,
			'/files/',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => function() {
						return new WP_REST_Response( $this->get_files(), 200 );
					},
					'permission_callback' => [ $this, 'check_permissions' ],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => function( WP_REST_Request $request ) {
						$this->request = $request;
						$this->remove_files();
					},
					'permission_callback' => [ $this, 'check_permissions' ],
				],
			]
		);

	}

	/**
	 * Check user permissions.
	 *
	 * @since 2.0.6
	 * @return bool
	 */
	public function check_permissions() {

		wp_verify_nonce( 'wp_rest' );
		return current_user_can( 'manage_options' );

	}

	/**
	 * Get plugin options.
	 *
	 * @since 2.0.6
	 * @return mixed|void
	 */
	private function get_options() {

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
	 * Get settings.
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Ajax response.
			wp_send_json_success( $this->get_options() );
		}

		// REST API response.
		return new WP_REST_Response( $this->get_options(), 200 );

	}

	/**
	 * Manage settings.
	 *
	 * TODO: add validation.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_settings() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && is_null( $this->request ) ) {
			$params = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_STRING );
			$params = json_decode( html_entity_decode( $params ), true );
		} else {
			$params = $this->request->get_params();
		}

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
		$settings = $this->get_options();
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

			if ( 'cron' === $params['items']['name'] ) {
				Helper::update_cron_schedule( $params['items']['value'] );
			}

			wp_send_json_success();
		}

		// No valid action - return error.
		return new WP_Error(
			'update-error',
			__( 'Unable to update the settings', 'market-exporter' ),
			$error_data
		);

	}

	/**
	 * Get YML elements array.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_elements() {

		// Ajax - just return the elements.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && is_null( $this->request ) ) {
			wp_send_json_success( Elements::get_elements() );
		}

		$method = "get_{$this->request['type']}_elements";

		if ( ! method_exists( __NAMESPACE__ . '\\Elements', $method ) ) {
			return new WP_Error(
				'method-not-found',
				printf(
					/* translators: %s: method name */
					esc_html__( 'Method %s not found.', 'market-exporter' ),
					esc_html( $method )
				)
			);
		}

		$elements = call_user_func( [ __NAMESPACE__ . '\\Elements', $method ] );

		return new WP_REST_Response( $elements, 200 );

	}

	/**
	 * Get array of all elements.
	 *
	 * @since 2.0.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_combined_elements() {

		$elements = Elements::get_elements();
		return new WP_REST_Response( $elements, 200 );

	}

	/**
	 * Ajax request. Generate YML step.
	 *
	 * @since 2.0.0
	 */
	public function generate_yml_step() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && is_null( $this->request ) ) {
			$params = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_STRING );
			$params = json_decode( html_entity_decode( $params ), true );
			wp_send_json_success( $this->generate_step( $params ) );
		}

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
	private function generate_step( $params ) {

		if ( ! isset( $params['step'] ) || ! isset( $params['steps'] ) ) {
			// No valid action - return error.
			return new WP_Error(
				'generation-error',
				__( 'Error determining steps or progress during generation', 'market-exporter' ),
				[ 'status' => 500 ]
			);
		}

		return $this->generator->run_step( $params['step'], $params['steps'] );

	}

	/**
	 * Get YML files.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_files() {

		$filesystem = new FS( 'market-exporter' );
		$upload_dir = wp_upload_dir();

		$files = [
			'files' => $filesystem->get_files(),
			'url'   => trailingslashit( $upload_dir['baseurl'] ) . trailingslashit( 'market-exporter' ),
		];

		// Ajax response.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && is_null( $this->request ) ) {
			wp_send_json_success( $files );
		}

		return $files;

	}

	/**
	 * Remove selected files.
	 *
	 * @return WP_Error|void
	 */
	public function remove_files() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && is_null( $this->request ) ) {
			$params = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_STRING );
			$params = json_decode( html_entity_decode( $params ), true );
		} else {
			$params = $this->request->get_params();
		}

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

		wp_send_json_success();

	}

}
