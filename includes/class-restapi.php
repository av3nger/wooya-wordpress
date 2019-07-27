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
class RestAPI extends Abstract_API {

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
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => function() {
						return $this->settings();
					},
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => function( WP_REST_Request $request ) {
						$this->update_settings( $request->get_params() );
					},
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
				'methods'             => WP_REST_Server::READABLE,
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
				'methods'             => WP_REST_Server::READABLE,
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
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => function( WP_REST_Request $request ) {
					return new WP_REST_Response( $this->generate_step( $request->get_params() ), 200 );
				},
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
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
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => function( WP_REST_Request $request ) {
						$this->remove_files( $request->get_params() );
					},
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				],
			]
		);

	}

	/**
	 * Get YML elements array.
	 *
	 * @param WP_REST_Request $request  Request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_elements( WP_REST_Request $request ) {

		$method = "get_{$request['type']}_elements";

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

}
