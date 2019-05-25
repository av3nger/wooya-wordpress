<?php
/**
 * Fired during plugin activation/deactivation
 *
 * @link       https://wooya.ru
 * @since      2.0.0
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 */

namespace Wooya\Includes;

/**
 * Fired during plugin activation/deactivation.
 *
 * This class defines all code necessary to run during the plugin's activation/deactivation.
 *
 * @since      2.0.0
 * @package    Wooya
 * @subpackage Wooya/Includes
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class Activator {

	/**
	 * The code that runs during plugin activation.
	 *
	 * @since 2.0.0
	 */
	public static function activate() {

		$options = get_option( 'wooya_settings' );

		if ( ! $options ) {
			$old_options = get_option( 'market_exporter_shop_settings' );

			if ( $old_options ) {
				self::update_from_v1( $old_options );
			} else {
				self::new_install();
			}
		}

	}

	/**
	 * The code that runs during plugin deactivation.
	 *
	 * @since 2.0.0
	 */
	public static function deactivate() {
		// Find out when the last event was scheduled.
		$timestamp = wp_next_scheduled( 'market_exporter_cron' );
		// Unschedule previous event if any.
		wp_unschedule_event( $timestamp, 'market_exporter_cron' );
	}

	/**
	 * Populate settings for a new install.
	 *
	 * @since 2.0.0
	 */
	public static function new_install() {

		$options = [];

		/**
		 * Include the elements class.
		 *
		 * @noinspection PhpIncludeInspection
		 */
		require WOOYA_PATH . 'includes/class-elements.php';
		$elements = Elements::get_elements();

		foreach ( $elements['shop'] as $name => $data ) {
			if ( false === $data['required'] ) {
				continue;
			}

			$options['shop'][ $name ] = $data['default'];
		}

		update_option( 'wooya_settings', $options );

	}

	/**
	 * Update from previous version of Market Exporter with different database structure.
	 *
	 * @since 2.0.0
	 *
	 * @param array $old_options  Previous settings.
	 */
	public static function update_from_v1( $old_options ) {

		$options = [];

		$options['shop']['name']     = isset( $old_options['website_name'] ) ? $old_options['website_name'] : get_bloginfo( 'name' );
		$options['shop']['company']  = isset( $old_options['company_name'] ) ? $old_options['company_name'] : get_bloginfo( 'name' );
		$options['shop']['url']      = get_site_url();
		$options['shop']['platform'] = __( 'WordPress', 'wooya' );
		$options['shop']['version']  = get_bloginfo( 'version' );
		$options['shop']['email']    = get_bloginfo( 'admin_email' );

		update_option( 'wooya_settings', $options );

	}

}
