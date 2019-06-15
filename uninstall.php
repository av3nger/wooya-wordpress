<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * @link       https://wooya.ru
 * @since      1.0.0
 *
 * @package    Wooya
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Data from v 1.x.
delete_option( 'market_exporter_website_name' );
delete_option( 'market_exporter_company_name' );
delete_option( 'market_exporter_shop_settings' );
delete_option( 'market_exporter_version' );
delete_option( 'market_exporter_notice_hide' );
delete_option( 'market_exporter_doing_cron' );

delete_option( 'wooya_settings' );
