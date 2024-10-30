<?php
/************************************************************************
 * LimeLight Storefront - Wordpress Plugin
 * Copyright (C) 2017 Lime Light CRM, Inc.

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class LimelightAdvanced {

	public $options;

	public function __construct() {

		$this->options  = get_option( 'limelight_advanced' );
		$this->campaign = get_option( 'limelight_campaign' );

		add_action( 'admin_menu',                [ $this, 'menu' ] );
		add_action( 'admin_init',                [ $this, 'settings' ] );
		add_action( 'admin_footer',              [ $this, 'javascript' ] );
		add_action( 'wp_ajax_build_onepage',     [ $this, 'build_onepage' ] );
		add_action( 'wp_ajax_build_threed',      [ $this, 'build_threed' ] );
		add_action( 'wp_ajax_build_edigital',    [ $this, 'build_edigital' ] );
		add_action( 'wp_ajax_build_boltpay',     [ $this, 'build_boltpay' ] );
		add_action( 'wp_ajax_build_amazonpay',   [ $this, 'build_amazonpay' ] );
		add_action( 'wp_ajax_build_square',      [ $this, 'build_square' ] );
		add_action( 'wp_ajax_edigital_products', [ $this, 'edigital_products' ] );
		add_action( 'wp_ajax_build_membership',  [ $this, 'build_membership' ] );
	}

	public function build_onepage() {

		$html = '<div class="row">';
		$ids  = [];

		if ( ! empty( $_POST['ids'] ) ) {
			$ids = $_POST['ids'];
		} else {
			return;
		}

		foreach ( $ids as $id ) {

			$meta     = get_post_meta( $id );
			$quantity = isset( $this->options['onepage_qty'][ $meta['id'][0] ] ) ? $this->options['onepage_qty'][ $meta['id'][0] ] : 1;
			$quantity = "<input id='onepage_qty_{$meta['id'][0]}' name='limelight_advanced[onepage_qty][{$meta['id'][0]}]' type='number' min='1' value='{$quantity}' style='width:50px;float:right;'><span style='float:right'>qty: &nbsp; </span>";

			$html .= $this->fill( $this->page_tpl( 'advanced_onepage_box_header' ), [
				'<!--sku-->'   => ( ! empty( $meta['sku'][0] )  ? $meta['sku'][0] : '' ),
				'<!--name-->'  => ( ! empty( $meta['name'][0] ) ? $meta['name'][0] : '' ),
				'<!--id-->'    => ( ! empty( $meta['id'][0] )   ? $meta['id'][0] : '' ),
				'<!--qty-->'   => $quantity,
				'<!--price-->' => number_format( $meta['price'][0], 2, '.', ',' )
			] );

			if ( $offers = json_decode( $meta['offers'][0], 1 ) ) {

				foreach ( $offers as $offer ) {

					if ( $offer['offer_id'] == $this->campaign['offer'] ) {

						$html .= "<select id='onepage_freq_{$meta['id'][0]}' name='limelight_advanced[onepage_freq][{$meta['id'][0]}]' style='float:left;'>";

						foreach ( $offer['billing_models'] as $k => $freq_name ) {

							if ( ! empty( $this->options['onepage_freq'][ ( string ) $meta['id'][0] ] ) ) {
								$select = $this->options['onepage_freq'][ ( string ) $meta['id'][0] ] == $k ? 'selected="selected"' : '';
							} else {
								$select = '';
							}

							$html  .= "<option value='{$k}' {$select}>{$freq_name}</option>";
						}

						$html .= '</select>';
					}
				}
			}

			if ( $variants = json_decode( $meta['variants'][0], 1 ) ) {

				$i = 0;

				foreach ( $variants as $variant ) {

					foreach ( $variant as $key => $val ) {

						if ( $key == 'name' ) {
							$html .= $val . "<input name='limelight_advanced[onepage_att_name][{$meta['id'][0]}][{$i}]' id='onepage_att_name_{$meta['id'][0]}_{$i}' type='hidden' value='{$val}'>";
						}

						if ( $key == 'children' ) {

							$html .= "<select name='limelight_advanced[onepage_att_value][{$meta['id'][0]}][{$i}]' id='onepage_att_value_{$meta['id'][0]}_{$i}'>";

							foreach ( $val as $child ) {

								if ( ! empty( $this->options['onepage_att_value'][ ( string ) $meta['id'][0] ][ $i ] ) ) {
									$select = $this->options['onepage_att_value'][ ( string ) $meta['id'][0] ][ $i ] == $child ? 'selected="selected"' : '';
								} else {
									$select = '';
								}

								$html .= "<option value='{$child}' {$select}>{$child}</option>";
							}

							$html .= '</select>';
							$i++;
						}
					}
				}
			}

			$html .= "</div>";
		}

		echo "{$html}</div>";
		wp_die();
	}

	public function build_threed() {

		echo $this->fill( $this->page_tpl( 'advanced_threed_area' ), [
			'<!--checked-->' => ( isset( $this->options['threed_sandbox'] ) ? ' checked="checked"' : '' ),
			'<!--api_key-->' => ( isset( $this->options['threed_apikey'] ) ? $this->options['threed_apikey'] : '' )
		] );
		wp_die();
	}

	public function build_boltpay() {

		echo $this->fill( $this->page_tpl( 'advanced_boltpay_area' ), [
			'<!--checked-->' => ( isset( $this->options['boltpay_sandbox'] ) ? ' checked="checked"' : '' ),
			'<!--api_key-->' => ( isset( $this->options['boltpay_apikey'] ) ? $this->options['boltpay_apikey'] : '' ),
			'<!--gateway-->' => ( isset( $this->options['boltpay_gateway'] ) ? $this->options['boltpay_gateway'] : '' )
		] );
		wp_die();
	}

	public function build_amazonpay() {

		$current   = ! empty( $this->options['amazonpay_size'] ) ? $this->options['amazonpay_size'] : 'medium';
		$htm_color = "<select id='amazonpay_color' name='limelight_advanced[amazonpay_color]'>";
		$htm_size  = "<select id='amazonpay_size' name='limelight_advanced[amazonpay_size]'>";

		foreach ( [ 'Gold', 'LightGray', 'DarkGray' ] as $color ) {

			$selected   = ( isset( $this->options['amazonpay_color'] ) && $color == $this->options['amazonpay_color'] ) ? 'selected="selected"' : '';
			$htm_color .= "<option {$selected} value='{$color}'>{$color}</option>";
		}

		foreach ( [ 'small', 'medium', 'large', 'x-large' ] as $size ) {

			$selected  = $size == $current ? 'selected="selected"' : '';
			$htm_size .= "<option {$selected} value='{$size}'>{$size}</option>";
		}

		echo $this->fill( $this->page_tpl( 'advanced_amazonpay_area' ), [
			'<!--color-->'    => "{$htm_color}</select>",
			'<!--size-->'     => "{$htm_size}</select>",
			'<!--sandbox-->'  => ( ! empty( $this->options['amazonpay_sandbox'] )     ? 'checked="checked"' : '' ),
			'<!--client-->'   => ( ! empty( $this->options['amazonpay_client_id'] )   ? $this->options['amazonpay_client_id'] : '' ),
			'<!--merchant-->' => ( ! empty( $this->options['amazonpay_merchant_id'] ) ? $this->options['amazonpay_merchant_id'] : '' )
		] );
		wp_die();
	}

	public function build_square() {
		echo $this->fill( $this->page_tpl( 'advanced_square_area' ), [
			'<!--checked-->'    => isset( $this->options['square_sandbox'] ) ? ' checked="checked"' : '',
			'<!--square_app-->' => isset( $this->options['square_app'] ) ? $this->options['square_app'] : '',
		] );
		wp_die();
	}

	public function build_edigital() {
		echo $this->fill( $this->page_tpl( 'advanced_edigital_area' ), [ '<!--campaigns-->' => $this->get_campaigns() ] );
		wp_die();
	}

	public function build_membership() {
		echo $this->fill( $this->page_tpl( 'advanced_membership_event' ), [ '<!--event_id-->' => $this->options['membership_event'] ] );
		wp_die();
	}

	public function get_campaigns() {

		$campaigns = limelight_curl( [ 'method' => 'campaign_find_active' ] );
		parse_str( $campaigns, $parsed );
		preg_match( '/campaign_name=([^&]*)/', $campaigns, $matches );

		$ids   = explode( ',', $parsed['campaign_id'] );
		$names = isset( $matches[1] ) ? explode( ',', $matches[1] ) : [];
		$html  = '';

		foreach ( $ids as $key => $val ) {

			$name  = urldecode( $names[ $key ] );
			$sel   = ( ! empty( $this->options['edigital_campaign'] ) && $this->options['edigital_campaign'] == $val ) ? 'selected="selected"' : '';
			$html .= "<option value='{$ids[ $key ]}' {$sel}>{$ids[ $key ]}. {$name}</option>";
		}

		return $html;
	}

	public function edigital_products() {

		$options  = '<option>Select Product</option>';
		$advanced = get_option( 'limelight_advanced' );
		$campaign = limelight_curl( [
			'method'      => 'campaign_view',
			'campaign_id' => sanitize_text_field( $_POST['campaign'] )
		], 1 );

		if ( $campaign['response_code'] == '100' ) {

			$ids     = explode( ',', $campaign['product_id'] );
			$names   = explode( ',', $campaign['product_name'] );
			$options = '';

			foreach ( $ids as $key => $val ) {

				$selected = ! empty( $advanced['edigital_product'] ) && $advanced['edigital_product'] == $val ? 'selected="selected"' : '';
				$options .= "<option value='{$val}' {$selected}>{$val}. {$names[ $key ]}</option>";
			}
		}

		echo $options;
		wp_die();
	}

	public function menu() {
		add_submenu_page( 'limelight-admin', 'Advanced Settings', 'Advanced Settings', 'manage_options', 'advanced-settings', [ $this, 'page' ] );
	}

	public function page() {

		$update = ! empty( $_GET['settings-updated'] ) ? true : false;

		echo $this->fill( $this->page_tpl( 'advanced_notification_area' ), [
			'<!--msg-->'  => ( ! empty( $update ) ) ? 'Your Advanced Settings have been updated successfully' : '',
			'<!--type-->' => ( ! empty( $update ) ) ? 'success' : '',
			'<!--disp-->' => ( ! empty( $update ) ) ? 'block' : 'none',
		] );

		settings_fields( 'advanced' );
		do_settings_sections( 'advanced-settings' );
		submit_button();

		echo "<form></div>";
	}

	public function settings() {
		register_setting( 'advanced', 'limelight_advanced',                 [ $this, 'sanitize' ] );
		add_settings_section( 'section_a', 'Advanced Configuration',        [ $this, 'print_section_a' ], 'advanced-settings' );
		add_settings_section( 'section_sep', '',                            [ $this, 'print_seperator' ], 'advanced-settings' );
		add_settings_section( 'section_b', 'Value Added Services',          [ $this, 'print_section_b' ], 'advanced-settings' );
		add_settings_section( 'section_sep2', '',                           [ $this, 'print_seperator' ], 'advanced-settings' );
		add_settings_section( 'section_c', 'Alternative Payment Providers', [ $this, 'print_section_c' ], 'advanced-settings' );
		add_settings_field( 'google_id', 'Google UA-ID #',                  [ $this, 'google_id' ], 'advanced-settings', 'section_a' );
		add_settings_field( 'prospect_product', 'Prospect Product',         [ $this, 'prospect_product' ], 'advanced-settings', 'section_a' );
		add_settings_field( 'default_shipping', 'Default Shipping',         [ $this, 'default_shipping' ], 'advanced-settings', 'section_a' );
		add_settings_field( 'onepage_products', 'OnePage Product(s)',       [ $this, 'onepage_products' ], 'advanced-settings', 'section_a' );
		add_settings_field( 'addtocart_redirect', 'Add-To-Cart Redirect',   [ $this, 'addtocart_redirect' ], 'advanced-settings', 'section_a' );
		add_settings_field( 'https', 'Force HTTPS',                         [ $this, 'checkbox' ], 'advanced-settings', 'section_a', [ 'advanced_https', 'https' ] );
		add_settings_field( 'group_upsells', 'Group Upsells',               [ $this, 'checkbox' ], 'advanced-settings', 'section_a', [ 'advanced_group_upsells', 'group_upsells' ] );
		add_settings_field( 'hide_wp_logo', 'Hide WP Logo',                 [ $this, 'checkbox' ], 'advanced-settings', 'section_a', [ 'advanced_hide_wp_logo', 'hide_wp_logo' ] );
		add_settings_field( 'member_config', 'Membership Config',           [ $this, 'checkbox_member' ], 'advanced-settings', 'section_a', [ 'advanced_membership_config' ] );
		add_settings_field( 'enable_threed', '3D Verify',                   [ $this, 'checkbox' ], 'advanced-settings', 'section_b', [ 'advanced_threed', 'enable_threed' ] );
		add_settings_field( 'enable_kount', 'Kount',                        [ $this, 'checkbox' ], 'advanced-settings', 'section_b', [ 'advanced_kount', 'enable_kount' ] );
		add_settings_field( 'enable_edigital', 'eDigital',                  [ $this, 'checkbox' ], 'advanced-settings', 'section_b', [ 'advanced_edigital', 'enable_edigital' ] );
		add_settings_field( 'enable_altpay', 'Enable',                      [ $this, 'checkbox' ], 'advanced-settings', 'section_c', [ 'advanced_altpay', 'enable_altpay' ] );
		add_settings_field( 'enable_boltpay', 'BoltPay',                    [ $this, 'radio' ], 'advanced-settings', 'section_c', [ 'advanced_boltpay', 'payment_type' ] );
		add_settings_field( 'enable_amazonpay', 'Amazon Pay',               [ $this, 'radio' ], 'advanced-settings', 'section_c', [ 'advanced_amazonpay', 'payment_type' ] );
		add_settings_field( 'enable_square', 'Square',                      [ $this, 'radio' ], 'advanced-settings', 'section_c', [ 'advanced_square', 'payment_type' ] );
	}

	public function print_section_a() {
		print 'Configure your advanced settings & value added services:';
	}

	public function print_section_b() {
		print 'Configure your LimeLight VAS Services:';
	}

	public function print_section_c() {
		print 'Configure an Alternative Payment Provider:';
	}

	public function print_seperator() {
		print '<hr>';
	}

	public function google_id() {
		echo $this->fill( $this->page_tpl( 'advanced_google_id' ), [ '<!--id-->' => $this->options['google_id'] ] );
	}

	public function default_shipping() {

		if ( empty( $this->campaign['shipping_name'] ) ) {
			return;
		}

		$html = "<select id='default-shipping' name='limelight_advanced[default_shipping]'>";

		foreach ( $this->campaign['shipping_name'] as $key => $val ) {

			$id    = $this->campaign['shipping_id'][ $key ];
			$price = $this->campaign['shipping_initial_price'][ $key ];
			$sel   = $this->options['default_shipping'] == $id ? "selected='selected'" : '';
			$html .= "<option value='{$id}' {$sel}>{$val} - \${$price}</option>";
		}

		echo "{$html}</select> &nbsp; <i>This will be the default shipping used throughout your site</i>";
	}

	public function prospect_product() {

		$html  = "<select id='prospect' name='limelight_advanced[prospect]'><option value=''>Select A Product</option>";
		$posts = get_posts( [
			'post_type'   => 'products',
			'numberposts' => -1
		] );

		foreach ( $posts as $post ) {

			$id    = get_post_meta( $post->ID, 'id' );
			$sel   = $this->options['prospect'] == $post->ID ? 'selected="selected"' : '';
			$html .= "<option value='{$post->ID}' {$sel}>{$id[0]}. {$post->post_title}</option>";
		}

		echo "{$html}</select> &nbsp; <i>The <b>Prospect</b> page will give the option to choose a product unless one is specified here</i>";
	}

	public function onepage_products() {

		$html  = "<select id='onepage' name='limelight_advanced[onepage][]' size='10' multiple>";
		$opts  = '';
		$posts = get_posts( [
			'post_type'   => 'products',
			'numberposts' => -1
		] );

		foreach ( $posts as $post ) {

			if ( ! empty( $post->ID ) ) {

				$id    = get_post_meta( $post->ID, 'id' );
				$sel   = isset( $this->options['onepage'] ) && in_array( $post->ID, $this->options['onepage'] ) ? 'selected="selected"' : '';
				$opts .= "<option value='{$post->ID}' {$sel}>{$id[0]}. {$post->post_title}</option>";
			}
		}

		$html .= "{$opts}</select> &nbsp; <i><b><u>Shift+Click</u></b> or <b><u>Ctrl+Click</u></b> to select multiple products</i><div id='onepage-section'></div>";
		echo ! empty( $opts ) ? $html : '';
	}

	public function addtocart_redirect() {

		$html = "<select id='addtocart_redirect' name='limelight_advanced[addtocart_redirect]'>";
		$opts = [
			''                 => 'Same Page',
			'll_cart_page'     => 'Cart',
			'll_checkout_page' => 'Checkout'
		];

		foreach ( $opts as $key => $val ) {

			$sel   = $this->options['addtocart_redirect'] == $key ? 'selected="selected"' : '';
			$html .= "<option value='{$key}' {$sel}>{$val}</option>";
		}

		echo "{$html}</select>";
	}

	public function checked( $name = '' ) {
		return isset( $this->options[ $name ] ) ? 'checked="checked"' : '';
	}

	public function radioed( $name = '', $option = '' ) {
		return $this->options[ $name ] == $option ? 'checked' : '';
	}

	public function javascript() {

		if ( isset( $_GET['page'] ) && $_GET['page'] == 'advanced-settings' ) {

			wp_enqueue_style( 'limelight-admin-grid', '//cdn.jsdelivr.net/npm/bootstrap-v4-grid-only@1.0.0/dist/bootstrap-grid.css' );
			wp_enqueue_script( 'limelight-advanced', plugins_url( '/js/advanced.js' , __FILE__ ) );
		}
	}

	public function fill( $tpl, $bind = [] ) {
		return strtr( $tpl, $bind );
	}

	public function page_tpl( $name ) {

		$file = plugin_dir_path( dirname( __FILE__ ) ) . "/classes/tpl/{$name}.htm";
		$tpl  = fopen( $file, 'r' );
		$htm  = fread( $tpl, filesize( $file ) );
		fclose( $tpl );

		return $htm;
	}

	public function checkbox( $input ) {

		if ( ! empty( $input ) )
			echo $this->fill( $this->page_tpl( $input[0] ), [ '<!--checked-->' => $this->checked( $input[1] ) ] );
	}

	public function radio( $input ) {

		$type = explode( '_', $input[0] );

		if ( ! empty( $input ) )
			echo $this->fill( $this->page_tpl( $input[0] ), [ '<!--checked-->' => $this->radioed( $input[1], $type[1] ) ] );
	}

	public function checkbox_member( $input ) {

		if ( ! empty( $input ) ) {

			$check = 'checked="checked"';

			echo $this->fill( $this->page_tpl( $input[0] ), [
				'<!--checked_1-->' => ! empty( $this->options['member_config']['account'] ) ? $check : '',
				'<!--checked_2-->' => ! empty( $this->options['member_config']['add_products'] ) ? $check : '',
				'<!--checked_3-->' => ! empty( $this->options['member_config']['skip_next'] ) ? $check : '',
				'<!--checked_4-->' => ! empty( $this->options['member_config']['change_model'] ) ? $check : '',
				'<!--checked_5-->' => ! empty( $this->options['member_config']['recurring_quantity'] ) ? $check : '',
				'<!--checked_6-->' => ! empty( $this->options['member_config']['pause_subscription'] ) ? $check : '',
				'<!--checked_7-->' => ! empty( $this->options['member_config']['stop_subscription'] ) ? $check : ''
			] );
		}
	}
}