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

require 'shop.php';
require 'member.php';
require 'products.php';
require 'upsells.php';
require 'catbase.php';

class LimelightClient {

	public
		$campaign,
		$advanced,
		$states,
		$errors,
		$shop,
		$member;

	public function __construct() {

		$this->api            = get_option( 'limelight_api' );
		$this->campaign       = get_option( 'limelight_campaign' );
		$this->offers         = get_option( 'limelight_offers' );
		$this->billing_models = get_option( 'limelight_billing_models' );
		$this->advanced       = get_option( 'limelight_advanced' );
		$this->states         = get_option( 'limelight_states' );
		$this->errors         = get_option( 'limelight_errors' );

		new LimelightProducts();
		new LimelightUpsells();
		new LimelightCatbase();

		$this->shop   = new LimeLightShop();
		$this->member = new LimelightMember();

		add_action( 'wp_head', [ $this, 'get_affiliates' ] );
		add_action( 'wp_head', [ $this, 'logout' ] );

		//scripts
		add_action( 'wp_head',               [ $this, 'frontend_vars' ] );
		add_action( 'wp_enqueue_scripts',    [ $this, 'main_scripts' ] );
		add_action( 'wp_footer',             [ $this, 'variable_scripts' ] );
		add_action( 'wp_footer',             [ $this, 'google_analytics' ] );
		add_action( 'wp_enqueue_scripts',    [ $this, 'traffic_attribution_script' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'traffic_attribution_script' ] );

		//overlays
		add_action( 'wp_head', [ $this, 'loading_overlay' ] );
		add_action( 'wp_head', [ $this, 'error_overlay' ] );

		//areas
		add_filter( 'the_content', [ $this, 'product_area' ] );
		add_filter( 'the_content', [ $this, 'upsell_area' ] );
		add_filter( 'the_content', [ $this, 'archive_area' ] );

		//forms
		add_shortcode( 'limelight_cart',             [ $this, 'cart_form' ] );
		add_shortcode( 'limelight_checkout',         [ $this, 'checkout_form' ] );
		add_shortcode( 'limelight_onepage_checkout', [ $this, 'onepage_checkout_form' ] );
		add_shortcode( 'limelight_account',          [ $this, 'account_page' ] );
		add_shortcode( 'limelight_orders',           [ $this, 'orders_page' ] );
		add_shortcode( 'limelight_subscriptions',    [ $this, 'subscriptions_page' ] );
		add_shortcode( 'limelight_thankyou',         [ $this, 'thankyou_page' ] );
		add_shortcode( 'limelight_login',            [ $this, 'login_form' ] );
		add_shortcode( 'limelight_prospect',         [ $this, 'prospect_form' ] );

		//ajax
		add_action( 'wp_ajax_get_error',                 [ $this, 'get_error' ] );
		add_action( 'wp_ajax_nopriv_get_error',          [ $this, 'get_error' ] );
		add_action( 'wp_ajax_get_item',                  [ $this, 'get_item' ] );
		add_action( 'wp_ajax_nopriv_get_item',           [ $this, 'get_item' ] );
		add_action( 'wp_ajax_prospect_process',          [ $this, 'prospect_process' ] );
		add_action( 'wp_ajax_nopriv_prospect_process',   [ $this, 'prospect_process' ] );
		add_action( 'wp_ajax_boltpay_process',           [ $this, 'boltpay_process' ] );
		add_action( 'wp_ajax_nopriv_boltpay_process',    [ $this, 'boltpay_process' ] );
		add_action( 'wp_ajax_amazonpay_process',         [ $this, 'amazonpay_process' ] );
		add_action( 'wp_ajax_nopriv_amazonpay_process',  [ $this, 'amazonpay_process' ] );
		add_action( 'wp_ajax_square_process',            [ $this, 'square_process' ] );
		add_action( 'wp_ajax_nopriv_square_process',     [ $this, 'square_process' ] );
		add_action( 'wp_ajax_login_process',             [ $this, 'login_process' ] );
		add_action( 'wp_ajax_nopriv_login_process',      [ $this, 'login_process' ] );
		add_action( 'wp_ajax_orders_page',               [ $this, 'orders_page' ] );
		add_action( 'wp_ajax_nopriv_orders_page',        [ $this, 'orders_page' ] );
		add_action( 'wp_ajax_account_page',              [ $this, 'account_page' ] );
		add_action( 'wp_ajax_nopriv_account_page',       [ $this, 'account_page' ] );
		add_action( 'wp_ajax_subscriptions_page',        [ $this, 'subscriptions_page' ] );
		add_action( 'wp_ajax_nopriv_subscriptions_page', [ $this, 'subscriptions_page' ] );
		add_action( 'wp_ajax_field_states',              [ $this, 'field_states' ] );
		add_action( 'wp_ajax_nopriv_field_states',       [ $this, 'field_states' ] );
		add_action( 'wp_ajax_validate_coupon',           [ $this, 'validate_coupon' ] );
		add_action( 'wp_ajax_nopriv_validate_coupon',    [ $this, 'validate_coupon' ] );
		add_action( 'wp_ajax_get_upsell_image',          [ $this, 'get_upsell_image' ] );
		add_action( 'wp_ajax_nopriv_get_upsell_image',   [ $this, 'get_upsell_image' ] );
		add_action( 'wp_ajax_onepage_products',          [ $this, 'onepage_products' ] );
		add_action( 'wp_ajax_nopriv_onepage_products',   [ $this, 'onepage_products' ] );
		add_action( 'wp_ajax_boltpay_order_view',        [ $this, 'boltpay_order_view' ] );
		add_action( 'wp_ajax_nopriv_boltpay_order_view', [ $this, 'boltpay_order_view' ] );
		add_action( 'wp_ajax_check_upsells',             [ $this, 'check_upsells' ] );
		add_action( 'wp_ajax_nopriv_check_upsells',      [ $this, 'check_upsells' ] );

		//subscription actions
		add_action( 'wp_ajax_subscription_skip_next',          [ $this, 'subscription_skip_next' ] );
		add_action( 'wp_ajax_nopriv_subscription_skip_next',   [ $this, 'subscription_skip_next' ] );
		add_action( 'wp_ajax_subscription_quantity',           [ $this, 'subscription_quantity' ] );
		add_action( 'wp_ajax_nopriv_subscription_quantity',    [ $this, 'subscription_quantity' ] );
		add_action( 'wp_ajax_subscription_cancel',             [ $this, 'subscription_cancel' ] );
		add_action( 'wp_ajax_nopriv_subscription_cancel',      [ $this, 'subscription_cancel' ] );
		add_action( 'wp_ajax_subscription_toggle',             [ $this, 'subscription_toggle' ] );
		add_action( 'wp_ajax_nopriv_subscription_toggle',      [ $this, 'subscription_toggle' ] );
		add_action( 'wp_ajax_subscription_frequency',          [ $this, 'subscription_frequency' ] );
		add_action( 'wp_ajax_nopriv_subscription_frequency',   [ $this, 'subscription_frequency' ] );
		add_action( 'wp_ajax_subscription_add_product',        [ $this, 'subscription_add_product' ] );
		add_action( 'wp_ajax_nopriv_subscription_add_product', [ $this, 'subscription_add_product' ] );
		add_action( 'wp_ajax_subscription_next_date',          [ $this, 'subscription_next_date' ] );
		add_action( 'wp_ajax_nopriv_subscription_next_date',   [ $this, 'subscription_next_date' ] );

		//shop
		add_shortcode( 'limelight_shop_name',     [ $this->shop, 'sc_name' ] );
		add_shortcode( 'limelight_shop_address1', [ $this->shop, 'sc_address1' ] );
		add_shortcode( 'limelight_shop_address2', [ $this->shop, 'sc_address2' ] );
		add_shortcode( 'limelight_shop_city',     [ $this->shop, 'sc_city' ] );
		add_shortcode( 'limelight_shop_state',    [ $this->shop, 'sc_state' ] );
		add_shortcode( 'limelight_shop_zip',      [ $this->shop, 'sc_zip' ] );
		add_shortcode( 'limelight_shop_phone',    [ $this->shop, 'sc_phone' ] );
	}

	public function main_scripts() {

		wp_deregister_script( 'jquery' );
		wp_enqueue_script( 'jquery', '//code.jquery.com/jquery-3.3.1.min.js' );

		wp_enqueue_script( 'limelight-js', plugins_url( 'limelight-storefront/assets/js/main.js' ), '', '', 1 );
		wp_enqueue_style( 'limelight-css', plugins_url( 'limelight-storefront/assets/css/main.css' ) );

		wp_register_script( 'limelight-jq-tpl', '//cdnjs.cloudflare.com/ajax/libs/jquery.loadtemplate/1.5.10/jquery.loadTemplate.min.js', '', '', 1 );
		wp_enqueue_script( 'limelight-jq-tpl' );

		wp_enqueue_style( 'limelight-fa', '//use.fontawesome.com/releases/v5.4.1/css/all.css' );

		if ( ! empty( $this->advanced['fallback_css'] ) )
			wp_enqueue_style( 'limelight-fallback', plugins_url( 'limelight-storefront/assets/css/fallback.css' ) );

		if ( $this->advanced['payment_type'] == 'square' )
			wp_enqueue_style( 'limelight-fallback', plugins_url( 'limelight-storefront/assets/css/square.css' ) );
	}

	public function frontend_vars() {

		echo $this->fill( $this->script_tpl( 'frontend_vars' ), [
			'<!--bearer-->'      => base64_encode("{$this->api['user']}:{$this->api['pass']}"),
			'<!--ajax_url-->'    => admin_url( 'admin-ajax.php' ),
			'<!--home_url-->'    => get_home_url(),
			'<!--wp_version-->'  => get_bloginfo( 'version' ),
			'<!--version-->'     => limelight_get_version(),
			'<!--appkey-->'      => $this->api['appkey'],
			'<!--campaign_id-->' => $this->campaign['id'],
			'<!--offer-->'       => ! empty( $this->campaign['offer'] )              ? $this->campaign['offer'] : '',
			'<!--group_ups-->'   => ! empty( $this->advanced['group_upsells'] )      ? $this->advanced['group_upsells'] : '',
			'<!--addtocart-->'   => ! empty( $this->advanced['addtocart_redirect'] ) ? $this->advanced['addtocart_redirect'] : '',
			'<!--cart-->'        => $this->get_page_by_type( 'limelight_cart' ),
			'<!--checkout-->'    => $this->get_page_by_type( 'limelight_checkout' ),
			'<!--onepage-->'     => $this->get_page_by_type( 'limelight_onepage_checkout' ),
			'<!--thankyou-->'    => $this->get_page_by_type( 'limelight_thankyou' ),
			'<!--shop-->'        => get_category_link( get_cat_ID( 'Shop' ) ),
			'<!--ipaddress-->'   => $_SERVER['REMOTE_ADDR'],
			'<!--threed-->'      => $this->advanced['enable_threed'],
			'<!--edigital-->'    => $this->advanced['enable_edigital'],
			'<!--campaignId-->'  => $this->advanced['edigital_campaign'],
			'<!--productId-->'   => $this->advanced['edigital_product'],
			'<!--shippingId-->'  => $this->advanced['default_shipping'],
			'<!--event_id-->'    => $this->advanced['membership_event'],
		] );
	}

	public function variable_scripts( $js = '' ) {

		if ( get_post_type() == 'products' && is_single() ) {
			$js .= $this->script_tpl( 'product' );
		} elseif ( get_post_type() == 'upsells' ) {
			$js .= $this->script_tpl( 'upsell' );
		} else {
			$page_type = $this->page_type();

			if ( $page_type == 'limelight_account' ) {
				$js .= $this->script_tpl( 'account' );
			} elseif ( $page_type == 'limelight_subscriptions' || $page_type == 'limelight_orders' ) {
				$js .= $this->fill( $this->script_tpl( 'pagination' ), [
					'<!--type-->'  => str_replace( 'limelight_', '', $page_type ),
					'<!--login-->' => $this->get_page_by_type( 'limelight_login' )
				] ) . $this->script_tpl( 'subscription' );
			} else {
				$payment = $this->script_tpl( 'coupon' ) . $this->script_tpl( 'creditcard' ) . $this->script_tpl( 'address' );

				switch ( $page_type ) {
					case 'limelight_login':
						$js .= $this->script_tpl( 'login' );
						break;
					case 'limelight_checkout':
					case 'limelight_onepage_checkout':
						$js .= $this->script_tpl( 'checkout' ) . $payment;
						break;
					case 'limelight_prospect':
						$js .= $this->script_tpl( 'prospect' );
						$js .= $this->script_tpl( 'address' );
						break;
					case 'limelight_cart':
						$js .= $this->script_tpl( 'cart' );
						$js .= $this->script_tpl( 'coupon' );
						break;
					case 'limelight_thankyou':
						$js .= $this->script_tpl( 'thankyou' );
						break;
				}
			}
		}
		echo $js;
	}

	public function login_form() {
		return $this->page_tpl( 'login' );
	}

	public function login_process() {

		$email    = sanitize_text_field( $_POST['email'] );
		$pass     = sanitize_text_field( $_POST['pass'] );
		$pass_old = sanitize_text_field( $_POST['pass_old'] );

		if ( ! empty( $_POST['pass_old'] ) ) {

			$response = json_decode( $this->member->update( [
				'email' => $email,
				'old'   => $pass_old,
				'new'   => $pass,
			] ), true );
		}

		$response = json_decode( $this->member->login( [
			'email' => $email,
			'pass'  => $pass,
		] ), true );

		if ( $response['response_code'] == '100' ) {
			$response['redirect'] = $this->redirect( 'limelight_account' );
		}

		echo json_encode( $response );
		wp_die();
	}

	public function logout() {

		if ( isset( $_GET['logout'] ) && $_GET['logout'] == 1 ) {
			echo $this->script_tpl( 'logout' );
		}
	}

	public function upsell_area( $content ) {

		if ( 'upsells' == get_post_type() && is_single() ) {

			$id         = get_the_ID();
			$meta       = get_post_meta( $id );
			$query      = [
				'<!--name-->'     => get_the_title( $id ),
				'<!--cid-->'      => $meta['upsell_campaign'][0],
				'<!--pid-->'      => $meta['upsell_product'][0],
				'<!--att-->'      => $meta['upsell_variant'][0],
				'<!--sid-->'      => $meta['upsell_shipping'][0],
				'<!--oid-->'      => $meta['upsell_offer'][0],
				'<!--bm-->'       => $meta['upsell_freq'][0],
				'<!--qty-->'      => $meta['upsell_qty'][0],
				'<!--price-->'    => ( ! empty( $meta['upsell_price'][0] )  ? $this->currency_format( $meta['upsell_price'][0] ) : '' ),
				'<!--shipping-->' => ( ! empty( $meta['upsell_sprice'][0] ) ? $this->currency_format( $meta['upsell_sprice'][0] ) : '' ),
				'<!--pic-->'      => ( ! empty( get_the_post_thumbnail_url( $id ) ) ) ? get_the_post_thumbnail_url( $id ) : '//dummyimage.com/50x50/f4f4f4/cccccc&text=',
				'<!--wpid-->'     => $id
			];

			$content .= $this->fill( $this->page_tpl( 'upsell' ), $query );
		}

		return $content;
	}

	public function product_area( $content ) {

		$inputs  = $offer = $label = $style = '';
		$html    = $content;
		$post_id = get_the_ID();

		if ( 'products' == get_post_type() && is_single() ) {

			$meta = get_post_meta( $post_id );

			foreach ( $meta as $i => $m ) {

				foreach ( $m as $k => $v ) {

					if ( $i != 'variants' && $i != 'offers' ) {
						$inputs .= "<input type='hidden' name='{$i}' value='{$m[ $k ]}'>";
					}
				}
			}

			if ( $offers = $this->offers ) {

				$bms   = (array) $offers['billing_models'];
				$style = count($bms) == 1 && isset( $bms[2] ) ? "style='display:none;'" : '';

				foreach ($offers['billing_models'] as $id => $name) {
					$offer .= "<option value='{$id}'>{$name}</option>";
				}

				$inputs .= "<select class='form-control mb-3' name='frequency' {$style}>{$offer}</select><input type='hidden' name='offer_id' value='{$offers['offer_id']}'>";
			}

			if ( isset( $meta['variants'][0] ) && $variants = json_decode( $meta['variants'][0], true ) ) {

				$i = 0;

				foreach ( $variants as $variant ) {

					$name = '';

					foreach ( $variant as $key => $item ) {

						if ( $key == 'name' ) {
							$name    = $item;
							$inputs .= "<input type='hidden' name='variant_name_{$i}' value='{$name}'><select class='form-control mb-3' name='variant_value_{$i}'><option value=''>Select {$name}</option>";
						}

						if ( $key == 'children' ) {

							foreach ( $item as $itm ) {
								$inputs .= "<option value='{$itm}'>{$itm}</option>";
							}

							$inputs .= "</select>";
							$i++;
						}
					}
				}
			}

			$inputs .= $this->fill( $this->page_tpl( 'item_additional' ), [
				'<!--link-->' => get_permalink( $post_id ),
				'<!--pic-->'  => ( ! empty( get_the_post_thumbnail_url( $post_id ) ) ? get_the_post_thumbnail_url( $post_id ) : '//dummyimage.com/50x50/f4f4f4/cccccc&text=' )
			] );

			$html   .= $this->fill( $this->page_tpl( 'product' ), [
				'<!--inputs-->' => $inputs,
				'<!--price-->'  => $this->currency_format( $meta['price'][0] )
			] );
		}

		return $html;
	}

	public function archive_area( $content ) {

		$html   = $offer = $label = $style = '';
		$output = $content;

		if ( 'products' == get_post_type() && is_archive() ) {

			$meta = get_post_meta( get_the_ID() );

			foreach ( $meta as $i => $m ) {

				foreach ( $m as $k => $v ) {

					if ( $i != 'variants' && $i != 'offers' ) {
						$html .= "<input type='hidden' name='{$i}' value='{$m[ $k ]}'>";
					}
				}
			}

			if ( $offers = $this->offers ) {

				$bms   = (array) $offers['billing_models'];
				$style = count($bms) == 1 && isset( $bms[2] ) ? "style='display:none;'" : '';

				foreach ($offers['billing_models'] as $id => $name) {
					$offer .= "<option value='{$id}'>{$name}</option>";
				}

				$html .= "<select class='form-control mb-3' name='frequency' {$style}>{$offer}</select><input type='hidden' name='offer_id' value='{$offers['offer_id']}'>";
			}

			if ( isset( $meta['variants'][0] ) && $variants = json_decode( $meta['variants'][0], true ) ) {

				$i = 0;

				foreach ( $variants as $variant ) {

					$name = '';

					foreach ( $variant as $key => $item ) {

						if ( $key == 'name' ) {
							$name  = $item;
							$html .= "<input type='hidden' name='variant_name_{$i}' value='{$name}'><select class='form-control mb-3' name='variant_value_{$i}'><option value=''>Select {$name}</option>";
						}

						if ( $key == 'children' ) {

							foreach ( $item as $itm ) {
								$html .= "<option value='{$itm}'>{$itm}</option>";
							}

							$html .= "</select>";
							$i++;
						}
					}
				}
			}

			$html .= $this->fill( $this->page_tpl( 'item_additional' ), [
				'<!--link-->' => get_permalink( $meta['wp_id'][0] ),
				'<!--pic-->'  => ( ! empty( get_the_post_thumbnail_url( $meta['wp_id'][0] ) ) ? get_the_post_thumbnail_url( $meta['wp_id'][0] ) : '//dummyimage.com/50x50/f4f4f4/cccccc&text=' )
			] );

			$output = $this->fill( $this->page_tpl( 'archive' ), [
				'<!--inputs-->' => $html,
				'<!--price-->'  => $this->currency_format( $meta['price'][0] ),
				'<!--id-->'     => get_the_ID()
			] );
		}

		return $output;
	}

	public function onepage_checkout_form() {

		$bind = [
			'<!--items-->'    => $this->onepage_products(),
			'<!--contact-->'  => $this->form_email_phone(),
			'<!--s_addr-->'   => $this->form_address( 'shipping' ),
			'<!--b_addr-->'   => $this->form_address( 'billing' ),
			'<!--gift-->'     => $this->page_tpl( 'gift' ),
			'<!--cc_form-->'  => $this->form_creditcard(),
			'<!--coupon-->'   => $this->form_coupon(),
			'<!--account-->'  => ! empty( $this->advanced['member_config']['account'] ) ? $this->page_tpl( 'create_account' ) : '',
			'<!--kount-->'    => $this->kount_pixel(),
			'<!--edigital-->' => $this->edigital_area(),
			'<!--submit-->'   => '<input id="ll-checkout-submit" class="btn btn-lg btn-primary btn-block my-3" type="submit" value="Checkout &raquo;">'
		];

		$third_party_script = '';

		if ( isset( $this->advanced['enable_threed'] ) ) {

			$endpoint = 'api';
			$verbose  = 'false';

			if ( isset( $this->advanced['threed_sandbox'] ) ) {

				$endpoint = 'sandbox-api';
				$verbose  = 'true';
			}

			$script = $this->fill( $this->script_tpl( '3d_verify' ), [
				'<!--form_id-->'  => 'll-onepage-checkout-form',
				'<!--apikey-->'   => $this->advanced['threed_apikey'],
				'<!--verbose-->'  => $verbose,
				'<!--endpoint-->' => "https://{$endpoint}.3dsintegrator.com"
			] );

			$bind['<!--3d_verify-->'] = $this->fill( $this->page_tpl( '3d_verify' ), [ '<!--3d_script-->' => $script ] );
			$third_party_script       = '<script src="//cdn.3dsintegrator.com/threeds.min.latest.js"></script>';
		}

		if ( isset( $this->advanced['enable_altpay'] ) ) {

			if ( $this->advanced['payment_type'] == 'boltpay' ) {

				$staging = $key = '';

				$bind['<!--submit-->'] = $this->fill( $this->page_tpl( 'boltpay_submit' ), [
					'<!--form_id-->' => 'll-onepage-checkout-form',
					'<!--mode-->'    => '1',
				] );

				unset( $bind['<!--cc_form-->'], $bind['<!--account-->'] );

				if ( ! empty( $this->advanced['boltpay_sandbox'] ) )
					$staging = '-staging';

				if ( ! empty( $this->advanced['boltpay_apikey'] ) )
					$key = $this->advanced['boltpay_apikey'];

				$third_party_script = $this->fill( $this->page_tpl( 'boltpay' ), [
					'<!--url-->'     => "https://api{$staging}.boltpay.io",
					'<!--key-->'     => $key,
					'<!--form_id-->' => 'll-onepage-checkout-form',
				] );

			} elseif ( $this->advanced['payment_type'] == 'amazonpay' ) {

				unset(
					$bind['<!--cc_form-->'],
					$bind['<!--account-->'],
					$bind['<!--contact-->'],
					$bind['<!--s_addr-->'],
					$bind['<!--b_addr-->'],
					$bind['<!--submit-->']
				);

				$seller_bind = [
					'<!--seller_id-->' => $this->advanced['amazonpay_merchant_id'],
					'<!--type-->'      => 'PwA',
					'<!--color-->'     => ! empty( $this->advanced['amazonpay_color'] ) ? $this->advanced['amazonpay_color'] : 'Gold',
					'<!--size-->'      => ! empty( $this->advanced['amazonpay_size'] ) ? $this->advanced['amazonpay_size'] : 'medium',
				];

				$amazon_button = '<button id="ll-checkout-submit" class="btn btn-lg btn-primary btn-block my-3" type="button" onclick="limelightAmazonProcess( event, \'ll-onepage-checkout-form\' );" disabled>Checkout &raquo;</button>';
				$amazon_inputs = '<input type="hidden" id="access_token" name="access_token" value="" /><input type="hidden" id="billing_agreement_id" name="billing_agreement_id" value="" />';
				$sandbox_mode  = empty( $this->advanced['amazonpay_sandbox'] ) ? '' : 'sandbox/';

				$bind['<!--s_addr-->'] = $this->fill( $this->page_tpl( 'amazonpay' ) )
					. $this->fill( $this->script_tpl( 'amazonpay_client' ), [ '<!--client_id-->' => $this->advanced['amazonpay_client_id'] ] )
					. $this->fill( $this->script_tpl( 'amazonpay_seller' ), $seller_bind )
					. "<script async='async' type='text/javascript' src='//static-na.payments-amazon.com/OffAmazonPayments/us/{$sandbox_mode}js/Widgets.js'></script>"
					. $amazon_button . $amazon_inputs;

			} elseif ( $this->advanced['payment_type'] == 'square' ) {

				unset(
					$bind['<!--cc_form-->'],
					$bind['<!--account-->']
				);

				$third_party_script = $this->fill( $this->page_tpl( 'square' ), [
					'<!--mode-->'    => $this->advanced['square_sandbox'] ? 'sandbox' : '',
					'<!--form_id-->' => 'll-onepage-checkout-form',
					'<!--app_id-->'  => $this->advanced['square_app'],
				] );

				$bind['<!--submit-->'] = $this->third_party_submit();
			}
		}

		return ( ! is_admin() ? $third_party_script . $this->fill( $this->page_tpl( 'onepage_checkout' ), $bind ) : '' );
	}

	public function prospect_form() {
		$binds = [
			'<!--items-->'   => $this->field_all_products(),
			'<!--contact-->' => $this->form_email_phone(),
			'<!--s_addr-->'  => $this->form_address( 'shipping' ),
		];

		return ( ! is_admin() ? $this->fill( $this->page_tpl( 'prospect' ), $binds ) : '' );
	}

	public function thankyou_page() {
		return $this->fill( $this->page_tpl( 'thankyou' ), [
			'<!--heading-->'        => $this->page_tpl( 'thankyou_heading_title' ),
			'<!--row_main-->'       => $this->fill( $this->page_tpl( 'thankyou_heading' ), [ '<!--type-->' => 'main' ] ),
			'<!--row_upsell-->'     => $this->fill( $this->page_tpl( 'thankyou_heading' ), [ '<!--type-->' => 'upsell' ] ),
			'<!--item_title-->'     => $this->page_tpl( 'thankyou_item_title' ),
			'<!--summary_main-->'   => $this->fill( $this->page_tpl( 'thankyou_summary' ), [ '<!--type-->' => 'main' ] ),
			'<!--summary_upsell-->' => $this->fill( $this->page_tpl( 'thankyou_summary' ), [ '<!--type-->' => 'upsell' ] ),
		] ) . $this->page_tpl( 'thankyou_item' );
	}

	public function cart_form() {
		$cart = $this->fill( $this->page_tpl( 'cart' ), [
				'<!--coupon_form-->' => $this->form_coupon(),
				'<!--shop_link-->'   => get_category_link( get_cat_ID( 'Shop' ) )
			] )
			. $this->page_tpl( 'cart_item' )
			. $this->page_tpl( 'matrix' );

		return ( ! is_admin() ? $cart : '' );
	}

	public function checkout_form() {
		$bind = [
			'<!--contact-->'  => $this->form_email_phone(),
			'<!--s_addr-->'   => $this->form_address( 'shipping' ),
			'<!--b_addr-->'   => $this->form_address( 'billing' ),
			'<!--gift-->'     => $this->page_tpl( 'gift' ),
			'<!--cc_form-->'  => $this->form_creditcard(),
			'<!--coupon-->'   => $this->form_coupon(),
			'<!--shipping-->' => $this->field_shippings(),
			'<!--account-->'  => ! empty( $this->advanced['member_config']['account'] ) ? $this->page_tpl( 'create_account' ) : '',
			'<!--kount-->'    => $this->kount_pixel(),
			'<!--edigital-->' => $this->edigital_area(),
			'<!--submit-->'   => '<input id="ll-checkout-submit" class="btn btn-lg btn-primary btn-block my-3" type="submit" value="Checkout &raquo;">'
		];

		$third_party_script = '';

		if ( isset( $this->advanced['enable_threed'] ) ) {

			$endpoint = 'api';
			$verbose  = 'false';

			if ( isset( $this->advanced['threed_sandbox'] ) ) {

				$endpoint = 'sandbox-api';
				$verbose  = 'true';
			}

			$script = $this->fill( $this->script_tpl( '3d_verify' ), [
				'<!--form_id-->'  => 'll-checkout-form',
				'<!--apikey-->'   => $this->advanced['threed_apikey'],
				'<!--verbose-->'  => $verbose,
				'<!--endpoint-->' => "https://{$endpoint}.3dsintegrator.com"
			] );

			$bind['<!--3d_verify-->'] = $this->fill( $this->page_tpl( '3d_verify' ), [ '<!--3d_script-->' => $script ] );
			$third_party_script       = '<script src="//cdn.3dsintegrator.com/threeds.min.latest.js"></script>';
		}

		if ( isset( $this->advanced['enable_altpay'] ) ) {

			if ( $this->advanced['payment_type'] == 'boltpay' ) {

				$staging = $key = '';

				$bind['<!--submit-->'] = $this->fill( $this->page_tpl( 'boltpay_submit' ), [
					'<!--form_id-->' => 'll-checkout-form',
					'<!--mode-->'    => '',
				] );

				unset( $bind['<!--cc_form-->'], $bind['<!--account-->'] );

				if ( ! empty( $this->advanced['boltpay_sandbox'] ) )
					$staging = '-staging';

				if ( ! empty( $this->advanced['boltpay_apikey'] ) )
					$key = $this->advanced['boltpay_apikey'];

				$third_party_script = $this->fill( $this->page_tpl( 'boltpay' ), [
					'<!--url-->'     => "https://api{$staging}.boltpay.io",
					'<!--key-->'     => $key,
					'<!--form_id-->' => 'll-checkout-form',
				] );

			} elseif ( $this->advanced['payment_type'] == 'amazonpay' ) {

				unset(
					$bind['<!--cc_form-->'],
					$bind['<!--account-->'],
					$bind['<!--contact-->'],
					$bind['<!--s_addr-->'],
					$bind['<!--b_addr-->'],
					$bind['<!--submit-->']
				);

				$seller_bind = [
					'<!--seller_id-->' => $this->advanced['amazonpay_merchant_id'],
					'<!--type-->'      => 'PwA',
					'<!--color-->'     => ! empty( $this->advanced['amazonpay_color'] ) ? $this->advanced['amazonpay_color'] : 'Gold',
					'<!--size-->'      => ! empty( $this->advanced['amazonpay_size'] ) ? $this->advanced['amazonpay_size'] : 'medium',
				];

				$amazon_button = '<button id="ll-checkout-submit" class="btn btn-lg btn-primary btn-block my-3" type="button" onclick="limelightAmazonProcess( event, \'ll-checkout-form\' );" disabled>Checkout &raquo;</button>';
				$amazon_inputs = '<input type="hidden" id="access_token" name="access_token" value="" /><input type="hidden" id="billing_agreement_id" name="billing_agreement_id" value="" />';
				$sandbox_mode  = empty( $this->advanced['amazonpay_sandbox'] ) ? '' : 'sandbox/';

				$bind['<!--s_addr-->'] = $this->fill( $this->page_tpl( 'amazonpay' ) )
					. $this->fill( $this->script_tpl( 'amazonpay_client' ), [ '<!--client_id-->' => $this->advanced['amazonpay_client_id'] ] )
					. $this->fill( $this->script_tpl( 'amazonpay_seller' ), $seller_bind )
					. "<script async='async' type='text/javascript' src='//static-na.payments-amazon.com/OffAmazonPayments/us/{$sandbox_mode}js/Widgets.js'></script>"
					. $amazon_button . $amazon_inputs;

			} elseif ( $this->advanced['payment_type'] == 'square' ) {

				unset(
					$bind['<!--cc_form-->'],
					$bind['<!--account-->']
				);

				$third_party_script = $this->fill( $this->page_tpl( 'square' ), [
					'<!--mode-->'    => $this->advanced['square_sandbox'] ? 'sandbox' : '',
					'<!--form_id-->' => 'll-checkout-form',
					'<!--app_id-->'  => $this->advanced['square_app'],
				] );

				$bind['<!--submit-->'] = $this->third_party_submit();
			}
		}

		$html = $third_party_script 
			. $this->fill( $this->page_tpl( 'checkout' ), $bind )
			. $this->page_tpl( 'checkout_item' )
			. $this->page_tpl( 'matrix' );

		return ( ! is_admin() ? $html : '' );
	}

	public function boltpay_process() {

		$post              = [];
		$pids              = [];
		$products          = [];
		$affiliates_matrix = [];
		$gift              = 0;
		$newacc            = 0;

		foreach ( $_POST['checkout'] as $item ) {

			$post[ $item['name'] ] = $item['value'];

			if ( substr( $item['name'], 0, 11 ) === "product_id_" )
				$pids[] = $item['value'];

			if ( stripos( $item['name'], 'affiliates_' ) !== false )
				$affiliate_matrix[ str_replace( 'affiliates_', '', $item['name'] ) ] = $item['value'];

			if ( $item['name'] == 'gift_order' && $item['value'] == 1 )
				$gift = 1;

			if ( $item['name'] == 'account_create' && $item['value'] == 1 )
				$newacc = 1;
		}

		foreach ( $pids as $id ) {

			$products[ $id ] = [
				'offer_id'         => $post[ 'product_offer_id_' . $id ],
				'billing_model_id' => $post[ 'product_billing_model_id_' . $id ],
				'quantity'         => $post[ 'product_qty_' . $id ]
			];

			$meta     = get_post_meta( $this->get_product_by_pid( $id ) );
			$variants = json_decode( $meta['variants_matrix'][0], true );

			foreach ( $variants as $variant ) {

				$match   = false;
				$counter = 0;

				while ( ! empty( $post["product_att_name_{$id}_{$counter}"] ) && ! empty( $post["product_att_value_{$id}_{$counter}"] ) ) {

					if ( strtolower( $variant[ strtolower( $post["product_att_name_{$id}_{$counter}"] ) ] ) == strtolower( $post["product_att_value_{$id}_{$counter}"] ) ) {
						$match = true;
					} else {
						$match = false;
						break;
					}

					$counter++;
				}

				if ( $match ) {
					$products[ $id ]['variant_id'] = $variant['id'];
					break;
				}
			}
		}

		$post['s_addr1'] = ! empty( $post['s_addr2'] ) ? "{$post['s_addr1']} {$post['s_addr2']}" : $post['s_addr1'];

		$query = [
			'transaction_reference'    => sanitize_text_field( $_POST['txn_ref'] ),
			'campaign_id'              => $this->campaign['id'],
			'gateway_id'               => $this->advanced['boltpay_gateway'],
			'ip_address'               => $_SERVER['REMOTE_ADDR'],
			'shipping_id'              => $post['shipping_id'],
			'email'                    => $post['email'],
			'phone'                    => $post['phone'],
			'billing_same_as_shipping' => $post['billing_same'] == '1' ? 'yes' : 'no',
			'billing_first_name'       => $post['s_first'],
			'billing_last_name'        => $post['s_first'],
			'billing_address_1'        => $post['s_addr1'],
			'billing_city'             => $post['s_city'],
			'billing_state'            => $post['s_state'],
			'billing_zip'              => $post['s_zip'],
			'billing_country'          => $post['s_country'],
			'delivery_first_name'      => $post['s_first'],
			'delivery_last_name'       => $post['s_last'],
			'delivery_address_1'       => $post['s_addr1'],
			'delivery_city'            => $post['s_city'],
			'delivery_state'           => $post['s_state'],
			'delivery_zip'             => $post['s_zip'],
			'delivery_country'         => $post['s_country'],
			'products'                 => $products
		];

		if ( $post['billing_same'] != '1' ) {

			$post['b_addr1'] = ( ! empty( $post['b_addr2'] ) ) ? $post['b_addr1'] . ' ' . $post['b_addr2'] : $post['b_addr1'];

			$query['billing_first_name'] = $post['b_first'];
			$query['billing_last_name']  = $post['b_last'];
			$query['billing_address_1']  = $post['b_addr1'];
			$query['billing_city']       = $post['b_city'];
			$query['billing_state']      = $post['b_state'];
			$query['billing_zip']        = $post['b_zip'];
			$query['billing_country']    = $post['b_country'];
		}

		if ( ! empty( $affiliate_matrix ) ) {
			$query = array_merge( $query, $affiliate_matrix );
		}

		if ( $gift ) { $query = array_merge( $query, [
			'gift' => [
				'email'   => $post['gift_email'],
				'message' => $post['gift_msg']
			] ] );
		}

		$response = json_decode( limelight_curl( $query, 0, 0, 'boltpay_api' ), true );
		$response['upsells'] = 'empty';

		if ( $upsells = $this->check_upsells() )
			$response['upsells'] = json_encode( $upsells );

		echo json_encode( $response );
		wp_die();
	}

	public function third_party_submit() {
		return <<<HTML
<div id="form-container">
	<div id="sq-card-number"></div>
	<div class="third" id="sq-expiration-date"></div>
	<div class="third" id="sq-cvv"></div>
	<div class="third" id="sq-postal-code"></div>
	<button id="sq-creditcard" class="button-credit-card" onclick="onGetCardNonce(event)">Checkout</button>
</div>
HTML;
	}

	public function prospect_process() {

		$post = $_POST['prospect'];

		foreach ( $post as $k => $v ) {
			if ( stripos( $k, 'affiliates_' ) !== false )
				$affiliate_matrix[ str_replace( 'affiliates_', '', $k ) ] = $v;
		}

		$notes    = 'Prospect created by appkey:' . $this->api['appkey'] . ' at ' . get_home_url() . ' WP v' . get_bloginfo( 'version' ) . '. Plugin v' . limelight_get_version();
		$shipping = ! empty( $this->advanced['default_shipping'] ) ? $this->advanced['default_shipping'] : $this->campaign['shipping_id'][0];
		$query    = [
			'method'     => 'NewProspect',
			'firstName'  => $post['s_first'],
			'lastName'   => $post['s_last'],
			'address1'   => $post['s_addr1'],
			'address2'   => $post['s_addr2'],
			'city'       => $post['s_city'],
			'state'      => $post['s_state'],
			'zip'        => $post['s_zip'],
			'country'    => $post['s_country'],
			'phone'      => $post['phone'],
			'email'      => $post['email'],
			'ipAddress'  => $_SERVER['REMOTE_ADDR'],
			'productId'  => $post['product_id'],
			'campaignId' => $this->campaign['id'],
			'shippingId' => $shipping,
			'notes'      => "(created-with-wp) {$notes}",
		];

		if ( ! empty( $affiliate_matrix ) )
			$query = array_merge( $query, $affiliate_matrix );

		echo json_encode( limelight_curl( $query, 1, 0, 1 ) );
		wp_die();
	}

	public function account_page() {
		$html = '<div id="ll-member-wrapper">';

		if ( ! empty( $_POST['userinfo'] ) ) {
			$details = $_POST['userinfo'];

			foreach ( $details as $d ) {
				$detail[ $d['name'] ] = $d['value'];
			}

			foreach ( $detail as $key => $value ) {

				if ( $key != 'order_id' && $key != 'campaign_id' && ( strpos( $value, '*' ) === false ) ) {

					$clean_val   = '"' . urlencode( $value ) . '"';
					$actions[]   = $key;
					$values[]    = $clean_val;
					$order_ids[] = $detail['order_id'];
				}
			}

			$v = limelight_curl( [
				'method'    => 'order_update',
				'order_ids' => implode( ",", $order_ids ),
				'actions'   => implode( ",", $actions ),
				'values'    => implode( ",", $values ),
				'sync_all'  => '1'
			], 1, 0 );

			$acs = explode( ',', $actions );

			foreach ( explode( ',', $v['response_code'] ) as $k => $response ) {

				if ( ! in_array( $response, [ '100', '343' ] ) ){
					$bad_fields[] = $acs[ $k ];
				}
			}

			$html .= '<div class="alert alert-warning">';

			if ( $bad_fields ) {

				$html .= 'There was a problem updating the following fields:<ul>';

				foreach ( $bad_fields as $bad_field ) {
					$html .= "<li>{$bad_field}</li>";
				}

				$html .= '</ul>';

			}	else {
				$html .= 'Your account was successfully updated.';
			}

			$html .= '</div>';
		}

		$orders = $this->all_customer_orders( ( ! empty( $_POST['customer_id'] ) ) ? $_POST['customer_id'] : 0 );

		if ( isset( $orders['order_ids'] ) ) {

			$cid  = $this->campaign['id'];
			$oids = explode( ',', $orders['order_ids'] );
			$last = end( $oids );
			$res  = limelight_curl( [
				'method'   => 'order_view',
				'order_id' => $last
			], 1, 0 );

			$html .= $this->fill( $this->page_tpl( 'account' ), [
				'<!--order_id-->'    => $last,
				'<!--campaign_id-->' => $cid,
				'<!--date-->'        => $res['acquisition_date'],
				'<!--customer_id-->' => $res['customer_id'],
				'<!--email-->'       => $res['email_address'],
				'<!--telephone-->'   => $res['customers_telephone'],
				'<!--cc_num-->'      => $res['credit_card_number'],
				'<!--cc_expiry-->'   => $res['cc_expires'],
				'<!--cc_type-->'     => $res['cc_type'],
				'<!--b_first-->'     => $res['billing_first_name'],
				'<!--b_last-->'      => $res['billing_last_name'],
				'<!--b_addr1-->'     => $res['billing_street_address'],
				'<!--b_addr2-->'     => $res['billing_street_address2'],
				'<!--b_city-->'      => $res['billing_city'],
				'<!--b_state-->'     => $res['billing_state'],
				'<!--b_postcode-->'  => $res['billing_postcode'],
				'<!--s_first-->'     => $res['shipping_first_name'],
				'<!--s_last-->'      => $res['shipping_last_name'],
				'<!--s_addr1-->'     => $res['shipping_street_address'],
				'<!--s_addr2-->'     => $res['shipping_street_address2'],
				'<!--s_city-->'      => $res['shipping_city'],
				'<!--s_state-->'     => $res['shipping_state'],
				'<!--s_postcode-->'  => $res['shipping_postcode']
			] );
		}

		$html .= '</div>';

		if ( isset( $_POST['aj'] ) ) {
			echo $html;
			wp_die();
		} else {
			return $html;
		}
	}

	public function orders_page() {

		$html   = '<div id="ll-member-wrapper">';
		$det    = '';
		$curr   = ! empty( $_POST['page_num'] ) && $_POST['page_num'] != 0 ? (int) $_POST['page_num'] : 1;
		$per    = ! empty( $_POST['per_page'] ) && $_POST['per_page'] != 0 ? (int) $_POST['per_page'] : 7;
		$start  = $per * ( $curr - 1 );
		$result = $this->all_customer_orders( ( ! empty( $_POST['customer_id'] ) ) ? $_POST['customer_id'] : 0 );

		if ( $ids = isset( $result['order_ids'] ) ? $result['order_ids'] : '' ) {

			$ids = array_reverse( explode( ',', $ids ) );

			foreach ( array_slice( $ids, $start, $per ) as $order_id ) {

				$products = '';
				$order    = limelight_curl( [
					'method'   => 'order_view',
					'order_id' => $order_id
				], 1, 0 );

				foreach ( $order['products'] as $p ) {

					$recurring = ( $p['recurring_date'] != '0000-00-00' ) ? true : false;
					$price     = $this->currency_format( $p['next_subscription_product_price'] );
					$wp_id     = $this->get_product_by_pid( $p['product_id'] );
					$products .= $this->fill( $this->page_tpl( 'orders_item' ), [
						'<!--name-->'        => $p['name'],
						'<!--sku-->'         => $p['sku'],
						'<!--qty-->'         => $p['product_qty'],
						'<!--price-->'       => $this->currency_format( $p['price'] ),
						'<!--image-->'       => ( ! empty( get_the_post_thumbnail_url( $wp_id ) ) ) ? get_the_post_thumbnail_url( $wp_id ) : '//dummyimage.com/82x82/f4f4f4/cccccc&text=',
						'<!--recur_date-->'  => ( $recurring ) ? "Recurs:<br>" . date( "M j, Y", strtotime( $p['recurring_date'] ) ) : '',
						'<!--recur_price-->' => ( $recurring ) ? "(\${$price})" : ''
					] );
				}

				$det .= $this->fill( $this->page_tpl( 'orders_item_head' ), [
					'<!--order_id-->' => $order_id,
					'<!--date-->'     => str_replace( ' - ', '<br>', date( "F j, Y - g:i a", strtotime( $order['acquisition_date'] ) ) ),
					'<!--total-->'    => $this->currency_format( $order['order_total'] ),
					'<!--products-->' => $products
				] );
			}

			$html .= $this->fill( $this->page_tpl( 'orders' ), [ '<!--details-->' => $det ] );
			$html .= $this->pagination( $ids, $curr, $per );
		}

		$html .= '</div>';

		if ( isset( $_POST['aj'] ) ) {
			echo $html;
			wp_die();
		} else {
			return $html;
		}
	}

	public function subscriptions_page() {

		$html        = '<div id="ll-member-wrapper">';
		$prev_order  = '';
		$curr        = ! empty( $_POST['page_num'] ) && $_POST['page_num'] != 0 ? (int) $_POST['page_num'] : 1;
		$per         = ! empty( $_POST['per_page'] ) && $_POST['per_page'] != 0 ? (int) $_POST['per_page'] : 7;
		$start       = $per * ( $curr - 1 );
		$customer_id = ! empty( $_POST['customer_id'] ) ? (int) $_POST['customer_id'] : 0;
		$results     = limelight_curl( [
			'method'          => 'order_find',
			'campaign_id'     => $this->campaign['id'],
			'start_date'      => '04/19/1983',
			'end_date'        => date( 'm/d/Y' ),
			'criteria'        => [ 'customer_id' => $customer_id ],
			'return_type'     => 'order_view',
			'search_type'     => 'all',
			'return_variants' => '1',
		], 0, 0, 0, 1 );

		$results = json_decode( $results['body'], true );

		if ( empty( $results ) ) {

			$no_results = 'No Subscriptions Found';

			if ( isset( $_POST['aj'] ) ) {
				echo $no_results;
				wp_die();
			} else {
				return $no_results;
			}

		} else {

			$count        = 0;
			$main_product = false;
			$campaign     = limelight_curl( [
				'method'      => 'campaign_view',
				'campaign_id' => $this->campaign['id'],
			], 0, 0, 0, 1 );

			$campaign_results     = json_decode( $campaign['body'], true );
			$upsell_options       = $campaign_results['products'];
			$ninja_upsell_options = '';

			foreach ( $results as $key => $value ) {

				if ( $key == 'data' ) {

					foreach ( $value as $current ) {

						foreach ( $current['products'] as $i => $product ) {

							if ( ! empty( $product['subscription_id'] && ! empty( $product['subscription_desc'] ) ) ) {

								$product['recurring_status'] = (int) ( $product['is_recurring'] == '1' && $product['on_hold'] == '0' );

								if ( $prev_order == $current['order_id'] ) {
									$title = '';
								} else {
									$title      = "<h1>{$current['order_id']} - {$current['acquisition_date']}</h1>";
									$prev_order = $current['order_id'];
									$count++;
								}

								$recurring[ $product['subscription_id'] ]                     = $product;
								$recurring[ $product['subscription_id'] ]['order_id']         = $current['order_id'];
								$recurring[ $product['subscription_id'] ]['order_index']      = $count;
								$recurring[ $product['subscription_id'] ]['recurring_status'] = (int) ( $product['is_recurring'] == '1' && $product['on_hold'] == '0' );

								if ( $main_product = ( $i == 0 ) ) {

									if ( $product['next_subscription_product_id'] != '0' ) {

										foreach ( $upsell_options AS $k => $v) {

											if ( ! in_array( $v['product_id'], array_column( $current['products'], 'product_id' ) ) ) {
												$ninja_upsell_options .= "<option value='{$v['product_id']}'>{$v['product_name']}</option>";
											}
										}
									}
								}
							}
						}
					}
				}
			}

			$recurring  = ! empty( $recurring ) ? array_reverse( $recurring ) : [];
			$prev_order = '';

			foreach ( array_slice( $recurring, $start, $per ) as $key => $product ) {

				$freq_name = '';
				$sub_id    = substr( $product['subscription_id'], -7 );
				$title     = $this->fill( $this->page_tpl( 'subscriptions_heading' ), [ '<!--order_no-->' => $product['order_index'] ] );

				if ( $prev_order == $product['order_id'] ) {
					$title = '';
				}

				$prev_order    = $product['order_id'];
				$order_id      = $product['order_id'];
				$toggle_button = $product['recurring_status'] > 0 ? '<i class="fas fa-stop"></i> &nbsp; Stop' : '<i class="fas fa-play"></i> &nbsp; Start';
				$footer        = $this->fill( $this->page_tpl( 'subscriptions_actions' ), [
					'<!--order_id-->'                   => $product['order_id'],
					'<!--product_id-->'                 => $product['product_id'],
					'<!--subscription_id-->'            => $product['subscription_id'],
					'<!--product_qty-->'                => $product['product_qty'],
					'<!--recur_date-->'                 => $product['recurring_date'],
					'<!--recurring_status-->'           => $product['recurring_status'],
					'<!--display_toggle-->'             => ! empty( $this->advanced['member_config']['stop_subscription'] ) ? 'block' : 'none',
					'<!--recurring_toggle-->'           => $toggle_button,
					'<!--sub_action_select_products-->' => $ninja_upsell_options
				] );

				$wp_id      = $this->get_product_by_pid( $product['product_id'] );
				$meta       = get_post_meta( $wp_id );
				$offer      = json_decode( $meta['offers'][0], true );
				$offer_html = 'Not Available';

				if ( ! empty( $offer[ $this->campaign['offer'] ]['billing_models'] ) ) {

					$offer_html = "<select id='ll-frequency-{$product['subscription_id']}' class='form-control-sm btn-outline-secondary mt-4 sub_action_select_bm_id'><option>Select Frequency</option>";

					foreach ( $offer[ $this->campaign['offer'] ]['billing_models'] as $billing_model_key => $model ) {

						if ( $billing_model_key == $product['billing_model']['id'] ) {
							$freq_name = $model;
						}
						$offer_html .= "<option value='{$billing_model_key}'>{$model}</option>";
					}
					$offer_html .= '</select>';
				}

				$actions     = ! empty( $product['subscription_desc'] ) ? 'block' : 'none';
				$change_freq = ! empty( $this->advanced['member_config']['change_model'] ) ? 'block' : 'none';
				$change_next = ! empty( $this->advanced['member_config']['skip_next'] ) ? 'block' : 'none';

				if ( $product['next_subscription_product_id'] ) {

					$add_product = '';

					if ( ! empty( $ninja_upsell_options ) && ! empty( $title ) && ! empty( $this->advanced['member_config']['add_products'] ) ) {

						$add_product = $this->fill( $this->page_tpl( 'subscriptions_add_product' ), [
							'<!--order_id-->'                   => $order_id,
							'<!--avail_freqs-->'                => str_replace( 'll-frequency-', 'll-frequency-add-', $offer_html ),
							'<!--sub_action_select_products-->' => $ninja_upsell_options,
						] );
					}

					$attributes = '';

					if ( ! empty( $product['variant']['attributes'] ) ) {

						foreach ( $product['variant']['attributes'] as $att ) {
							$attributes .= "{$att['name']}: {$att['value']}<br>";
						}

						$attributes = "<small>{$attributes}</small>";
					}

					$variants = json_encode( $product['variant'] );
					$sku      = ! empty( $product['variant']['sku'] ) ? $product['variant']['sku'] : ( ! empty( $product['sku'] ) ? $product['sku'] : '' );
					$price    = ! empty( $product['variant']['price'] ) ? $product['variant']['price'] : ( ! empty( $product['price'] ) ? $product['price'] : '' );
					$html    .= $title . $add_product . $this->fill( $this->page_tpl('subscriptions_item'), [
						'<!--pic-->'             => ! empty( get_the_post_thumbnail_url( $wp_id ) ) ? get_the_post_thumbnail_url( $wp_id ) : '//dummyimage.com/82x82/f4f4f4/cccccc&text=',
						'<!--name-->'            => ! empty( $product['name'] ) ? $product['name'] : '',
						'<!--product_id-->'      => ! empty( $product['product_id'] ) ? $product['product_id'] : '',
						'<!--product_qty-->'     => ! empty( $product['product_qty'] ) ? $product['product_qty'] : '',
						'<!--subscription_id-->' => ! empty( $product['subscription_id'] ) ? $product['subscription_id'] : '',
						'<!--recurring_date-->'  => date( 'm/d/Y', strtotime( $product['recurring_date'] ) ),
						'<!--sku-->'             => ! empty( $product['variant']['sku'] ) ? $product['variant']['sku'] : $sku,
						'<!--price-->'           => $this->currency_format( $product['variant']['price'] != 0 ? $product['variant']['price'] : $price ),
						'<!--freq-->'            => $freq_name,
						'<!--order_id-->'        => $order_id,
						'<!--avail_freqs-->'     => $offer_html,
						'<!--change_freq-->'     => $change_freq,
						'<!--change_next-->'     => $change_next,
						'<!--actions_display-->' => $actions,
						'<!--attributes-->'      => $attributes,
						'<!--next_recurring-->'  => ( ! empty( $product['next_subscription_product_id'] ) ? $product['next_subscription_product_id'] : '' ),
					] ) . $footer . '<hr>';
				}
			}

			$output = $html . $this->pagination( $recurring, $curr, $per ) . '</div>';

			if ( isset( $_POST['aj'] ) ) {
				echo $output;
				wp_die();
			} else {
				return $output;
			}
		}
	}

	public function page_type() {
		return get_post_meta( get_the_ID(), 'll_page_type', true );
	}

	public function all_customer_orders( $id = 0 ) {

		if ( $id != 0 ) {

			return limelight_curl( [
				'method'      => 'order_find',
				'campaign_id' => $this->campaign['id'],
				'start_date'  => '04/19/1983',
				'end_date'    => date( 'm/d/Y' ),
				'criteria'    => "customer_id={$id}"
			], 1, 0 );
		}
	}

	public function pagination( $ids, $curr, $per ) {

		$total   = count( $ids );
		$pages   = intval( ceil( $total / $per ) ) ? : 1;
		$options = "<option value='1' selected>1</option>";

		if ( $pages > 1 ) {

			$options = '';

			for ( $counter = 1; $counter <= $pages; $counter++ ) {

				$select = '';

				if ( $curr == $counter ) {
					$select = 'selected';
				}

				$options .= "<option value=\"{$counter}\" {$select}>{$counter}</option>";
			}
		}

		return $this->fill( $this->page_tpl( 'pagination' ), [
			'<!--per_page-->'     => $per,
			'<!--total_orders-->' => $total,
			'<!--total_pages-->'  => $pages,
			'<!--options-->'      => $options
		] );
	}

	public function get_page_by_type( $t ) {

		$p = get_posts( [
			'post_type'      => 'page',
			'meta_key'       => 'll_page_type',
			'meta_value'     => $t,
			'post_status'    => 'any',
			'posts_per_page' => 1
		] );

		return isset( $p[0]->ID ) ? get_permalink( $p[0]->ID ) : '';
	}

	public function get_product_by_pid( $id ) {

		$p = get_posts( [
			'post_type'      => 'products',
			'meta_key'       => 'id',
			'meta_value'     => $id,
			'post_status'    => 'any',
			'posts_per_page' => 1
		] );

		return isset( $p[0]->ID ) ? $p[0]->ID : '';
	}

	public function google_analytics() {

		echo $this->fill( $this->script_tpl( 'google_analytics' ), [
			'<!--google_id-->'  => $this->advanced['google_id'],
			'<!--dimension1-->' => $this->api['appkey'],
			'<!--dimension2-->' => $this->campaign['id'],
			'<!--dimension3-->' => get_bloginfo( 'version' ),
			'<!--dimension4-->' => limelight_get_version(),
			'<!--dimension5-->' => get_home_url(),
			'<!--user_id-->'    => "{$this->api['appkey']}:{$this->campaign['id']}"
		] );
	}

	public function traffic_attribution_script() {
		wp_register_script( 'limelight-traffic-attribution-js', plugins_url( 'limelight-storefront/assets/js/limelight-traffic-attribution.min.js' ), [], limelight_get_version(), true );
		wp_enqueue_script( 'limelight-traffic-attribution-js' );
	}

	public function get_affiliates() {

		$affiliates      = [];
		$affiliates_sent = 0;
		$get = array_change_key_case( $_GET, CASE_LOWER );
		$aff = [
			'AFID',
			'SID',
			'AFFID',
			'C1',
			'C2',
			'C3',
			'AID',
			'BID',
			'CID',
			'OPT',
			'ClickID',
			'click_id'
		];

		foreach ( $aff as $a ) {

			$x = strtolower( $a );

			if ( isset( $get[ $x ] ) ) {

				$affiliates[ $a ] = $get[ $x ];
				$affiliates_sent  = 1;
			}
		}

		$affiliates = json_encode( $affiliates );

		if ( $affiliates_sent ) {
			echo $this->fill( $this->script_tpl( 'affiliates' ), [ '<!--affiliates-->' => $affiliates ] );
		}
	}

	public function redirect( $name = '', $id = 0 ) {

		if ( ! empty ( $name ) ) {
			$loc = $this->get_page_by_type( $name );
		} elseif ( $id > 0 ) {
			$loc = get_permalink( $id );
		}

		return $this->fill( $this->script_tpl( 'redirect' ), [ '<!--where-->' => $loc ] );
	}

	public function fill( $tpl, $bind = [] ) {
		return strtr( $tpl, $bind );
	}

	public function page_tpl( $name ) {

		$file    = plugin_dir_path( dirname( __FILE__ ) ) . "/assets/tpl/htm/{$name}.htm";
		$tpl     = fopen( $file, "r" );
		$content = fread( $tpl, filesize( $file ) );
		fclose( $tpl );

		return $content;
	}

	public function script_tpl( $name ) {

		$file    = plugin_dir_path( dirname( __FILE__ ) ) . "/assets/tpl/js/{$name}.js";
		$tpl     = fopen( $file, "r" );
		$content = fread( $tpl, filesize( $file ) );
		fclose( $tpl );

		return "<script type='text/javascript'>{$content}</script>";
	}

	public function loading_overlay() {
		echo $this->fill( $this->page_tpl( 'loading_overlay_css' ), [
			'<!--img-->' => plugins_url( 'limelight-storefront/assets/img/loading.gif' )
		] ) . $this->page_tpl( 'loading_overlay_div' );
	}

	public function error_overlay() {
		echo $this->page_tpl( 'error_overlay_css' ) . $this->page_tpl( 'error_overlay_div' );
	}

	public function field_card_month() {

		$html = "<select class='form-control mr-2' id='ll-expiry-month' name='expiry_month'>";

		for ( $counter = 1; $counter <= 12; $counter++ ) {

			$value = str_pad( $counter, 2, "0", STR_PAD_LEFT );
			$name  = date( 'F', mktime( 0, 0, 0, $counter, 10 ) );
			$html .= "<option value='{$value}'>{$value} - {$name}</month>";
		}

		return "{$html}</select>";
	}

	public function field_card_year() {

		$year = date( 'y' );
		$html = "<select class='form-control mr-2' id='ll-expiry-year' name='expiry_year'>";

		for ( $counter = 0; $counter <= 25; $counter++ ) {

			$value = $year + $counter;
			$html .= "<option value='{$value}'>20{$value}</month>";
		}

		return "{$html}</select>";
	}

	public function field_card_types() {

		if ( empty( $this->campaign['payment_name'] ) ) {
			return 'Alternative Payment Provider';
		}

		$html = "<select class='form-control col-sm-5' id='ll-cc-type' name='cc_type'>";

		foreach ( $this->campaign['payment_name'] as $k => $v ) {
			$html .= "<option value='{$k}'>{$v}</option>";
		}

		return "{$html}</select>";
	}

	public function field_states( $type ) {

		$matrix  = $this->country_matrix();
		$type    = $type ? : $_POST['address_type'];
		$country = isset( $_POST['country'] ) ? $_POST['country'] : '';

		if ( ! $country ) {
			$html = "<input class='form-control mr-2' type='text' value='' placeholder='State' id='ll-{$type}-state' name='{$type[0]}_state'>";
		} else {

			$html = "<select class='form-control mr-2' id='ll-{$type}-state' name='{$type[0]}_state'><option value=''>Select State</option>";

			foreach ( $matrix[ $country ]['states'] as $k => $state ) {
				$html .= "<option value='{$state}'>{$k}</option>";
			}
			$html .= '</select>';
		}

		if ( isset( $_POST['aj'] ) ) {
			echo $html;
			wp_die();
		} else {
			return $html;
		}
	}

	public function field_countries( $prefix ) {

		$matrix = $this->country_matrix();
		$html   = "<select class='form-control mr-2' id='ll-{$prefix}-country' name='{$prefix[0]}_country'>";
		$html  .= "<option value=''>Select Country</option>";

		foreach ( $this->campaign['countries'] as $x ) {
			$html .= "<option value='{$x}'>{$matrix[ $x ]['name']}</option>";
		}

		return "{$html}</select>";
	}

	public function country_matrix() {

		if ( ( $handle = fopen( plugins_url( 'limelight-storefront/assets/limelight_country_state_code_map.csv' ), 'r' ) ) !== FALSE ) {

			while ( ( $d = fgetcsv( $handle, 1000, "," ) ) !== FALSE ) {

				$limit = count( $d );

				for ( $counter = 0; $counter < $limit; $counter++ ) {
					$matrix[ $d[1] ]['name']            = $d[0];
					$matrix[ $d[1] ]['states'][ $d[2] ] = $d[3];
				}
			}

			fclose( $handle );
		}

		return $matrix;
	}

	public function field_shippings() {

		$html = "<select class='form-control' id='ll-shipping-id' name='shipping_id'>";

		foreach ( $this->campaign['shipping_name'] as $k => $v ) {

			$id     = $this->campaign['shipping_id'][ $k ];
			$price  = $this->campaign['shipping_initial_price'][ $k ];
			$html  .= "<option value='{$id}'>{$v} - \${$price}</option>";
		}

		return "{$html}</select><input id='ll-shipping-price' name='shipping_cost' type='hidden'>";
	}

	public function field_all_products() {

		if ( ! empty( $prospect = $this->advanced['prospect'] ) ) {

			$id   = get_post_meta( $prospect, 'id' );
			$html = "<input type='hidden' id='ll-product-id' name='product-id' value='{$id[0]}'>";

		} else {

			$products = get_posts( [
				'post_type'   => 'products',
				'numberposts' => -1
			] );

			$html = "<select class='form-control mb-3' id='ll-product-id' name='product_id'>";

			foreach ( $products as $p ) {

				$id     = get_post_meta( $p->ID, 'id' );
				$select = ( isset( $this->options['prospect'] ) && $this->options['prospect'] == $p->ID ) ? ' selected="selected"' : '';
				$html  .= "<option value='{$id[0]}' {$select}>{$id[0]}. {$p->post_title}</option>";
			}

			$html .= "</select>";
		}

		return $html;
	}

	public function onepage_products( $thankyou = 0 ) {

		$html        = '';
		$matrix      = '';
		$advanced    = $this->advanced;
		$campaign    = $this->campaign;
		$class       = 'class="text-right"';
		$grand_total = 0;

		if ( empty( $advanced['onepage'] ) ) {
			return;
		}

		foreach ( $advanced['onepage'] as $z ) {

			$meta      = get_post_meta( $z );
			$img       = has_post_thumbnail( $z )                       ? get_the_post_thumbnail( $z, [ 50, 50 ] ) : "<img src='//dummyimage.com/50x50/f4f4f4/cccccc&amp;text='>";
			$id        = isset( $meta['id'][0] )                        ? $meta['id'][0] : '';
			$link      = isset( $meta['wp_id'][0] )                     ? get_permalink( $meta['wp_id'][0] ) : '';
			$qty       = isset( $advanced['onepage_qty'][ $id ] )       ? $advanced['onepage_qty'][ $id ] : 0;
			$freq      = isset( $advanced['onepage_freq'][ $id ] )      ? $advanced['onepage_freq'][ $id ] : '';
			$att_name  = isset( $advanced['onepage_att_name'][ $id ] )  ? $advanced['onepage_att_name'][ $id ] : '';
			$att_value = isset( $advanced['onepage_att_value'][ $id ] ) ? $advanced['onepage_att_value'][ $id ] : '';
			$sku       = isset( $meta['sku'][0] )                       ? $meta['sku'][0] : '';
			$offrs     = isset( $meta['offers'][0] )                    ? json_decode( $meta['offers'][0], true ) : '';
			$variants  = isset( $meta['variants_matrix'][0] )           ? json_decode( $meta['variants_matrix'][0], true ) : '';
			$variance  = [];
			$shipping  = '';
			$totaling  = '';

			if ( is_array( $variants ) ) {

				foreach ( $variants as $vnt ) {

					$match    = 0;
					$counter  = 0;
					$variance = [];

					while ( ! empty( $advanced['onepage_att_value'][ $meta['id'][0] ][ $counter ] ) ) {

						if ( strtolower( $vnt[ strtolower( $advanced['onepage_att_name'][ $meta['id'][0] ][ $counter ] ) ] ) == strtolower( $advanced['onepage_att_value'][ $meta['id'][0] ][ $counter ] ) ) {
							$match = 1;
						} else {
							$match = 0;
							break;
						}

						$counter++;
					}

					if ( $match != 0 ) {
						$variance = $vnt;
						break;
					}
				}
			}

			$price        = ! empty( $variance['price'] ) ? $variance['price'] : ( ! empty( $meta['price'][0] ) ? $meta['price'][0] : 0 );
			$total        = $this->currency_format( $price * $qty );
			$price        = $this->currency_format( $price );
			$grand_total += $total;

			if ( ! empty( $offrs ) ) {

				foreach ( $offrs as $o ) {

					if ( $o['offer_id'] == $this->campaign['offer'] ) {

						foreach ( $o['billing_models'] as $k => $f ) {

							if ( $k == $freq ) {
								$freqs   = $f;
								$freq_id = $k;
							}
						}
					}
				}
			}

			$counter  = 0;
			$v_matrix = '';
			$variants = '';

			while ( isset( $att_name[ $counter ] ) && isset( $att_value[ $counter ] ) ) {

				$variants .= ucwords( $att_name[ $counter ] ) . ': ' . $att_value[ $counter ] . '<br>';
				$v_matrix .= $this->fill( $this->page_tpl( 'matrix_onepage_variants' ), [
					'<!--id-->'       => $id,
					'<!--counter-->'  => $counter,
					'<!--att_name-->' => $att_name[ $counter ],
					'<!--att_val-->'  => $att_value[ $counter ]
				] );

				$counter++;
			}

			$matrix .= $this->fill( $this->page_tpl( 'matrix_onepage' ), [
				'<!--id-->'       => $id,
				'<!--qty-->'      => $qty,
				'<!--price-->'    => $price,
				'<!--freq_id-->'  => $freq_id,
				'<!--offer-->'    => $campaign['offer'],
				'<!--v_matrix-->' => $v_matrix
			] );

			$variants = "<br>{$variants}";
			$sku      = ! empty( $variance['sku'] ) ? $variance['sku'] : $sku;

			if ( isset( $_POST['aj'] ) ) { //thankyou case

				$html .= $this->fill( $this->page_tpl( 'onepage_item_thankyou' ), [
					'<!--img-->'      => $img,
					'<!--link-->'     => $link,
					'<!--name-->'     => $meta['name'][0],
					'<!--qty-->'      => $qty,
					'<!--price-->'    => $price,
					'<!--total-->'    => $total,
					'<!--class-->'    => $class,
					'<!--variants-->' => $variants,
					'<!--freqs-->'    => $freqs,
					'<!--sku-->'      => $sku
				] );

				$totaling = '';

			} else {

				$html .= $this->fill( $this->page_tpl( 'onepage_item' ), [
					'<!--img-->'      => $img,
					'<!--link-->'     => $link,
					'<!--name-->'     => $meta['name'][0],
					'<!--qty-->'      => $qty,
					'<!--price-->'    => $price,
					'<!--total-->'    => $total,
					'<!--class-->'    => $class,
					'<!--variants-->' => $variants,
					'<!--freqs-->'    => $freqs,
					'<!--sku-->'      => $sku
				] );

				$totaling = 'onepage_total';
				$shipping = $this->field_shippings();

			}
		}

		if ( ! empty ( $totaling ) ) {
			$html .= $this->fill( $this->page_tpl( $totaling ), [
				'<!--total-->'    => $this->currency_format( $grand_total ),
				'<!--shipping-->' => $shipping
			] );
		}

		if ( isset( $_POST['aj'] ) ) {
			echo $html;
			wp_die();
		} else {
			return "{$html}<div id='ll-matrix'>{$matrix}</div>";
		}
	}

	public function form_creditcard() {

		$icons = '';

		foreach( $this->campaign['payment_name'] as $key => $name ) {

			switch ( $key ) {

				case 'amex':
				case 'discover':
				case 'visa':
					$icons .= "<i class='fab fa-cc-{$key} ml-1'></i>";
					break;

				case 'master':
					$icons .= "<i class='fab fa-cc-mastercard ml-1'></i>";
					break;
			}
		}

		return $this->fill( $this->page_tpl( 'creditcard' ), [
			'<!--types-->' => $this->field_card_types(),
			'<!--month-->' => $this->field_card_month(),
			'<!--year-->'  => $this->field_card_year(),
			'<!--icons-->' => $icons
		] );
	}

	public function form_coupon() {
		return $this->page_tpl( 'coupon' );
	}

	public function form_address( $type ) {

		$address = $this->fill( $this->page_tpl( $type . '_address' ), [
			'<!--states-->'    => $this->field_states( $type ),
			'<!--countries-->' => $this->field_countries( $type )
		] );

		if ( $type == 'shipping' ) {
			$address = "<h3 class='my-3'>Shipping Address</h3>{$address}";
		} else {
			$address = "<hr><input id='ll-billing-same' name='billing_same' type='checkbox' value='1' checked='checked'> &nbsp; Billing Same As Shipping{$address}";
		}

		return $address;
	}

	public function form_email_phone() {
		return $this->page_tpl( 'email_phone' );
	}

	public function validate_coupon() {

		$email     = ! empty ( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : time() . '@limelightcrm.com';
		$ids       = [];
		$matrix    = [];
		$posted    = [];
		$attribute = [];
		$variant   = [];
		$query     = [
			'method'      => 'coupon_validate',
			'campaign_id' => $this->campaign['id'],
			'shipping_id' => ( isset( $_POST['sid'] ) && $_POST['sid'] != '' ) ? $_POST['sid'] : $this->campaign['shipping_id'][0],
			'email'       => $email,
			'promo_code'  => ( ! empty( $_POST['promo_code'] ) ) ? (string) $_POST['promo_code'] : ''
		];

		$matrix_items = ! empty( $_POST['matrix'] ) ? $_POST['matrix'] : [];

		if ( ! empty( $matrix_items ) ) {

			foreach ( $matrix_items as $item ) {

				if ( stripos( $item['name'], 'product_qty_' ) !== false ) {

					$id              = str_replace( 'product_qty_', '', $item['name'] );
					$ids[]           = $id;
					$mkey            = 'product_qty_' . $id;
					$matrix[ $mkey ] = $item['value'];

				} else {
					$posted[ $item['name'] ] = $item['value'];
				}
			}

			foreach ( $ids as $id ) {

				$counter       = 0;
				$variant_names = $variant_values = [];

				while ( isset( $posted[ "product_att_name_{$id}_{$counter}" ] ) && isset( $posted[ "product_att_value_{$id}_{$counter}" ] ) ) {

					$variant_names[]  .= $posted[ "product_att_name_{$id}_{$counter}" ];
					$variant_values[] .= $posted[ "product_att_value_{$id}_{$counter}" ];
					$counter++;
				}

				$variant[ "prod_attr_{$id}" ] = '';
				$variant[ "prod_opt_{$id}" ]  = '';

				if ( ! empty( $variant_names ) && ! empty( $variant_values ) ) {

					foreach ( $variant_names as $key => $value ) {
						$attribute[ $id ][ $value ] = $variant_values[ $key ];
					}

					$variant[ "prod_attr_{$id}" ] = implode( ",", $variant_names );
					$variant[ "prod_opt_{$id}" ]  = implode( ",", $variant_values );
				}
			}

			$matrix['product_attribute'] = $attribute;
			$matrix['product_ids']       = implode( ",", $ids );
			$matrix                      = ! empty( $variant ) ? array_merge( $matrix, $variant ) : $matrix;
		}

		$response = limelight_curl( array_merge( $query, $matrix ), 1, 0, 0 );

		if ( $response['response_code'] == '100' ) {

			$promo   = ! empty( $_POST['promo_code'] ) ? (string) $_POST['promo_code'] : '';
			$amount  = $this->currency_format( $response['coupon_amount'] );
			$message = "<span id='ll-coupon-remove' class='btn btn-warning btn-sm' onclick='limelightRemoveCoupon();'>Remove</span>";
			$message = "<small><b>{$promo}</b> applied. You save <b>\${$amount}</b>! &nbsp; {$message}</small>";

			echo json_encode( [
				'message'       => $message,
				'promo_code'    => $promo,
				'coupon_amount' => $amount,
				'response_code' => '100'
			] );

		}	else {

			echo json_encode( [
				'message'       => 'Coupon could not be applied',
				'coupon_amount' => 0
			] );
		}

		wp_die();
	}

	public function check_upsells() {

		$upsells = get_posts( [
			'post_type'   => 'upsells',
			'numberposts' => -1,
			'orderby'     => 'meta_value',
			'meta_key'    => 'upsell_order',
			'order'       => 'asc',
			'meta_query'  => [ [
					'key'   => 'upsell_active',
					'value' => 'true'
				] ]
		] );

		if ( isset( $_POST['aj'] ) ) {
			echo json_encode( $upsells );
			wp_die();
		} else {
			return $upsells;
		}
	}

	public function get_error() {

		$res                 = $_POST['response'];
		$custom              = $this->errors[ 'ERROR_' . $res['responseCode'] ];
		$html                = '<h3>Error</h3>';
		$res['responseCode'] = ( ! empty( $res['responseCode'] ) ) ? $res['responseCode'] : $res['response_code'];

		if ( ! empty( $this->errors['show_error_code'] ) ) {
			$html = "<h3>Error {$res['responseCode']}</h3>";
		}

		if ( ! empty( $custom ) ) {
			$html .= "<p>{$custom}</p>";
		}

		if ( empty( $res['errorMessage'] ) ) {

			if ( $res['error_message'] ) {
				$res['errorMessage'] = $res['error_message'];
			} elseif ( $res['declineReason'] ) {
				$res['errorMessage'] = $res['declineReason'];
			} elseif ( $res['decline_reason'] ) {
				$res['errorMessage'] = $res['decline_reason'];
			} else {
				$res['errorMessage'] = 'An Error Occured. Please Try Again.';
			}
		}

		if ( ! empty( $this->errors['show_error_default'] ) || empty( $custom ) ) {
			$html .= "<p>{$res['errorMessage']}</p>";
		}

		echo $html;
		wp_die();
	}

	public function kount_pixel() {

		if ( empty( $this->advanced['enable_kount'] ) ) {
			return;
		}

		$session = str_replace( '.', '', microtime( true ) );
		$src     = "https://{$this->api['appkey']}.limelightcrm.com/pixel.php?t=gif&campaign_id={$this->campaign['id']}&sessionId={$session}";

		return $this->fill( $this->page_tpl( 'kount' ), [
			'<!--src-->'     => $src,
			'<!--session-->' => $session
		] );
	}

	public function edigital_area() {
		return ( isset( $this->advanced['enable_edigital'] ) ) ? '<br><input type="checkbox" value="1" id="edigital" name="edigital" checked> &nbsp; Yes! Sign me up for the Health and Fitness program, only $1.97 monthly.' : '';
	}

	public function currency_format( $val = 0 ) {
		return number_format( $val, 2, '.', ',' );
	}

	public function get_item() {

		$item = get_posts( [
			'post_type'   => 'products',
			'ID'          => $_POST['id'],
			'numberposts' => 1
		] );

		echo json_encode( $item[0] );
		wp_die();
	}

	public function subscription_skip_next() {

		echo json_encode( limelight_curl( [
			'method'          => 'skip_next_billing',
			'subscription_id' => sanitize_text_field( $_POST['id'] )
		] ) );
		wp_die();
	}

	public function subscription_quantity() {

		echo json_encode( limelight_curl( [
			'method'                   => 'subscription_order_update',
			'order_id'                 => sanitize_text_field( $_POST['order_id'] ),
			'product_id'               => sanitize_text_field( $_POST['product_id'] ),
			'new_recurring_product_id' => sanitize_text_field( $_POST['recur_id'] ),
			'new_recurring_quantity'   => sanitize_text_field( $_POST['qty'] ),
		] ) );
		wp_die();
	}

	public function subscription_toggle() {

		echo json_encode( limelight_curl( [
			'method'     => 'subscription_order_update',
			'order_id'   => sanitize_text_field( $_POST['order_id'] ),
			'product_id' => sanitize_text_field( $_POST['product_id'] ),
			'status'     => sanitize_text_field( $_POST['recurring_status'] ) == '1' ? 'stop' : 'reset',
		] ) );
		wp_die();
	}

	public function subscription_frequency() {

		echo json_encode( limelight_curl( [
			'method'           => 'subscription_order_update',
			'order_id'         => sanitize_text_field( $_POST['order_id'] ),
			'product_id'       => sanitize_text_field( $_POST['product_id'] ),
			'billing_model_id' => sanitize_text_field( $_POST['billing_model_id'] )
		] ) );
		wp_die();
	}

	public function subscription_add_product() {

		echo json_encode( limelight_curl( [
			'method'                              => 'subscription_order_update',
			'order_id'                            => sanitize_text_field( $_POST['order_id'] ),
			'additional_product_id'               => sanitize_text_field( $_POST['product_id'] ),
			'additional_product_billing_model_id' => sanitize_text_field( $_POST['billing_model_id'] ),
		] ) );
		wp_die();
	}

	public function subscription_next_date() {

		$result = json_encode( limelight_curl( [
			'method'   => 'order_view',
			'order_id' => sanitize_text_field( $_POST['order_id'] )
		], 0, 0, 0, 1 ) );

		$date   = '';
		$result = json_decode( $result, true );
		$result = json_decode( $result['body'], true );

		foreach ( $result['products'] as $key => $val ) {

			if ( $val['product_id'] == $_POST['product_id'] ) {
				$date = $val['recurring_date'];
			}
		}

		echo date( 'm/d/Y', strtotime( $date ) );
		wp_die();
	}

	public function boltpay_order_view() {

		echo json_encode( limelight_curl( [
			'method'   => 'order_view',
			'order_id' => sanitize_text_field( $_POST['order_id'] )
		], 1 ) );
		wp_die();
	}

	public function amazonpay_process() {

		$pids             = [];
		$affiliate_matrix = [];
		$gift             = 0;
		$shipping_cost    = 0;
		$form_data        = ! empty( $_POST['form_data'] ) ? $_POST['form_data'] : [];
		$notes            = 'AmazonPay Order created by appkey:' . $this->api['appkey'] . ' at ' . get_home_url() . ' WP v' . get_bloginfo( 'version' ) . '. Plugin v' . limelight_get_version();

		//grabbing posted
		foreach ( $form_data as $field ) {

			$form[ $field['name'] ] = $field['value'];

			if ( substr( $field['name'], 0, 11 ) === "product_id_" ) {
				$pids[] = $field['value'];
			}

			if ( $field['name'] == 'gift_order' && $field['value'] == 1 ) {
				$gift = 1;
			}

			if ( stripos( $field['name'], 'affiliates_' ) !== false ) {
				$affiliate_matrix[ str_replace( 'affiliates_', '', $field['name'] ) ] = $field['value'];
			}

			if ( $field['name'] == 'shipping_cost' ) {
				$shipping_cost = $field['value'];
			}
		}

		//products
		foreach ( $pids as $id ) {

			$product_matrix[ $id ] = [
				'offer_id'         => $form[ 'product_offer_id_' . $id ],
				'billing_model_id' => $form[ 'product_billing_model_id_' . $id ],
				'quantity'         => $form[ 'product_qty_' . $id ],
			];

			$counter = 0;
			$variant = [];

			while ( isset( $form["product_att_name_{$id}_{$counter}"] ) && isset( $form["product_att_value_{$id}_{$counter}"] ) ) {

				$variant[] = [
					'attribute_name'  => $form["product_att_name_{$id}_{$counter}"],
					'attribute_value' => $form["product_att_value_{$id}_{$counter}"],
				];

				$counter++;
			}

			$product_matrix[ $id ]['variant'] = $variant;
		}

		$query = [
			'method'               => 'new_order',
			'creditCardType'       => 'amazonpay',
			'tranType'             => 'sale',
			'access_token'         => $form['access_token'],
			'billing_agreement_id' => $form['billing_agreement_id'],
			'shippingId'           => $form['shipping_id'],
			'ipAddress'            => $_SERVER['REMOTE_ADDR'],
			'campaignId'           => $this->campaign['id'],
			'products'             => $product_matrix,
			'notes'                => "(created-with-wp) {$notes}",
			'promoCode'            => $form['coupon']
		];

		//affiliates
		if ( ! empty( $affiliate_matrix ) ) {
			$query = array_merge( $query, $affiliate_matrix );
		}

		//gift
		if ( $gift ) {

			$query = array_merge( $query, [ 'gift' => [
				'email'   => $form['gift_email'],
				'message' => $form['gift_msg']
			] ] );
		}

		$result = limelight_curl( $query, 0, 0, 0, 1 );

		echo json_encode( $result );
		wp_die();
	}

	public function square_process() {

		foreach( ! empty( $_POST['checkout'] ) ? $_POST['checkout'] : [] as $field ) {

			$form[$field['name']]=$field['value'];

			if ( substr( $field['name'], 0, 11 ) === "product_id_" )
				$pids[] = $field['value'];

			if ( stripos( $item['name'], 'affiliates_' ) !== false )
				$affiliates[ str_replace( 'affiliates_', '', $item['name'] ) ] = $item['value'];

			if ( $item['name'] == 'gift_order' && $item['value'] == 1 )
				$gift = 1;

			if ( $item['name'] == 'account_create' && $item['value'] == 1 )
				$newacc = 1;
		}

		foreach ( $pids as $id ) {

			$products[ $id ] = [
				'offer_id'         => $form[ 'product_offer_id_' . $id ],
				'billing_model_id' => $form[ 'product_billing_model_id_' . $id ],
				'quantity'         => $form[ 'product_qty_' . $id ],
			];

			$counter = 0;
			$variant = [];

			while ( isset( $form["product_att_name_{$id}_{$counter}"] ) && isset( $form["product_att_value_{$id}_{$counter}"] ) ) {

				$variant[] = [
					'attribute_name'  => $form["product_att_name_{$id}_{$counter}"],
					'attribute_value' => $form["product_att_value_{$id}_{$counter}"],
				];

				$counter++;
			}

			$products[ $id ]['variant'] = $variant;
		}

		$notes  = 'Square Order created by appkey:' . $this->api['appkey'] . ' at ' . get_home_url() . ' WP v' . get_bloginfo( 'version' ) . '. Plugin v' . limelight_get_version();
		$prefix = $post['billing_same'] != '1' ? 'b' : 's';
		$query  = [
			'method'                => 'new_order',
			'ipAddress'             => $_SERVER['REMOTE_ADDR'],
			'email'                 => $form['email'],
			'phone'                 => $form['phone'],
			'square_token'          => $form['square_token'],
			'shippingId'            => $form['shipping_id'],
			'campaignId'            => $this->campaign['id'],
			'tranType'              => 'sale',
			'firstName'             => $form['s_first'],
			'lastName'              => $form['s_last'],
			'shippingAddress1'      => $form['s_addr1'],
			'shippingAddress2'      => $form['s_addr2'],
			'shippingCity'          => $form['s_city'],
			'shippingState'         => $form['s_state'],
			'shippingZip'           => $form['s_zip'],
			'shippingCountry'       => $form['s_country'],
			'billingSameAsShipping' => $form['billing_same'] == '1' ? 'yes' : 'no',
			'billingFirstName'      => $form["{$prefix}_first"],
			'billingLastName'       => $form["{$prefix}_first"],
			'billingAddress1'       => $form["{$prefix}_addr1"],
			'billingAddress2'       => $form["{$prefix}_addr2"],
			'billingCity'           => $form["{$prefix}_city"],
			'billingState'          => $form["{$prefix}_state"],
			'billingZip'            => $form["{$prefix}_zip"],
			'billingCountry'        => $form["{$prefix}_country"],
			'products'              => $products,
			'notes'                 => "(created-with-wp) {$notes}",
			'creditCardType'        => 'square',
		];

		if ( ! empty( $affiliates ) )
			$query = array_merge( $query, $affiliates );

		if ( $gift )
			$query = array_merge( $query, [
				'gift' => [
					'email'   => $form['gift_email'],
					'message' => $form['gift_msg'],
				]
			] );

		if ( $newacc )
			$query = array_merge( $query, [
				'create_member' => 1,
				'event_id'      => $this->advanced['membership_event'],
			] );

		$result = limelight_curl( $query, 0, 0, 0, 1 );

		echo $result['body'];
		wp_die();
	}
}