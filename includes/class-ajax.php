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
		add_action( 'wp_ajax_me_files', array( $this, 'get_files' ) );
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
