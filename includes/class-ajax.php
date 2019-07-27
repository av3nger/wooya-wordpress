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
class Ajax extends Abstract_API {

	/**
	 * Ajax constructor.
	 */
	public function __construct() {

		add_action( 'wp_ajax_me_settings', [ $this, 'manange_settings' ] );
		add_action( 'wp_ajax_me_elements', [ $this, 'get_elements' ] );
		add_action( 'wp_ajax_me_generate', [ $this, 'generate_yml_step' ] );
		add_action( 'wp_ajax_me_files', [ $this, 'manage_files' ] );

	}

	/**
	 * Get settings.
	 */
	public function manange_settings() {

		$params = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_STRING );

		// Just getting the settings.
		if ( 'false' === $params ) {
			wp_send_json_success( $this->settings() );
		}

		$params = json_decode( html_entity_decode( $params ), true );
		$this->update_settings( $params );

	}

	/**
	 * Get YML elements array.
	 *
	 * @since 2.0.0
	 */
	public function get_elements() {

		$params = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_STRING );

		// Just getting the elements.
		if ( 'false' === $params ) {
			wp_send_json_success( Elements::get_elements() );
		}

	}

	/**
	 * Generate YML step.
	 *
	 * @since 2.0.0
	 */
	public function generate_yml_step() {

		$params = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_STRING );
		$params = json_decode( html_entity_decode( $params ), true );
		wp_send_json_success( $this->generate_step( $params ) );

	}

	/**
	 * Manage files.
	 *
	 * @since 2.0.0
	 */
	public function manage_files() {

		$params = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_STRING );

		// Just getting the files.
		if ( 'false' === $params ) {
			wp_send_json_success( $this->get_files() );
		}


		$params = json_decode( html_entity_decode( $params ), true );
		$this->remove_files( $params );

	}

}
