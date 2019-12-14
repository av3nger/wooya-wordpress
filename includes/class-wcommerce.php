<?php
/**
 * Adds a meta box to the product edit screen, that allows to custom configure the plugin on a per product basis.
 *
 * @link       https://wooya.ru
 * @since      2.1.0
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 */

namespace Wooya\Includes;

/**
 * WooCommerce class, provides meta box support for products.
 *
 * @package    Wooya
 * @subpackage Wooya/Includes
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class WCommerce {

	/**
	 * WCommerce constructor.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {

		add_filter ('woocommerce_product_data_tabs', [ $this, 'register_tab' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'display_tab' ] );
		add_action( 'admin_head', [ $this, 'change_tab_icon' ] );

	}

	/**
	 * Register a new tab in the WooCommerce product data meta box.
	 *
	 * @since 2.1.0
	 * @param array $tabs  Tabs array.
	 *
	 * @return array
	 */
	public function register_tab( $tabs ) {

		$tabs['market_exporter'] = [
			'label'    => __( 'Market Exporter', 'market-exporter' ),
			'target'   => 'market_exporter_data',
			'priority' => 70,
		];

		return $tabs;

	}

	/**
	 * Change tab icon.
	 *
	 * @since 2.1.0
	 */
	public function change_tab_icon() {
		?>
		<style>
			#woocommerce-product-data ul.wc-tabs li.market_exporter_options.market_exporter_tab a:before { content: "\f174"; }
		</style>
		<?php
	}

	/**
	 * Tab content.
	 *
	 * @since 2.1.0
	 */
	public function display_tab() {

		//woocommerce_options_panel
		echo '<div id="market_exporter_data" class="panel wc-metaboxes-wrapper hidden">';
		echo '<div class="toolbar toolbar-top"><button type="button" class="button">' . esc_html__( 'Add param', 'market-exporter' ) . '</button></div>';

		?>

		<div class="product_attributes wc-metaboxes ui-sortable">

			<div data-taxonomy="pa_size" class="woocommerce_attribute wc-metabox closed taxonomy pa_size" rel="1">
				<h3 class="">
					<a href="#" class="remove_row delete">Remove</a>
					<div class="handlediv" title="Click to toggle" aria-expanded="true"></div>
					<div class="tips sort"></div>
					<strong class="attribute_name">Размер</strong>
				</h3>
				<div class="woocommerce_attribute_data wc-metabox-content hidden" style="display: none;">
					<table cellpadding="0" cellspacing="0">
						<tbody>
						<tr>
							<td class="attribute_name">
								<label>Name:</label>

								<strong>Размер</strong>
								<input type="hidden" name="attribute_names[0]" value="pa_size">

								<input type="hidden" name="attribute_position[0]" class="attribute_position" value="0">
							</td>
							<td rowspan="3">
								<label>Value(s):</label>
								<select multiple="" data-placeholder="Select terms" class="multiselect attribute_values wc-enhanced-select select2-hidden-accessible enhanced" name="attribute_values[0][]" tabindex="-1" aria-hidden="true">
									<option value="29" selected="selected">75B</option><option value="30">80С</option>								</select><span class="select2 select2-container select2-container--default" dir="ltr" style="width: 100px;"><span class="selection"><span class="select2-selection select2-selection--multiple" aria-haspopup="true" aria-expanded="false" tabindex="-1"><ul class="select2-selection__rendered" aria-live="polite" aria-relevant="additions removals" aria-atomic="true"><li class="select2-selection__choice" title="75B"><span class="select2-selection__choice__remove" role="presentation" aria-hidden="true">×</span>75B</li><li class="select2-search select2-search--inline"><input class="select2-search__field" type="text" tabindex="0" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" role="textbox" aria-autocomplete="list" placeholder="" style="width: 0.75em;"></li></ul></span></span><span class="dropdown-wrapper" aria-hidden="true"></span></span>
								<button class="button plus select_all_attributes">Select all</button>
								<button class="button minus select_no_attributes">Select none</button>
								<button class="button fr plus add_new_attribute">Add new</button>
							</td>
						</tr>
						<tr>
							<td>
								<label><input type="checkbox" class="checkbox" checked="checked" name="attribute_visibility[0]" value="1"> Visible on the product page</label>
							</td>
						</tr>
						<tr>
							<td>
								<div class="enable_variation show_if_variable" style="display: none;">
									<label><input type="checkbox" class="checkbox" name="attribute_variation[0]" value="1"> Used for variations</label>
								</div>
							</td>
						</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>


		<div class="toolbar">
			<span class="expand-close">
				<a href="#" class="expand_all">Expand</a> / <a href="#" class="close_all">Close</a>
			</span>
			<button type="button" class="button save_attributes button-primary">Save attributes</button>
		</div>
		<?php

		woocommerce_wp_text_input( [
			'id'          => 'me_custom_param_name',
			'value'       => get_post_meta( get_the_ID(), 'me_custom_param_name', true ),
			'placeholder' => __( 'Screen size', 'market-exporter' ),
			'label'       => __( 'Name', 'market-exporter' ),
			'desc_tip'    => true,
			'description' => __( 'Param name (for example, Screen size)', 'market-exporter' )
		] );

		woocommerce_wp_text_input( [
			'id'          => 'me_custom_param_value',
			'value'       => get_post_meta( get_the_ID(), 'me_custom_param_value', true ),
			'placeholder' => '27',
			'label'       => __( 'Value', 'market-exporter' ),
			'desc_tip'    => true,
			'description' => __( 'Param value (for example, 27)', 'market-exporter' ),
		] );

		woocommerce_wp_text_input( [
			'id'          => 'me_custom_param_unit',
			'value'       => get_post_meta( get_the_ID(), 'me_custom_param_unit', true ),
			'placeholder' => __( 'Inch', 'market-exporter' ),
			'label'       => __( 'Unit', 'market-exporter' ),
			'desc_tip'    => true,
			'description' => __( 'Param unit value (for example, Inch)', 'market-exporter' ),
		] );

		echo '</div>';

	}

}
