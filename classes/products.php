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

class LimelightProducts {

	public function __construct() {

		add_action( 'init',                                  [ $this, 'products' ], 0 );
		add_action( 'init',                                  [ $this, 'image_thumbnails' ] );
		add_action( 'init',                                  [ $this, 'add_taxonomies_to_products' ] );
		add_action( 'dashboard_glance_items',                [ $this, 'at_a_glance' ] );
		add_filter( 'manage_products_posts_columns',         [ $this, 'add_product_columns' ] );
		add_action( 'manage_products_posts_custom_column',   [ $this, 'fill_product_columns' ], 10, 2 );
		add_filter( 'manage_edit-products_sortable_columns', [ $this, 'set_sortable_columns' ] );
		add_action( 'pre_get_posts',                         [ $this, 'custom_orderby' ] );
		add_filter( 'get_pages',                             [ $this, 'add_products_to_dropdown' ], 10, 2 );
	}

	public function products() {

		register_post_type( 'products', [
			'label'               => __( 'products', '' ),
			'description'         => __( 'Product news and reviews', '' ),
			'labels'              => [
				'name'               => _x( 'Products', 'Post Type General Name', '' ),
				'singular_name'      => _x( 'Product', 'Post Type Singular Name', '' ),
				'menu_name'          => __( 'Products', '' ),
				'parent_item_colon'  => __( 'Parent Product', '' ),
				'all_items'          => __( 'All Products', '' ),
				'view_item'          => __( 'View Product', '' ),
				'add_new_item'       => __( 'Add New Product', '' ),
				'add_new'            => __( 'Add New', '' ),
				'edit_item'          => __( 'Edit Product', '' ),
				'update_item'        => __( 'Update Product', '' ),
				'search_items'       => __( 'Search Product', '' ),
				'not_found'          => __( 'Not Found', '' ),
				'not_found_in_trash' => __( 'Not found in Trash', '' )
			],
			'supports'            => [ 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields' ],
			'taxonomies'          => [ 'products' ],
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
			'menu_icon'           => 'dashicons-cart',
			'show_in_rest'          => true,
			'rest_base'             => 'products',
			'rest_controller_class' => 'WP_REST_Posts_Controller'
		] );

		flush_rewrite_rules( false );
	}

	public function add_taxonomies_to_products() {

		register_taxonomy_for_object_type( 'post_tag', 'products' );
		register_taxonomy_for_object_type( 'category', 'products' );
	}

	public function at_a_glance() {

		$post_types = get_post_types( [ 'public' => true, '_builtin' => false ], 'object', 'and' );

		foreach ( $post_types as $post_type ) {

			$nposts = wp_count_posts( $post_type->name );
			$num    = number_format_i18n( $nposts->publish );
			$text   = _n( $post_type->labels->singular_name, $post_type->labels->name, intval( $nposts->publish ) );

			if ( current_user_can( 'edit_posts' ) ) {

				$output = '<a href="edit.php?post_type=' . $post_type->name . '">' . $num . ' ' . $text . '</a>';
				echo '<li class="post-count ' . $post_type->name . '-count">' . $output . '</li>';
			}
		}
	}

	public function add_product_columns( $columns ) {

		$title      = $columns['title'];
		$date       = $columns['date'];
		$categories = $columns['categories'];

		unset(
			$columns['title'],
			$columns['date'],
			$columns['categories'],
			$columns['author'],
			$columns['tags'],
			$columns['comments']
		);

		$columns['img']        = __( '', 'll-prod-columns' );
		$columns['title']      = $title;
		$columns['product_id'] = __( 'Product_ID', 'll-prod-columns' );
		$columns['sku']        = __( 'Sku', 'll-prod-columns' );
		$columns['price']      = __( 'Price', 'll-prod-columns' );
		$columns['cost']       = __( 'Cost', 'll-prod-columns' );
		$columns['max_qty']    = __( 'Max_Qty', 'll-prod-columns' );
		$columns['offers']     = __( 'Offers', 'll-prod-columns' );
		$columns['variants']   = __( 'Variants', 'll-prod-columns' );
		$columns['categories'] = $categories;
		$columns['post_id']    = __( 'Post_ID', 'll-prod-columns' );
		$columns['date']       = $date;
		$columns['link']       = __( '', 'll-prod-columns' );

		return $columns;
	}

	public function fill_product_columns( $column ) {

		global $post;

		$meta = get_post_meta( $post->ID );

		switch ( $column ) {

			case 'img':
				echo has_post_thumbnail() ? the_post_thumbnail( 'products-thumb' ) : '<img src="//via.placeholder.com/60x60">';
				break;
			case 'product_id':
				echo ! empty( $meta['id'][0] ) ? "<b>{$meta['id'][0]}</b>" : '';
				break;
			case 'sku':
				echo ! empty( $meta['sku'][0] ) ? $meta['sku'][0] : '';
				break;
			case 'price':
				echo ! empty( $meta['price'][0] ) ? $meta['price'][0] : '';
				break;
			case 'cost':
				echo ! empty( $meta['cost'][0] ) ? $meta['cost'][0] : '';
				break;
			case 'max_qty':
				echo ! empty( $meta['max_quantity'][0] ) ? $meta['max_quantity'][0] : '';
				break;
			case 'offers':
				echo ! empty( $meta['offers'][0] ) ? 'Y' : 'N';
				break;
			case 'variants':
				echo ! empty( $meta['variants'][0] ) ? 'Y' : 'N';
				break;
			case 'post_id':
				echo ! empty( $post->ID ) ? $post->ID : '';
				break;
			case 'link':
				$id       = ! empty( $meta['id'][0] ) ? $meta['id'][0] : '';
				$api_data = get_option( 'limelight_api' );
				$link     = "https://{$api_data['appkey']}.limelightcrm.com/admin/products/products.php?use_new=1&product_id={$id}";
				echo "<a target='_blank' href='{$link}'><input type='button' class='button' value='View in CRM &raquo'></a>";
				break;
		}
	}

	public function set_sortable_columns() {

		return [
			'product_id' => 'product_id',
			'price'      => 'price',
			'cost'       => 'cost',
			'max_qty'    => 'max_qty',
			'post_id'    => 'post_id',
			'sku'        => 'sku',
		];
	}

	public function custom_orderby( $query ) {

		$orderby = $query->get( 'orderby' );

		if ( 'price' == $orderby ) {

			$query->set( 'meta_key', 'price' );
			$query->set( 'orderby', 'meta_value_num' );

		} elseif ( 'product_id' == $orderby ) {

			$query->set( 'meta_key', 'id');
			$query->set( 'orderby', 'meta_value_num' );

		} elseif ( 'cost' == $orderby ) {

			$query->set( 'meta_key', 'cost' );
			$query->set( 'orderby', 'meta_value_num' );

		} elseif ( 'max_qty' == $orderby ) {

			$query->set( 'meta_key', 'max_quantity' );
			$query->set( 'orderby', 'meta_value_num' );

		} elseif ( 'sku' == $orderby ) {

			$query->set( 'meta_key', 'sku' );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	public function image_thumbnails() {
		add_image_size( 'products-thumb', 60, 60, true );
	}

	public function add_products_to_dropdown( $pages, $r ) {

		if ( isset( $r['name'] ) && 'page_on_front' == $r['name'] ) {

			$prods = get_posts( [ 'post_type' => 'products', 'numberposts' => -1 ] );
			$pages = array_merge( $pages, $prods );
		}

		return $pages;
	}
}