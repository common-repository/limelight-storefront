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

global $wpdb;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

// option
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'limelight_%'" );

// product & upsell
$products = get_posts( [
	'post_type'   => [ 'products', 'upsells' ],
	'numberposts' => -1
] );
foreach ( $products as $p ) {
	wp_delete_post( $p->ID, true );
}

// page
$pages = [
	'prospect',
	'cart',
	'checkout',
	'onepage_checkout',
	'thankyou',
	'account',
	'orders',
	'subscriptions',
	'login'
];
foreach ( $pages as $p ) {
	$page = get_posts( [
		'post_type'      => 'page',
		'meta_key'       => 'll_page_type',
		'meta_value'     => $p,
		'post_status'    => 'any',
		'posts_per_page' => 1
	] );
	wp_delete_post( $page[0]->ID, true );
}

// taxonomy
$parent = [ get_cat_ID( 'Shop' ) ];
wp_delete_category( $parent );
$terms = get_terms( [ 'category' ], [ 'parent' => $parent ] );
foreach ( $terms as $term ) {
	wp_delete_category( $term->term_id );
}