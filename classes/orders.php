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

class LimelightOrders {

	public function __construct() {

		$this->api      = get_option( 'limelight_api' );
		$this->campaign = get_option( 'limelight_campaign' );
		add_action( 'admin_menu', [ $this, 'add_orders_menu' ] );
	}

	public function add_orders_menu() {

		add_menu_page( 'Orders', 'Orders', 'manage_options', 'limelight-orders', [ $this, 'page' ], 'dashicons-list-view', 4 );
		add_action( 'admin_head', [ $this, 'column_css' ] );
	}

	public function page() {

		$page   = new OrdersListTable();
		$page->prepare_items();
		$appkey = $this->api['appkey'] ? : 'www';

		echo '<div class="wrap"><h1 class="wp-heading-inline">Orders</h1>';

		if ( $this->campaign ) {
			echo "<p>These are your orders for <b>Campaign #{$this->campaign['id']}</b>. You can update orders at <a target='_blank' href='http://{$appkey}.limelightcrm.com/admin'>{$appkey}.limelightcrm.com</a>.</p>";
			$page->display();
		} else {
			echo 'Please configure your campaign settings to see your orders.';
		}

		echo '</div>';
	}

	public function column_css() {
		echo <<<HTML
<style type="text/css">
.column-line_items { white-space:nowrap; width: 20%; }
.column-status { white-space:nowrap; width: 25px; }
</style>
HTML;
	}
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class OrdersListTable extends WP_List_Table {

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
		$this->items           = $data;
	}

	public function get_columns() {

		return [
			'id'         => 'ID',
			'date'       => 'Date',
			'status'     => 'Status',
			'name'       => 'Customer',
			'contact'    => 'Contact',
			'shipping'   => 'Shipping',
			'billing'    => 'Billing',
			'line_items' => 'Items',
			'total'      => 'Total',
			'link'       => ''
		];
	}

	public function get_hidden_columns() {
		return [];
	}

	public function get_sortable_columns() {
		return [ 'id' => [ 'id', false ] ];
	}

	private function get_order_ids() {

		$campaign  = get_option( 'limelight_campaign' );
		$order_ids = [];
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

		$string_ids = implode( ',', array_map( function ( $entry ) {
			return $entry['id'];
		}, $ids ) );

		$order_view = limelight_curl( [
			'method'   => 'order_view',
			'order_id' => $string_ids
		], 1, 0 );

		if ( ! isset( $order_view['data'] ) ) {
			return;
		}

		$orders = json_decode( $order_view['data'], 1 );

		if ( $orders == 0 ) {
			return;
		}

		foreach ( $orders as $k => $order ) {

			$line_items = [ '<ul>' ];

			foreach ( $order['products'] as $product ) {

				$post = get_posts( [
					'post_type'      => 'products',
					'meta_key'       => 'id',
					'meta_value'     => $product['product_id'],
					'post_status'    => 'any',
					'posts_per_page' => 1
				] );

				$image        = isset( $post[0]->ID ) ? get_the_post_thumbnail( $post[0]->ID, [ 20, 20 ] ) : '';
				$image        = $image ? : '<img src="http://via.placeholder.com/20x20">';
				$recur_icon   = $product['subscription_id'] ? ' &nbsp <span style="font-size:40%;color:#ccc;" class="dashicons-before dashicons-image-rotate"></span>' : '';
				$line_items[] = "<li style='border-bottom: 1px solid #e5e5e5;'>{$image} &nbsp; {$product['product_id']}. {$product['name']} <span style='color:#aaa;font-size:70%;'>({$product['sku']})</span>  <span style='float:right;'>{$product['product_qty']} &nbsp; x &nbsp; \${$product['price']}</span>{$recur_icon}</li>";
			}

			$line_items[] = "</ul>";
			$line_items   = implode( "\n", $line_items );
			$line_items  .= 'Shipping: &nbsp; <b>#' . $order['shipping_id'] . '. ' . $order['shipping_method_name'] . '</b>';

			$contact_info = '<a class="dashicons-before dashicons-email-alt" href="mailto:' . $order['email_address'] . '"> &nbsp; ' . $order['email_address'] . '</a><br><a class="dashicons-before dashicons-phone" href="tel:' . $order['customers_telephone'] . '"> &nbsp; ' . $order['customers_telephone'] . '</a>';

			$billing   = $order['billing_street_address'] . '<br>';
			$billing  .= $order['billing_street_address2'] ? $order['billing_street_address2'] . '<br>' : '';
			$billing  .= $order['billing_city'] . '<br>' . $order['billing_state'] . ' ' . $order['billing_postcode'];

			$shipping  = $order['shipping_street_address'] . '<br>';
			$shipping .= $order['shipping_street_address2'] ? $order['shipping_street_address2'] . '<br>' : '';
			$shipping .= $order['shipping_city'] . '<br>' . $order['shipping_state'] . ' ' . $order['shipping_postcode'];

			$api_data  = get_option( 'limelight_api' );
			$crm_link  = "https://{$api_data['appkey']}.limelightcrm.com/admin/orders.php?show_details=show_details&fromPost=1&show_by_id={$k}";

			$order_details[] = [
				'id'         => "<h3>{$k}</h3>",
				'date'       => $order['acquisition_date'],
				'status'     => $order['order_status'],
				'name'       => $order['first_name'] . ' ' . $order['last_name'],
				'contact'    => $contact_info,
				'shipping'   => $shipping,
				'billing'    => $billing,
				'line_items' => $line_items,
				'total'      => "<h3 style='text-align:right;'>\${$order['order_total']}</h3>",
				'link'       => "<a target='_blank' href='{$crm_link}'><input type='button' class='button' value='View Order &raquo;'></a>"
			];
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