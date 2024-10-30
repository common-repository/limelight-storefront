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

class LimelightSubscriptions {

	public function __construct() {

		$this->api      = get_option( 'limelight_api' );
		$this->campaign = get_option( 'limelight_campaign' );

		add_action( 'admin_menu', [ $this, 'add_subscriptions_menu' ] );
	}

	public function add_subscriptions_menu() {

		add_menu_page( 'Subscriptions', 'Subscriptions', 'manage_options', 'limelight-subscriptions', [ $this, 'page' ], 'dashicons-image-rotate', 4 );
		add_action( 'admin_head', [ $this, 'column_css' ] );
	}

	public function page() {

		$html          = '<div class="wrap"><h1 class="wp-heading-inline">Subscriptions</h1>';
		$appkey        = ( $this->api['appkey'] ) ? : 'www';
		$subscriptions = new SubscriptionsListTable();
		$subscriptions->prepare_items();

		if ( $this->campaign ) {
			echo "{$html}<p>These are your subscriptions for <b>Campaign #{$this->campaign['id']}</b>. You can update subscriptions at <a target='_blank' href='http://{$appkey}.limelightcrm.com/admin'>{$appkey}.limelightcrm.com</a>.</p>";
			$subscriptions->display();
		} else {
			echo "{$html}Please configure your campaign settings to see your subscribers.";
		}

		echo '</div>';
	}

	public function column_css() {
		echo '<style type="text/css">.column-id { white-space:nowrap; }</style>';
	}
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class SubscriptionsListTable extends WP_List_Table {

	public function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$ids      = $this->get_order_ids();
		usort( $ids, [ &$this, 'sort_data' ] );

		$perPage     = 10;
		$currentPage = $this->get_pagenum();
		$totalItems  = count( $ids );

		$this->set_pagination_args( [
			'total_items' => $totalItems,
			'per_page'    => $perPage
		] );

		$ids  = array_slice( $ids, ( ( $currentPage - 1 ) * $perPage ), $perPage );
		$data = $this->get_order_details( $ids );

		$this->_column_headers = [ $columns, $hidden, $sortable ];
		$this->items = $data;
	}

	public function get_columns() {

		return [
			'id'             => 'ID',
			'date'           => 'Date',
			'status'         => 'Status',
			'name'           => 'Customer',
			'contact'        => 'Contact',
			'shipping'       => 'Shipping',
			'billing'        => 'Billing',
			'recurring_date' => 'Recurs',
			'line_items'     => 'Items',
			'price'          => 'Price',
			'link'           => ''
		];
	}

	public function get_hidden_columns() {
		return [];
	}

	public function get_sortable_columns() {
		return [ 'id' => [ 'id', false ] ];
	}

	private function get_order_ids() {

		$order_ids = [];
		$campaign  = get_option( 'limelight_campaign' );
		$query     = [
			'method'      => 'order_find',
			'campaign_id' => $campaign['id'],
			'start_date'  => date( 'm/d/Y', strtotime( '04/19/1983' ) ),
			'end_date'    => date( 'm/d/Y' ),
			'criteria'    => 'approved,success'
		];

		$result = limelight_curl( $query, 1, 0 );

		if ( isset( $result['order_ids'] ) ) {

			$ids = explode( ',', $result['order_ids'] );

			foreach ( $ids as $id ) {
				$order_ids[] = [ 'id' => $id ];
			}
		}

		return $order_ids;
	}

	public function get_order_details( $ids ) {

		$order_id = implode( ',', array_map( function ( $entry ) {
			return $entry['id'];
		}, $ids ) );

		$order_view = limelight_curl( [
			'method'   => 'order_view',
			'order_id' => $order_id
		], 1, 0 );

		$order_view = isset( $order_view['data'] ) ? json_decode( $order_view['data'], 1 ) : 0;

		if ( $order_view <= 0 ) {
			return;
		}

		foreach ( $order_view as $key => $order ) {

			$products = [ '<ul>' ];

			foreach ( $order['products'] as $product ) {

				if ( $product['subscription_id'] ) {

					$id  = implode( '<br>', str_split( $product['subscription_id'], 8 ) );
					$id .= "<div style='font-size:70%;padding-top:10px;'>Order #{$key}</div>";

					$name    = "{$order['first_name']} {$order['last_name']}";
					$contact = <<<EX
<a class="dashicons-before dashicons-email-alt" href="mailto:{$order['email_address']}"> &nbsp; {$order['email_address']}</a><br>
<a class="dashicons-before dashicons-phone" href="tel:{$order['customers_telephone']}"> &nbsp; {$order['customers_telephone']}</a>
EX;

					$address2 = ! empty( $order['shipping_street_address2'] ) ? "{$order['shipping_street_address2']}<br>" : '';
					$shipping = <<<EX
{$order['shipping_street_address']}<br>
{$address2}
{$order['shipping_city']}<br>{$order['shipping_state']} {$order['shipping_postcode']}
EX;

					$address2 = ! empty( $order['billing_street_address2'] ) ? "{$order['billing_street_address2']}<br>" : '';
					$billing = <<<EX
{$order['billing_street_address']}<br>
{$address2}
{$order['billing_city']}<br>{$order['billing_state']} {$order['billing_postcode']}
EX;

					$details_head = "{$product['product_qty']} x {$product['name']} <span style='color:#a9a9a9;font-size:80%;'>({$product['product_id']}: {$product['sku']})</span>";

					$details_addl = <<<EX
next_product_id: {$product['next_subscription_product_id']}<br>
next_product: {$product['next_subscription_product']}<br>
is_add_on: {$product['is_add_on']}<br>
on_hold: {$product['on_hold']}<br>
EX;

					$api_data = get_option( 'limelight_api' );
					$crm_link = "https://{$api_data['appkey']}.limelightcrm.com/admin/orders.php?show_details=show_details&fromPost=1&show_by_id={$key}";
					$query    = [
						'post_type'      => 'products',
						'meta_key'       => 'id',
						'meta_value'     => $product['product_id'],
						'post_status'    => 'any',
						'posts_per_page' => 1
					];

					$post   = get_posts( $query );
					$image  = isset( $post[0]->ID ) ? get_the_post_thumbnail( $post[0]->ID, [ 60, 60 ] ): '';
					$image  = $image ? : '<img src="http://via.placeholder.com/60x60">';
					$r_date = $product['recurring_date'] != '0000-00-00' ? date( 'F j, Y', strtotime( $product['recurring_date'] ) ) : 'N/A';

					$order_details[] = [
						'id'             => "<h3>{$id}</h3>",
						'date'           => $order['acquisition_date'],
						'status'         => $order['order_status'],
						'name'           => "<b>{$name}</b>",
						'contact'        => $contact,
						'shipping'       => $shipping,
						'billing'        => $billing,
						'recurring_date' => $r_date,
						'line_items'     => "<h3>{$details_head}</h3><div style='width:80px;float:left;padding: 0 5px 5px 0;'>{$image}</div>" . $details_addl,
						'price'          => "<h3 style='text-align:right;'>\${$product['price']}</h3>",
						'link'           => "<a target='_blank' href='{$crm_link}'><input type='button' class='button' value='View Order &raquo;'></a>"
					];

					$products[] = "<li style='border-bottom: 1px solid #e5e5e5;'>{$product['product_id']}. {$product['name']} <span style='color:#aaa;font-size:70%;'>({$product['sku']})</span>  <span style='float:right;'>{$product['product_qty']} &nbsp; x &nbsp; \${$product['price']}</span></li>";
				}
			}

			$products[] = "</ul>";
			$products   = implode( "\n", $products );
			$products  .= 'Shipping: &nbsp; <b>#' . $order['shipping_id'] . '. ' . $order['shipping_method_name'] . '</b>';
		}

		return $order_details;
	}

	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			case 'date':
				$date = explode( '-', date( 'F j, Y-g:ia', strtotime( $item[ $column_name ] ) ) );
				return "{$date[0]}<br>{$date[1]}";

			default:
				return $item[ $column_name ];
		}
	}

	private function sort_data( $a, $b ) {

		$orderby = ! empty( $_GET['orderby'] ) ? (string) $_GET['orderby'] : 'id';
		$order   = ! empty( $_GET['order'] ) ? (string) $_GET['order'] : 'asc';
		$result  = strcmp( $a[ $orderby ], $b[ $orderby ] );

		return ( $order === 'asc' ) ? -$result : $result;
	}
}