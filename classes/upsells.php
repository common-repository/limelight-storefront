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

class LimelightUpsells {

	public function __construct() {

		add_action( 'init',                                 [ $this, 'upsells' ], 0 );
		add_action( 'add_meta_boxes',                       [ $this, 'add_custom_meta_box' ] );
		add_action( 'save_post',                            [ $this, 'save_upsell_meta' ] );
		add_action( 'wp_ajax_get_offers',                   [ $this, 'get_offers' ] );
		add_action( 'wp_ajax_get_freqs',                    [ $this, 'get_freqs' ] );
		add_action( 'wp_ajax_get_products',                 [ $this, 'get_products' ] );
		add_action( 'wp_ajax_get_shippings',                [ $this, 'get_shippings' ] );
		add_action( 'wp_ajax_get_price',                    [ $this, 'get_price' ] );
		add_action( 'wp_ajax_get_variants',                 [ $this, 'get_variants' ] );
		add_action( 'admin_footer',                         [ $this, 'javascript' ] );
		add_filter( 'manage_upsells_posts_columns',         [ $this, 'add_upsell_columns' ] );
		add_action( 'manage_upsells_posts_custom_column',   [ $this, 'fill_upsell_columns' ], 10, 2 );
		add_filter( 'manage_edit-upsells_sortable_columns', [ $this, 'set_sortable_columns' ] );
		add_action( 'pre_get_posts',                        [ $this, 'custom_orderby' ] );
	}

	public function upsells() {

		register_post_type( 'upsells', [
			'label'               => __( 'upsells', '' ),
			'description'         => __( 'Upsell news and reviews', '' ),
			'labels'              => [
				'name'               => _x( 'Upsells', 'Post Type General Name', '' ),
				'singular_name'      => _x( 'Upsell', 'Post Type Singular Name', '' ),
				'menu_name'          => __( 'Upsells', '' ),
				'parent_item_colon'  => __( 'Parent Upsell', '' ),
				'all_items'          => __( 'All Upsells', '' ),
				'view_item'          => __( 'View Upsell', '' ),
				'add_new_item'       => __( 'Add New Upsell', '' ),
				'add_new'            => __( 'Add New', '' ),
				'edit_item'          => __( 'Edit Upsell', '' ),
				'update_item'        => __( 'Update Upsell', '' ),
				'search_items'       => __( 'Search Upsell', '' ),
				'not_found'          => __( 'Not Found', '' ),
				'not_found_in_trash' => __( 'Not found in Trash', '' )
			],
			'supports'            => [
				'title',
				'editor',
				'excerpt',
				'author',
				'thumbnail',
				'revisions',
				'custom-fields'
			],
			'taxonomies'            => [ 'upsells' ],
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => true,
			'show_in_admin_bar'     => true,
			'menu_position'         => 4,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => true,
			'capability_type'       => 'page',
			'menu_icon'             => 'dashicons-carrot',
			'show_in_rest'          => true,
			'rest_base'             => 'upsells',
			'rest_controller_class' => 'WP_REST_Posts_Controller'
		] );

		flush_rewrite_rules( false );
	}

	public function upsell_meta_box_markup( $object ) {

		$checked = get_post_meta( $object->ID, 'upsell_active', true ) == 'true' ? 'checked' : '';
		$price   = get_post_meta( $object->ID, "upsell_price", true );
		$sprice  = get_post_meta( $object->ID, "upsell_sprice", true );
		$order   = get_post_meta( $object->ID, "upsell_order", true );
		$qty     = get_post_meta( $object->ID, "upsell_qty", true ) ?: 1;

		echo <<<EX
<div>
	<input name="upsell_active" type="checkbox" value="true" {$checked}>
	<label for="upsell_active">Active</label><br>
	<label for="upsell_campaign">Campaign</label><br>
	{$this->get_campaigns()}<br>
	<label for="upsell_offer">Offer</label><br>
	{$this->get_offers()}<br>
	<label for="upsell_freq">Billing Model</label><br>
	{$this->get_freqs()}<br>
	<label for="upsell_product">Product</label><br>
	{$this->get_products()}<br>
	<label for="upsell_shipping">Shipping</label><br>
	{$this->get_shippings()}<br>
	<label for="upsell_price">Price</label><br>
	<input id="upsell_price" name="upsell_price" type="text" value="{$price}"><br>
	<label for="upsell_sprice">Shipping Price</label><br>
	<input id="upsell_sprice" name="upsell_sprice" type="text" value="{$sprice}"><br>
	<label for="upsell_order">Order</label><br>
	<input name="upsell_order" type="number" value="{$order}"><br>
	<label for="upsell_qty">Qty</label><br>
	<input name="upsell_qty" type="number" value="{$qty}" min="1" placeholder="1"><br>
	<label for="upsell_variant">Variant</label><br>
	{$this->get_variants()}<br>
</div>
EX;
	}

	public function add_custom_meta_box() {
		add_meta_box( 'upsell-meta-box', 'LimeLight Configuration', [ $this, 'upsell_meta_box_markup' ], 'upsells', 'side', 'high', null );
	}

	public function get_campaigns() {

		global $post;

		$cid       = get_post_meta( $post->ID, 'upsell_campaign', true );
		$campaigns = $this->get_all_campaigns();

		parse_str( $campaigns, $parsed );
		preg_match( '/campaign_name=([^&]*)/', $campaigns, $matches );

		$campaign_ids   = explode( ',', $parsed['campaign_id'] );
		$campaign_names = isset( $matches[1] ) ? explode( ',', $matches[1] ) : [];
		$html           = "<select id='campaign' name='upsell_campaign'><option value=''>Select Campaign</option>";

		foreach ( $campaign_ids as $key => $val ) {

			$name     = urldecode( $campaign_names[ $key ] );
			$selected = ( $cid == $val ) ? 'selected="selected"' : '';
			$html    .= "<option value='{$val}' $selected>{$val}. {$name}</option>";
		}

		return "{$html}</select>";
	}

	public function get_offers() {

		global $post;

		$oid    = get_post_meta( $post->ID, 'upsell_offer', true );
		$offers = $this->get_all_offers( $_POST['campaign_id'] ?: get_post_meta( $post->ID, 'upsell_campaign', true ) );
		$html   = "<select id='offer' name='upsell_offer'><option value=''>Select Offer</option>";

		if ( $offers->data ) {
			foreach ($offers->data as $offer) {
				$selected = ( $oid == $offer->id ) ? 'selected="selected"' : '';
				$html    .= "<option value='{$offer->id}' $selected>{$offer->id}. {$offer->name}</option>";
			}
		}

		$html .= '</select>';

		if ( isset( $_POST['aj'] ) ) {
			echo $html;
			wp_die();
		} else {
			return $html;
		}
	}

	public function get_all_campaigns() {
		return limelight_curl( [ 'method' => 'campaign_find_active' ] );
	}

	public function get_all_offers( $cid ) {
		return json_decode( limelight_curl( [
			'method'      => 'offer_view',
			'campaign_id' => $cid,
		] ) );
	}

	public function get_freqs() {

		global $post;

		$fid   = get_post_meta( $post->ID, 'upsell_freq', true );
		$freqs = $this->get_all_freqs( $_POST['offer_id'] ?: get_post_meta( $post->ID, 'upsell_offer', true ));
		$html  = "<select id='freq' name='upsell_freq'><option value=''>Select Frequency</option>";

		if ( $freqs->data ) {
			foreach ($freqs->data as $freq) {
				$selected = ( $fid == $freq->id ) ? 'selected="selected"' : '';
				$html    .= "<option value='{$freq->id}' $selected>{$freq->id}. {$freq->name}</option>";
			}
		}

		$html .= '</select>';

		if ( isset( $_POST['aj'] ) ) {
			echo $html;
			wp_die();
		} else {
			return $html;
		}
	}

	public function get_all_freqs( $oid ) {
		return json_decode( limelight_curl( [
			'method'   => 'billing_model_view',
			'offer_id' => $oid,
		] ) );
	}

	public function save_upsell_meta() {

		global $post;

		if ( ! isset( $post->ID ) ) {
			return;
		}

		$keys = [
			'upsell_active',
			'upsell_campaign',
			'upsell_freq',
			'upsell_offer',
			'upsell_product',
			'upsell_shipping',
			'upsell_variant',
			'upsell_order',
			'upsell_qty',
			'upsell_price',
			'upsell_sprice',
		];

		foreach ( $keys as $key ) {

			$value = '';

			if ( ! empty( $_POST[ $key ] ) )	{
				$value = $_POST[ $key ];
			}

			update_post_meta( $post->ID, $key, $value );
		}
	}

	public function javascript() {

		$screen = get_current_screen();

		if ( $screen->parent_base == 'edit' && get_post_type() == 'upsells' ) {
			wp_enqueue_script( 'limelight-upsells', plugins_url( '/js/upsells.js' , __FILE__ ) );
		}
	}

	public function get_products() {

		$html     = '';
		$success  = true;
		$product  = get_post_meta( get_the_ID(), 'upsell_product', true );
		$campaign = isset( $_POST['campaign_id'] ) ? $_POST['campaign_id'] : get_post_meta( get_the_ID(), 'upsell_campaign', true );
		$result   = limelight_curl( [
			'method'      => 'campaign_view',
			'campaign_id' => $campaign
		], 1, 0 );

		$html     = "<select id='product' name='upsell_product'><option value=''>Select Product</option>";
		$exploded = explode( ',', $result['product_id'] );

		foreach ( $exploded as $id ) {

			$result = limelight_curl( [
				'product_id' => $id,
				'method'     => 'product_index'
			], 1, 0, 0, 1 );

			$result = json_decode( $result['body'], 1 );

			if ( $result['products'] ) {
				foreach ( $result['products'] as $option ) {
					$selected = ( $product == $option['product_id'] ) ? 'selected="selected"' : '';
					$html .= "<option value='{$option['product_id']}' {$selected}>{$option['product_id']}. {$option['product_name']}</option>";
				}
			}
		}

		$html = "{$html}</select>";

		if ( isset( $_POST['aj'] ) ) {
			echo $html;
			wp_die();
		} else {
			return $html;
		}
	}

	public function get_shippings() {

		$campaign = get_post_meta( get_the_ID(), 'upsell_campaign', true );
		$shipping = get_post_meta( get_the_ID(), 'upsell_shipping', true );
		$campaign = isset( $_POST['campaign_id'] ) ? $_POST['campaign_id' ] : $campaign;
		$response = limelight_curl( [
			'method'      => 'campaign_view',
			'campaign_id' => $campaign
		], 1, 0 );

		$ship_ids = isset( $response['shipping_id'] ) ? $response['shipping_id'] : '';
		$html     = "<select id='shipping' name='upsell_shipping'><option value=''>Select Shipping</option>";

		if ( $ship_ids ) {

			$ship_ids = explode( ',', $ship_ids );
			$names    = explode( ',', $response['shipping_name'] );
			$prices   = explode( ',', $response['shipping_initial_price'] );

			foreach ( $ship_ids as $key => $value ) {

				$selected = ( $shipping == $value ) ? 'selected="selected"' : '';
				$html    .= "<option value='{$value}' {$selected}>{$value}. {$names[$key]} - \${$prices[$key]}</option>";
			}
		}

		$html = "{$html}</select>";

		if ( isset( $_POST['aj'] ) ) {
			echo $html;
			wp_die();
		} else {
			return $html;
		}
	}

	public function get_variants() {

		$vnt = get_post_meta( $_POST['post_id'], 'upsell_variant', true );
		$pid = ! empty( $_POST['product_id'] ) ? $_POST['product_id'] : 0;

		$response = limelight_curl( [
			'method'     => 'product_attribute_index',
			'product_id' => $pid,
		], 0, 0, 0, 1 );

		$response = json_decode( $response['body'], 1 );

		if ( isset( $response['data'][$pid]['data'] ) && ! empty( $response['data'][$pid]['data'] ) ) {

			$html = "<select id='variant' name='upsell_variant'><option value=''>Select Variant</option>";

			foreach ( $response['data'][$pid]['data'] as $variant ) {

				$attributes = [];

				foreach ($variant as $key => $val) {

					if ( ! in_array( $key, [ 'id', 'price', 'max_quantity', 'weight', 'sku' ] ) ) {
						$attributes[] = [
							'attribute_name'  => $key,
							'attribute_value' => $val
						];
					}
				}

				$att      = json_encode( $attributes );
				$selected = $vnt == $att ? 'selected="selected"' : '';
				$html    .= "<option value='{$att}' {$selected}>{$variant['id']}. {$variant['sku']} (\${$variant['price']})</option>";
			}

			$html = "{$html}</select>";
		}
		else
		{
			$html = '<span id="variant">N/A</span>';
		}

		if ( isset( $_POST['aj'] ) ) {
			echo $html;
			wp_die();
		} else {
			return $html;
		}
	}

	public function get_price() {

		$res = limelight_curl( [
			'product_id' => sanitize_text_field( $_POST['product_id'] ),
			'method'     => 'product_index'
		], 1, 0 );

		echo number_format( $res['product_price'], 2, '.', ',' );
	}

	public function add_upsell_columns( $columns ) {

		$date  = $columns['date'];
		$title = $columns['title'];

		unset(
			$columns['title'],
			$columns['date'],
			$columns['author'],
			$columns['comments']
		);

		$columns['img']             = __( '', 'll-prod-columns' );
		$columns['title']           = $title;
		$columns['upsell_active']   = __( 'Active', 'll-prod-columns' );
		$columns['upsell_campaign'] = __( 'Campaign_ID', 'll-prod-columns' );
		$columns['upsell_offer']    = __( 'Offer_ID', 'll-prod-columns' );
		$columns['upsell_freq']     = __( 'Frequency', 'll-prod-columns' );
		$columns['upsell_product']  = __( 'Product_ID', 'll-prod-columns' );
		$columns['upsell_shipping'] = __( 'Shipping_ID', 'll-prod-columns' );
		$columns['upsell_variant']  = __( 'Variant', 'll-prod-columns' );
		$columns['upsell_price']    = __( 'Price', 'll-prod-columns' );
		$columns['upsell_sprice']   = __( 'Shipping Price', 'll-prod-columns' );
		$columns['upsell_order']    = __( 'Ordering', 'll-prod-columns' );
		$columns['upsell_qty']      = __( 'Quantity', 'll-prod-columns' );
		$columns['post_id']         = __( 'Post_ID', 'll-prod-columns' );
		$columns['date']            = $date;
		$columns['link']            = __( '', 'll-prod-columns' );

		return $columns;
	}

	public function fill_upsell_columns( $column, $post_id ) {

		global $post;

		$meta = get_post_meta( $post->ID );

		if ( ! empty( $meta ) ) {

			switch ( $column ) {

				case 'img':
					echo has_post_thumbnail() ? the_post_thumbnail( 'products-thumb' ) : '<img src="//via.placeholder.com/60x60">';
					break;
				case 'upsell_active':
					echo ! empty( $meta['upsell_active'][0] ) ? $meta['upsell_active'][0] : '';
					break;
				case 'upsell_campaign':
					echo ! empty( $meta['upsell_campaign'][0] ) ? $meta['upsell_campaign'][0] : '';
					break;
				case 'upsell_offer':
					echo ! empty( $meta['upsell_offer'][0] ) ? $meta['upsell_offer'][0] : '';
					break;
				case 'upsell_freq':
					echo ! empty( $meta['upsell_freq'][0] ) ? $meta['upsell_freq'][0] : '';
					break;
				case 'upsell_product':
					echo ! empty( $meta['upsell_product'][0] ) ? $meta['upsell_product'][0] : '';
					break;
				case 'upsell_shipping':
					echo ! empty( $meta['upsell_shipping'][0] ) ? $meta['upsell_shipping'][0] : '';
					break;
				case 'upsell_variant':
					echo ! empty( $meta['upsell_variant'][0] ) ? $meta['upsell_variant'][0] : '';
					break;
				case 'upsell_price':
					echo ! empty( $meta['upsell_price'][0] ) ? '$' . number_format( $meta['upsell_price'][0], 2, '.', ',' ) : '';
					break;
				case 'upsell_sprice':
					echo ! empty( $meta['upsell_sprice'][0] ) ? '$' . number_format( $meta['upsell_sprice'][0], 2, '.', ',' ) : '';
					break;
				case 'upsell_order':
					echo ! empty( $meta['upsell_order'][0] ) ? $meta['upsell_order'][0] : '';
					break;
				case 'upsell_qty':
					echo ! empty( $meta['upsell_qty'][0] ) ? $meta['upsell_qty'][0] : '';
					break;
				case 'post_id':
					echo $post->ID;
					break;
				case 'link':
					$id       = ! empty( $meta['upsell_product'][0] ) ? $meta['upsell_product'][0] : '';
					$api_data = get_option( 'limelight_api' );
					$link     = "https://{$api_data['appkey']}.limelightcrm.com/admin/products/products.php?use_new=1&product_id={$id}";
					echo "<a target='_blank' href='{$link}'><input type='button' class='button' value='View in CRM &raquo'></a>";
					break;
			}
		}
	} 

	public function set_sortable_columns() {

		return [
			'upsell_active'   => 'upsell_active',
			'upsell_campaign' => 'upsell_campaign',
			'upsell_offer'    => 'upsell_offer',
			'upsell_freq'     => 'upsell_freq',
			'upsell_product'  => 'upsell_product',
			'upsell_shipping' => 'upsell_shipping',
			'upsell_variant'  => 'upsell_variant',
			'upsell_price'    => 'upsell_price',
			'upsell_order'    => 'upsell_order',
			'upsell_qty'      => 'upsell_qty',
			'post_id'         => 'post_id'
		];
	}

	public function custom_orderby( $query ) {

		$orderby = $query->get( 'orderby' );

		if ( 'upsell_active' == $orderby ) {

			$query->set( 'meta_key', 'upsell_active' );
			$query->set( 'orderby', 'meta_value' );

		} elseif ( 'upsell_campaign' == $orderby ) {

			$query->set( 'meta_key', 'upsell_campaign' );
			$query->set( 'orderby', 'meta_value_num' );

		} elseif ( 'upsell_offer' == $orderby ) {

			$query->set( 'meta_key', 'upsell_offer' );
			$query->set( 'orderby', 'meta_value_num' );

		} elseif ( 'upsell_freq' == $orderby ) {

			$query->set( 'meta_key', 'upsell_freq' );
			$query->set( 'orderby', 'meta_value_num' );

		} elseif ( 'upsell_product' == $orderby )	{

			$query->set( 'meta_key', 'upsell_product' );
			$query->set( 'orderby', 'meta_value_num' );

		} elseif ( 'upsell_shipping' == $orderby ) {

			$query->set( 'meta_key', 'upsell_shipping' );
			$query->set( 'orderby', 'meta_value_num' );

		} elseif ( 'upsell_variant' == $orderby ) {

			$query->set( 'meta_key', 'upsell_variant' );
			$query->set( 'orderby', 'meta_value_num' );

		} elseif ( 'upsell_price' == $orderby ) {

			$query->set( 'meta_key', 'upsell_price' );
			$query->set( 'orderby', 'meta_value_num' );

		} elseif ( 'upsell_order' == $orderby ) {

			$query->set( 'meta_key', 'upsell_order' );
			$query->set( 'orderby', 'meta_value_num' );

		} elseif ( 'upsell_qty' == $orderby ) {

			$query->set( 'meta_key', 'upsell_qty' );
			$query->set( 'orderby', 'meta_value_num' );

		} elseif ( 'post_id' == $orderby ) {

			$query->set( 'orderby', 'ID' );
		}
	}
}