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

class LimelightCustomers {

	public function __construct() {

		$this->api      = get_option( 'limelight_api' );
		$this->campaign = get_option( 'limelight_campaign' );
		add_action( 'admin_menu', [ $this, 'add_customers_menu' ] );
	}

	public function add_customers_menu() {

		add_menu_page( 'Customers', 'Customers', 'manage_options', 'limelight-customers', [ $this, 'page' ], 'dashicons-groups', 4 );
		add_action( 'admin_head', [ $this, 'column_css' ] );
		add_action( 'admin_footer', [ $this, 'jdenticon_script' ] );
	}

	public function page() {

		$page   = new CustomersListTable();
		$page->prepare_items();
		$appkey = $this->api['appkey'] ? : 'www';

		echo '<div class="wrap"><h1 class="wp-heading-inline">Customers</h1>';

		if ( $this->campaign ) {
			echo "<p>These are your customers for <b>Campaign #{$this->campaign['id']}</b>. You can update customers at <a target='_blank' href='http://{$appkey}.limelightcrm.com/admin'>{$appkey}.limelightcrm.com</a>.</p>";
			$page->display();
		} else {
			echo 'Please configure your campaign settings to see your customers.';
		}

		echo '</div>';
	}

	public function column_css() {
		echo '<style type="text/css">.column-img {width:70px;}</style>';
	}

	public function jdenticon_script() {
		echo '<script src="//cdn.jsdelivr.net/npm/jdenticon@2.1.0" async></script>';
	}
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class CustomersListTable extends WP_List_Table {

	public function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$ids      = $this->get_customer_ids();
		usort( $ids, [ &$this, 'sort_data' ] );

		$perPage     = 10;
		$currentPage = $this->get_pagenum();
		$totalItems  = count( $ids );

		$this->set_pagination_args( [
			'total_items' => $totalItems,
			'per_page'    => $perPage
		] );

		$ids  = array_slice( $ids, ( ( $currentPage - 1 ) * $perPage ), $perPage );
		$data = $this->get_customer_details( $ids );

		$this->_column_headers = [ $columns, $hidden, $sortable ];
		$this->items = $data;
	}

	public function get_columns() {

		return [
			'img'    => '',
			'id'     => 'ID',
			'date'   => 'Created Date',
			'name'   => 'Customer Name',
			'email'  => 'Email',
			'phone'  => 'Phone',
			'orders' => 'Orders',
			'link'   => ''
		];
	}

	public function get_hidden_columns() {
		return [];
	}

	public function get_sortable_columns() {
		return [ 'id' => [ 'id', false ] ];
	}

	private function get_customer_ids() {

		$data     = [];
		$campaign = get_option( 'limelight_campaign' );
		$query    = [
			'method'      => 'customer_find',
			'campaign_id' => $campaign['id'],
			'start_date'  => date( 'm/d/Y', strtotime( '04/19/1983' ) ),
			'end_date'    => date( 'm/d/Y' )
		];

		$result = limelight_curl( $query, 1, 0 );

		if ( isset( $result['customer_ids'] ) ) {

			$ids = explode( ',', $result['customer_ids'] );

			foreach ( $ids as $id ) {
				$data[] = [ 'id' => $id ];
			}
		}

		return $data;
	}

	public function get_customer_details( $ids ) {

		$customer_details = [];

		foreach ( $ids as $k => $customer ) {

			$api_data      = get_option( 'limelight_api' );
			$customer_view = limelight_curl( [
				'method'      => 'customer_view',
				'customer_id' => $customer['id']
			], 1, 0 );

			$link = "https://{$api_data['appkey']}.limelightcrm.com/admin/customers/details.php?id=" . $customer['id'];

			$customer_details[] = [
				'img'    => '<svg width="80" height="80" data-jdenticon-value="' . $customer['id'] . $customer_view['first_name'] . $customer_view['last_name'] . '">No Image</svg>',
				'id'     => "<h3>{$customer['id']}</h3>",
				'date'   => $customer_view['date_created'],
				'name'   => "<h3>{$customer_view['first_name']} {$customer_view['last_name']}</h3>",
				'email'  => $customer_view['email'],
				'phone'  => $customer_view['phone'],
				'orders' => '<b>Total Orders: &nbsp; <span style="font-size:160%">' . $customer_view['order_count'] . '</span></b><br><br>' . str_replace( ',', ', ', $customer_view['order_list'] ),
				'link'   => "<a target='_blank' href='{$link}'><input type='button' class='button' value='View in CRM &raquo;'></a>"
			];
		}

		return $customer_details;
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