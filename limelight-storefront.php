<?php
// Kill heartbeat for dev
// add_action( 'init', 'stop_heartbeat', 1 );
// function stop_heartbeat() {
// wp_deregister_script( 'heartbeat' );
// }
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

/************************************************************************
 *
 * @link              https://limelightcrm.com
 * @since             1.6.1.5
 * @package           Limelight
 *
 * @wordpress-plugin
 * Plugin Name:       LimeLight Storefront
 * Plugin URI:        http://help.limelightcrm.com/hc/en-us/articles/115003634306-Wordpress-Plugin
 * Description:       A Plugin to easily integrate LimeLight.
 * Version:           1.6.1.3
 * Author:            Lime Light CRM, Inc.
 * Tested up to:      5.2
 * Requires PHP:      5.4
 * Author URI:        https://limelightcrm.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
*/

if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
	die;
}  

require 'classes/client.php';
require 'classes/admin.php';

new LimelightClient();

if ( is_admin() ) {
	new LimelightAdmin();
}

remove_filter( 'get_the_excerpt',         'wp_trim_excerpt');
add_filter( 'get_the_excerpt',            'limelight_trim_excerpt');
add_filter( 'pre_get_posts',              'limelight_add_custom_types' );
add_action( 'wp_dashboard_setup',         'limelight_add_dashboard' );
add_action( 'template_redirect',          'limelight_force_https', 1 );
add_action( 'wp_before_admin_bar_render', 'limelight_hide_wp_logo', 1 );

function limelight_curl( $query = [], $parsed = 0, $additional_headers = 0, $endpoint = 0, $json_api = 0 ) {
	global $wp_version;
	$api = get_option( 'limelight_api' );

	$headers = [
		'headers' => [
			'wp_version'     => $wp_version,
			'plugin_version' => limelight_get_version()
		]
	];

	if ( $additional_headers ) {
		$headers = array_merge( $headers, $additional_headers );
	}

	if ( $endpoint == 'boltpay_api' && $endpoint != '0' ) {

		$endpoint = "https://{$api['appkey']}.limelightcrm.com/admin/api/{$endpoint}.php?username={$api['user']}&password={$api['pass']}&";

	} else {

		if ( $json_api == 1 ) {

			$headers = [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( $api['user'] . ':' . $api['pass'] )
			];

			$endpoint = "https://{$api['appkey']}.limelightcrm.com/api/v1/{$query['method']}";
			$query['method'] = 'POST';

		} else {

			$api_type = ( ! $endpoint ) ? 'membership' : 'transact';
			$endpoint = "https://{$api['appkey']}.limelightcrm.com/admin/{$api_type}.php?username={$api['user']}&password={$api['pass']}&";

			if ( isset( $endpoint ) && $endpoint != 1 ) {
				$endpoint = $endpoint;
				$headers  = [];
			}
		}
	}

	if ( $json_api != 1 ) {
		$response = wp_remote_retrieve_body( wp_remote_get( $endpoint . http_build_query( $query ), $headers ) );
	} else {
		$request['timeout'] = '600';
		$request['body']    = json_encode( $query );
		$request['headers'] = $headers;
		$response           = wp_remote_post( $endpoint, $request );
	}

	if ( $parsed && $json_api != 1 ) {
		parse_str( $response, $parsed );
		$output = $parsed;
	} else {
		$output = $response;
	}

	return $output;
}

function limelight_dashboard_widget( $post, $callback_args ) {
	$version = limelight_get_version();
	$path    = plugin_dir_url( __FILE__ );
	echo <<<HTML
<table style="font-size:18px;">
	<tr>
		<td>
			<a href="admin.php?page=limelight-admin">
				<img src="{$path}assets/img/limelight.png" />
			</a>
		</td>
		<td style="font-size:14px;">
			<ul>
				<li><b>Storefront Settings</b></li>
				<li><a href="admin.php?page=limelight-admin">API Credentials</a></li>
				<li><a href="admin.php?page=campaign-settings">Campaign Settings</a></li>
				<li><a href="admin.php?page=advanced-settings">Advanced Settings</a></li>
				<li><a href="admin.php?page=shop-settings">Shop Settings</a></li>
				<li><a href="admin.php?page=error-responses">Error Responses</a></li>
				<li><a href="admin.php?page=submit-feedback">Submit Feedback</a></li>
			</ul>
		</td>
	</tr>
</table>
<table style="width:100%;font-size:130%;text-align:center;padding:3px;border-radius:9px;" bgcolor="#f4f4f4">
	<tr>
		<td><a href="edit.php?post_type=products"><div class="dashicons-before dashicons-cart"> &nbsp; Products</div></a></td>
		<td><a href="admin.php?page=limelight-orders"><div class="dashicons-before dashicons-list-view"> &nbsp; Orders</div></a></td>
	</tr>
	<tr>
		<td><a href="edit.php?post_type=upsells"><div class="dashicons-before dashicons-carrot"> &nbsp; Upsells</div></a></td>
		<td><a href="admin.php?page=limelight-subscriptions"><div class="dashicons-before dashicons-image-rotate"> &nbsp; Subscriptions</div></a></td>
	</tr>
	<tr>
		<td><a href="admin.php?page=limelight-customers"><div class="dashicons-before dashicons-groups"> &nbsp; Customers</div></a></td>
		<td><a href="admin.php?page=limelight-admin"><div class="dashicons-before dashicons-admin-tools"> &nbsp; Settings</div></a></td>
	</tr>
</table>
<p>You are running the LimeLightCRM Wordpress Plugin Version {$version}. To find out more about <b>LimeLightCRM</b> visit our <a target="_blank" href="//www.limelightcrm.com">Official Website</a>.</p>
HTML;
}

function limelight_add_dashboard() {
	wp_add_dashboard_widget( 'dashboard_widget', 'LimeLightCRM Plugin', 'limelight_dashboard_widget' );
}

function limelight_force_https() {

	if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == "on" ) {
		return;
	}

	$s = get_option( 'limelight_advanced' );

	if ( isset( $s['https'] ) && $s['https'] == 'on' ) {
		wp_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301 );
		exit();
	}
}

function limelight_hide_wp_logo() {
	global $wp_admin_bar;
	$s = get_option( 'limelight_advanced' );

	if ( isset( $s['hide_wp_logo'] ) && $s['hide_wp_logo'] == 'on' ) {
		$wp_admin_bar->remove_menu( 'wp-logo' );
	}
}

function limelight_add_custom_types( $query ) {

	if ( ( is_category() || is_tag() ) && $query->is_archive() && empty( $query->query_vars['suppress_filters'] ) ) {
		$query->set( 'post_type', [ 'post', 'products' ] );
	}

	return $query;
}

function limelight_get_version() {

	if ( $handle = fopen( dirname( __FILE__ ) . '/.version', 'r' ) ) {

		while ( ( $line = fgets( $handle ) ) !== false ) {
			$version = rtrim( $line );
		}
		fclose( $handle );
	}	else {
		$version = 'Unknown';
	}

	return $version;
}

function limelight_allowed_excerpt_tags() {
	return '<div>,<form>,<button>,<input>,<option>,<select>,<b>,<h1>,<h2>,<h3>,<script>,<style>,<br>,<em>,<i>,<ul>,<ol>,<li>,<a>,<p>,<img>,<video>,<audio>'; 
}

function limelight_trim_excerpt( $excerpt ) {
	$raw = $excerpt;

	if ( $excerpt == '' ) {
		$excerpt    = get_the_content();
		$excerpt    = strip_shortcodes( $excerpt );
		$excerpt    = apply_filters( 'the_content', $excerpt );
		$excerpt    = str_replace( ']]>', ']]&gt;', $excerpt );
		$excerpt    = strip_tags( $excerpt, limelight_allowed_excerpt_tags() );
		$word_count = 1000;
		$length     = apply_filters('excerpt_length', $word_count ); 
		$tokens     = [];
		$output     = '';
		$count      = 0;

		preg_match_all( '/(<[^>]+>|[^<>\s]+)\s*/u', $excerpt, $tokens );

		foreach ( $tokens[0] as $token ) { 

			if ( $count >= $length && preg_match( '/[\,\;\?\.\!]\s*$/uS', $token ) ) { 
				$output .= trim( $token );
				break;
			}
			$count++;
			$output .= $token;
		}

		return trim( force_balance_tags( $output ) );
	}

	return apply_filters( 'limelight_trim_excerpt', $excerpt, $raw );
}

// Cart Script
add_action( 'wp_head', function () {

	echo <<<HTML
<script type="text/javascript">
function limelightUpdateCart() {

	let items = '';
	let count = 0;
	let total = 0;
	let cart  = localStorage.getItem( 'limelight_cart' );

	if ( cart ) {

		cart = JSON.parse( cart );

		jQuery.each( cart, function( i, field ) {

			field['pic'] = field['pic'] ? field['pic'] : '//dummyimage.com/30x30/f4f4f4/cccccc&text=';
			let image    = '<img style="width:30px;" src="' + field['pic'] + '" />';

			count += +field['qty'];
			total += +field['qty'] * +field['price'].replace( /[^0-9.]/g, '' );
			items += '<hr><div class="row"><div class="col-sm-2">' + image + '</div><div class="col-sm-5">' + field['name'] + '<br><small class="text-muted">' + field['sku'] + '</small></div><div class="col-sm-5 text-right">' + field['qty'] + ' x <b>' + field['price'] + '</b><br>' + field['frequency_name'] + '</div></div>';

			for ( i = 0; field[ 'variant_name_' + i ]; i++ ) {
				items += '<small class="badge badge-light mr-2 font-weight-normal">' + field[ 'variant_name_' + i ] + ': ' + field[ 'variant_value_' + i ] + '</small>';
			}
		} );
	}

	jQuery( '#limelight-header-cart-count' ).html( count );
	jQuery( '#limelight-header-cart-total' ).html( total.toFixed( 2 ) );
	jQuery( '#limelight-widget-cart-items' ).html( items );
	jQuery( '#limelight-widget-cart-count' ).html( count + ' items in cart' );
	jQuery( '#limelight-widget-cart-total' ).html( total.toFixed( 2 ) );
}

jQuery( document ).ready( function() {
	limelightUpdateCart();
} );

jQuery( 'body' ).on( 'click change load', function() {
	limelightUpdateCart();
} );
</script>
HTML;
} );

// Cart Widget - [limelight_cart_widget]
function limelight_cart_widget_area( $atts ) {

	$cart     = get_page_by_title( 'Cart' );
	$checkout = get_page_by_title( 'Checkout' );

	return <<<HTML
<div id="cart-widget"><h5 class="widget-title h6">Cart</h5>
	<div id="limelight-widget-cart-items">0 Items In Cart</div>
	<hr class="cart-item-sep">
	<div class="text-center">
		SubTotal: <b>\$<span id="limelight-widget-cart-total">0.00</span></b>
	</div>
	<div class="text-center">
		<small id="limelight-widget-cart-count" class="text-muted text-center">0 items in cart</small>
	</div>
	<hr class="cart-item-sep">
	<div class="text-center">
		<button type="button" class="btn btn-primary" onclick="window.location.href = '{$cart->guid}'"><i class="fa fa-shopping-cart"></i> &nbsp; View Cart</button>
		<button type="button" class="btn btn-primary" onclick="window.location.href = '{$checkout->guid}'"><i class="fa fa-chevron-circle-right"></i> &nbsp; Checkout</button>
	</div>
</div>
HTML;
}

add_shortcode( 'limelight_cart_widget', 'limelight_cart_widget_area' );