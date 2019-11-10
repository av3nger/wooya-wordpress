<?php
/**
 * Freemius SDK integration module.
 *
 * @link       https://wooya.ru
 * @since      2.0.0
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 */

namespace Wooya\Includes;

use Freemius_Exception;

/**
 * Class Freemius
 */
class Freemius {

	/**
	 * Freemius constructor.
	 */
	public function __construct() {

		// Include Freemius SDK.
		/* @noinspection PhpIncludeInspection */
		require_once WOOYA_PATH . '/freemius/start.php';
		$this->init_fremius();
		// Signal that SDK was initiated.
		do_action( 'wooya_fremius_loaded' );

		$this->init_fremius()->add_filter( 'connect_message_on_update', [ $this, 'connect_message_on_update' ], 10, 6 );
		$this->init_fremius()->add_action( 'after_uninstall', [ $this, 'uninstall_cleanup' ] );

	}

	/**
	 * Init Freemius.
	 *
	 * @since 2.0.0
	 * @return \Freemius
	 * @throws Freemius_Exception  Freemius exception.
	 */
	public function init_fremius() {

		global $wooya_fremius;

		if ( ! isset( $wooya_fremius ) ) {
			$wooya_fremius = fs_dynamic_init(
				[
					'id'                  => '3640',
					'slug'                => 'market-exporter',
					'type'                => 'plugin',
					'public_key'          => 'pk_8e3bfb7fdecdacb5e4b56998fbe73',
					'is_premium'          => true,
					'premium_suffix'      => '',
					// If your plugin is a serviceware, set this option to false.
					'has_premium_version' => true,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					'trial'               => [
						'days'               => 7,
						'is_require_payment' => false,
					],
					'menu'                => [ 'slug' => 'market-exporter' ],
					// Set the SDK to work in a sandbox mode (for development & testing).
					// IMPORTANT: MAKE SURE TO REMOVE SECRET KEY BEFORE DEPLOYMENT.
					'secret_key'          => 'sk_IZrJEoI&q<yjZSQ4)5{W+&0DKo2Py',
				]
			);
		}

		return $wooya_fremius;

	}

	/**
	 * Show opt-in message for current users.
	 *
	 * @since 2.0.0
	 *
	 * @param string $message          Current message.
	 * @param string $user_first_name  User name.
	 * @param string $plugin_title     Plugin title.
	 * @param string $user_login       User login.
	 * @param string $site_link        Link to site.
	 * @param string $freemius_link    Link to Freemius.
	 *
	 * @return string
	 */
	public function connect_message_on_update( $message, $user_first_name, $plugin_title, $user_login, $site_link, $freemius_link ) {

		return sprintf(
			/* translators: %1$s: user name, %2$s: plugin name, %3$s: user login, %4%s: site link, %5$s: Freemius link */
			__( 'Hey %1$s', 'market-exporter' ) . ',<br>' . __( 'Please help us improve %2$s! If you opt-in, some data about your usage of %2$s will be sent to %5$s. If you skip this, that\'s okay! %2$s will still work just fine.', 'market-exporter' ),
			$user_first_name,
			'<b>' . $plugin_title . '</b>',
			'<b>' . $user_login . '</b>',
			$site_link,
			$freemius_link
		);

	}

	/**
	 * Cleanup on uninstall.
	 *
	 * @since 2.0.0 Moved from uninstall.php file
	 */
	public function uninstall_cleanup() {

		// Data from v 1.x.
		delete_option( 'market_exporter_website_name' );
		delete_option( 'market_exporter_company_name' );
		delete_option( 'market_exporter_shop_settings' );
		delete_option( 'market_exporter_version' );
		delete_option( 'market_exporter_notice_hide' );
		delete_option( 'market_exporter_doing_cron' );

		delete_option( 'wooya_settings' );

	}

}
