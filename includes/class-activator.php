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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

		$version = get_site_option( 'wooya_version' );

		if ( version_compare( WOOYA_VERSION, '2.0.4', '<' ) ) {
			self::upgrade_2_0_4();
		}

		if ( ! $version || WOOYA_VERSION !== $version ) {
			update_site_option( 'wooya_version', WOOYA_VERSION );
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

		foreach ( $elements as $type => $group ) {
			foreach ( $group as $name => $data ) {
				if ( false === $data['required'] ) {
					continue;
				}

				$options[ $type ][ $name ] = $data['default'];
			}
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

		/**
		 * Show settings.
		 */
		$options['shop']['name']    = isset( $old_options['website_name'] ) ? $old_options['website_name'] : get_bloginfo( 'name' );
		$options['shop']['company'] = isset( $old_options['company_name'] ) ? $old_options['company_name'] : get_bloginfo( 'name' );

		/**
		 * Offer settings.
		 */
		if ( isset( $old_options['include_cat'] ) && is_array( $old_options['include_cat'] ) && ! empty( $old_options['include_cat'] ) ) {
			foreach ( $old_options['include_cat'] as $category_id ) {
				$term = get_term_by( 'id', $category_id, 'product_cat' );
				if ( $term ) {
					$options['offer']['include_cat'][] = [
						'value' => $category_id,
						'label' => $term->name,
					];
				}
			}
		}
		if ( isset( $old_options['params'] ) && is_array( $old_options['params'] ) && ! empty( $old_options['params'] ) ) {
			foreach ( $old_options['params'] as $param_id ) {
				$term = wc_get_attribute( $param_id );
				if ( $term ) {
					$options['offer']['params'][] = [
						'value' => str_replace( 'pa_', '', $term->slug ),
						'label' => $term->name,
					];
				}
			}
		}
		if ( isset( $old_options['model'] ) ) {
			$options['offer']['model'] = $old_options['model'];
		}
		if ( isset( $old_options['vendor'] ) ) {
			$options['offer']['vendor'] = $old_options['vendor'];
		}
		if ( isset( $old_options['type_prefix'] ) ) {
			$options['offer']['typePrefix'] = $old_options['type_prefix'];
		}
		if ( isset( $old_options['backorders'] ) ) {
			$options['offer']['backorders'] = $old_options['backorders'];
		}
		if ( isset( $old_options['sales_notes'] ) ) {
			$options['offer']['sales_notes'] = $old_options['sales_notes'];
		}
		if ( isset( $old_options['warranty'] ) ) {
			$options['offer']['warranty'] = $old_options['warranty'];
		}
		if ( isset( $old_options['origin'] ) ) {
			$options['offer']['origin'] = $old_options['origin'];
		}
		if ( isset( $old_options['size'] ) ) {
			$options['offer']['size'] = $old_options['size'];
		}
		if ( isset( $old_options['image_count'] ) ) {
			$options['offer']['image_count'] = $old_options['image_count'];
		}
		if ( isset( $old_options['stock_quantity'] ) ) {
			$options['offer']['stock_quantity'] = $old_options['stock_quantity'];
		}

		/**
		 * Delivery settings.
		 */
		if ( isset( $old_options['delivery'] ) ) {
			$options['delivery']['delivery'] = $old_options['delivery'];
		}
		if ( isset( $old_options['pickup'] ) ) {
			$options['delivery']['pickup'] = $old_options['pickup'];
		}
		if ( isset( $old_options['store'] ) ) {
			$options['delivery']['store'] = $old_options['store'];
		}
		if ( isset( $old_options['delivery_options'] ) ) {
			$options['delivery']['delivery_options'] = $old_options['delivery_options'];
		}
		if ( isset( $old_options['cost'] ) ) {
			$options['delivery']['cost'] = $old_options['cost'];
		}
		if ( isset( $old_options['days'] ) ) {
			$options['delivery']['days'] = $old_options['days'];
		}
		if ( isset( $old_options['order_before'] ) ) {
			$options['delivery']['order_before'] = $old_options['order_before'];
		}

		/**
		 * Misc settings.
		 */
		if ( isset( $old_options['file_date'] ) ) {
			$options['misc']['file_date'] = $old_options['file_date'];
		}
		if ( isset( $old_options['cron'] ) ) {
			$options['misc']['cron'] = $old_options['cron'];
		}
		if ( isset( $old_options['description'] ) ) {
			$options['misc']['description'] = $old_options['description'];
		}
		if ( isset( $old_options['update_on_change'] ) ) {
			$options['misc']['update_on_change'] = $old_options['update_on_change'];
		}

		update_option( 'wooya_settings', $options );
		delete_option( 'market_exporter_shop_settings' );

	}

	/**
	 * Upgrade to 2.0.4 version.
	 *
	 * @since 2.0.4
	 */
	private static function upgrade_2_0_4() {

		$options = get_option( 'wooya_settings' );

		if ( ! isset( $options['misc']['description'] ) ) {
			$options['misc']['description'] = 'default';
		}

		if ( ! isset( $options['misc']['update_on_change'] ) ) {
			$options['misc']['update_on_change'] = false;
		}

		update_option( 'wooya_settings', $options );

	}

}
