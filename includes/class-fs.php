<?php
/**
 * The file that defines the filesystem functionality
 *
 * A class that handles file system specific functionality of the plugin.
 *
 * @link       https://wooya.ru
 * @since      0.0.1
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 */

namespace Wooya\Includes;

use WP_Filesystem_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The filesystem class.
 *
 * @since      0.0.1
 * @package    Wooya
 * @subpackage Wooya/Includes
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class FS {

	/**
	 * The ID of this plugin.
	 *
	 * @since  0.0.1
	 * @access private
	 * @var    string $plugin_name  The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * Use WP_Filesystem API.
	 *
	 * @since 2.0.0
	 * @var   bool   $fs_api
	 */
	private $fs_api = false;

	/**
	 * FS constructor.
	 *
	 * @since 0.0.1
	 * @param string $plugin_name  The name of this plugin.
	 */
	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
	}

	/**
	 * Initiate file system for read/write operations.
	 *
	 * @since  0.0.8
	 * @return bool   Return true if everything ok.
	 */
	private function init() {

		// Need to include file.php for cron.
		if ( ! function_exists( 'request_filesystem_credentials' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Check if the user has write permissions.
		$access_type = get_filesystem_method();
		if ( 'direct' === $access_type ) {
			$this->fs_api = true;

			// You can safely run request_filesystem_credentials() without any issues
			// and don't need to worry about passing in a URL.
			$credentials = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, null );

			// Mow we have some credentials, try to get the wp_filesystem running.
			if ( ! WP_Filesystem( $credentials ) ) {
				// Our credentials were no good, ask the user for them again.
				return false;
			}
		} else {
			// Don't have direct write access.
			$this->fs_api = false;
		}

		return true;

	}

	/**
	 * Write YML file to /wp-content/uploads/ dir.
	 *
	 * @since  0.0.1
	 * @since  2.0.0  Add $append and $new params.
	 * @param  string $yml     Variable to display contents of the YML file.
	 * @param  string $date    Yes or No for date at the end of the file.
	 * @param  bool   $append  Append to the end of file? Default: false.
	 * @param  bool   $new     Create a new file by deleting the old one. Only needed for use when $append = true.
	 * @return string          Return the path of the saved file.
	 */
	public function write_file( $yml, $date, $append = false, $new = false ) {

		// If unable to initialize filesystem, quit.
		if ( ! $this->init() ) {
			return false;
		}

		// Get the upload directory and make a ym-export-YYYY-mm-dd.yml file.
		$upload_dir = wp_upload_dir();
		$folder     = trailingslashit( $upload_dir['basedir'] ) . trailingslashit( $this->plugin_name );
		if ( $date ) {
			$filename = 'ym-export-' . date( 'Y-m-d' ) . '.yml.tmp';
		} else {
			$filename = 'ym-export.yml.tmp';
		}

		$file_path = $folder . $filename;

		// Use WP_Filesystem API.
		if ( $this->fs_api ) {
			/**
			 * By this point, the $wp_filesystem global should be working, so let's use it to create a file.
			 *
			 * @var WP_Filesystem_Base $wp_filesystem
			 */
			global $wp_filesystem;

			$this->check_dir_wp_api( $folder );

			// Create the file.
			if ( $append ) {
				// Delete old file.
				if ( $new && $wp_filesystem->exists( $file_path ) ) {
					$wp_filesystem->delete( $file_path );
				}
				$result = @file_put_contents( $file_path, $yml, FILE_APPEND | LOCK_EX );
			} else {
				$result = $wp_filesystem->put_contents( $file_path, $yml, FS_CHMOD_FILE );
			}
		} else {
			$this->check_dir_native( $folder );

			// Create the file.
			if ( $append ) {
				// Delete old file.
				if ( $new && file_exists( $file_path ) ) {
					unlink( $file_path );
				}
				$result = @file_put_contents( $file_path, $yml, FILE_APPEND | LOCK_EX );
			} else {
				$result = @file_put_contents( $file_path, $yml );
			}
		}

		if ( ! $result ) {
			esc_html_e( 'Error uploading file.', 'market-exporter' );
		}

		return $upload_dir['baseurl'] . '/' . $this->plugin_name . '/' . $filename;

	}

	/**
	 * Get a list of generated YML files.
	 *
	 * @since  0.0.8
	 * @return array|bool Returns an array of generated files.
	 */
	public function get_files() {

		// If unable to initialize filesystem, quit.
		if ( ! $this->init() ) {
			return false;
		}

		// Get the upload directory and make a ym-export-YYYY-mm-dd.yml file.
		$upload_dir = wp_upload_dir();
		$folder     = trailingslashit( $upload_dir['basedir'] ) . trailingslashit( $this->plugin_name );

		// Use WP_Filesystem API.
		if ( $this->fs_api ) {
			/**
			 * By this point, the $wp_filesystem global should be working, so let's use it to create a file.
			 *
			 * @var WP_Filesystem_Base $wp_filesystem
			 */
			global $wp_filesystem;

			$this->check_dir_wp_api( $folder );

			return $wp_filesystem->dirlist( $folder, false );
		}

		$this->check_dir_native( $folder );

		$dir = scandir( $folder );
		// Let's form the same array as dirlist provides.
		$structure = [];
		foreach ( $dir as $directory ) {
			if ( '.' === $directory || '..' === $directory ) {
				continue;
			}

			$structure[ $directory ]['name'] = $directory;
		}

		return $structure;

	}

	/**
	 * Delete selected files.
	 *
	 * @since  0.0.8
	 * @param  array $files Array of file names to delete.
	 * @return bool
	 */
	public function delete_files( $files ) {

		// If unable to initialize filesystem, quit.
		if ( ! $this->init() ) {
			return false;
		}

		// Get the upload directory and make a ym-export-YYYY-mm-dd.yml file.
		$upload_dir = wp_upload_dir();
		$folder     = trailingslashit( $upload_dir['basedir'] ) . trailingslashit( $this->plugin_name );

		// Use WP_Filesystem API.
		if ( $this->fs_api ) {
			/**
			 * By this point, the $wp_filesystem global should be working, so let's use it to create a file.
			 *
			 * @var WP_Filesystem_Base $wp_filesystem
			 */
			global $wp_filesystem;

			foreach ( $files as $file ) {
				$wp_filesystem->delete( $folder . $file );
			}
		} else {
			foreach ( $files as $file ) {
				unlink( $folder . $file );
			}
		}

		return true;

	}

	/**
	 * Rename the .tmp file to a .yml file.
	 *
	 * @since 2.0.4
	 * @param string $date  Yes or No for date at the end of the file.
	 */
	public function rename( $date ) {

		// If unable to initialize filesystem, quit.
		if ( ! $this->init() ) {
			return;
		}

		// Get the upload directory and make a ym-export-YYYY-mm-dd.yml file.
		$upload_dir = wp_upload_dir();
		$folder     = trailingslashit( $upload_dir['basedir'] ) . trailingslashit( $this->plugin_name );
		if ( $date ) {
			$filename = 'ym-export-' . date( 'Y-m-d' ) . '.yml';
		} else {
			$filename = 'ym-export.yml';
		}

		$file_path = $folder . $filename;

		// Use WP_Filesystem API.
		if ( $this->fs_api ) {
			/**
			 * By this point, the $wp_filesystem global should be working, so let's use it to create a file.
			 *
			 * @var WP_Filesystem_Base $wp_filesystem
			 */
			global $wp_filesystem;

			$wp_filesystem->move( "{$file_path}.tmp", $file_path, true );
		} else {
			rename( "{$file_path}.tmp", $file_path );
		}

	}

	/**
	 * Check if the directory for yml files exists. Native PHP check.
	 *
	 * @since 2.0.1
	 *
	 * @param string $folder  Folder to check.
	 */
	private function check_dir_native( $folder ) {

		// Check if 'uploads/market-exporter' folder exists. If not - create it.
		if ( ! is_dir( $folder ) ) {
			if ( ! wp_mkdir_p( $folder ) ) {
				esc_html_e( 'Error creating directory.', 'market-exporter' );
			}
		}

	}

	/**
	 * Check if the directory for yml files exists. WP Filesystem check.
	 *
	 * @since 2.0.1
	 *
	 * @param string $folder  Folder to check.
	 */
	private function check_dir_wp_api( $folder ) {

		global $wp_filesystem;

		// Check if 'uploads/market-exporter' folder exists. If not - create it.
		if ( ! $wp_filesystem->exists( $folder ) ) {
			if ( ! $wp_filesystem->mkdir( $folder, FS_CHMOD_DIR ) ) {
				esc_html_e( 'Error creating directory.', 'market-exporter' );
			}
		}

	}

}
