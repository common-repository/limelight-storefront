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

class LimelightCampaign {

	private $options, $validated;

	public $pages = [
		'limelight_prospect'         => [ 'content' => '[limelight_prospect]',         'title' => 'Prospect' ],
		'limelight_cart'             => [ 'content' => '[limelight_cart]',             'title' => 'Cart' ],
		'limelight_checkout'         => [ 'content' => '[limelight_checkout]',         'title' => 'Checkout' ],
		'limelight_onepage_checkout' => [ 'content' => '[limelight_onepage_checkout]', 'title' => 'One Page Checkout' ],
		'limelight_thankyou'         => [ 'content' => '[limelight_thankyou]',         'title' => 'Thank You' ],
		'limelight_account'          => [ 'content' => '[limelight_account]',          'title' => 'My Account' ],
		'limelight_orders'           => [ 'content' => '[limelight_orders]',           'title' => 'Order History' ],
		'limelight_subscriptions'    => [ 'content' => '[limelight_subscriptions]',    'title' => 'Subscriptions' ],
		'limelight_login'            => [ 'content' => '[limelight_login]',            'title' => 'Login' ]
	];

	public function __construct() {

		$this->campaign  = get_option( 'limelight_campaign' );
		$this->validated = get_option( 'limelight_validated' );

		add_action( 'admin_menu',                  [ $this, 'menu' ] );
		add_action( 'admin_init',                  [ $this, 'settings' ] );
		add_action( 'admin_footer',                [ $this, 'get_offers_javascript' ] );
		add_action( 'wp_ajax_get_campaign_offers', [ $this, 'get_campaign_offers' ] );
	}

	public function menu() {
		add_submenu_page( 'limelight-admin', 'Campaign Settings', 'Campaign Settings', 'manage_options', 'campaign-settings', [ $this, 'page' ] );
	}

	public function page() {

		$html        = '';
		$notice      = [];
		$updated     = isset( $_GET['settings-updated'] ) ? (boolean) $_GET['settings-updated'] : '';
		$update_only = isset( $this->campaign['update_only'] ) ? $this->campaign['update_only'] : '';

		if ( $updated ) {

			$notice = [
				'msg'  => 'Your Campaign Settings have been updated successfully. You can now configure your <a href="edit.php?post_type=products">Products</a>, <a href="edit.php?post_type=upsells">Upsells</a> & <a href="admin.php?page=general-settings">General Settings</a>',
				'type' => 'success'
			];

		} elseif ( ! $this->validated ) {

			$notice = [
				'msg'  => 'Please check your <a href="admin.php?page=limelight-admin">API Credentials</a>. There was a problem connecting to LimeLightCRM.',
				'type' => 'error'
			];
		}

		if ( ! empty( $notice ) ) {

			$html .= <<<EX
<div class="notice notice-{$notice['type']} is-dismissible"> 
	<p><strong>{$notice['msg']}</strong></p>
</div>
EX;
		}

		$html .= "<div class='wrap'><h1>LimeLight Campaign Settings</h1>";

		if ( $this->validated ) {

			echo "{$html}<form method='post' action='options.php'>";
			settings_fields( 'campaign_group' );
			do_settings_sections( 'campaign-settings' );
			submit_button( 'Save & Import Products' );

			$date = date( 'F j, Y, g:i a' );
			$html = "<input type='hidden' name='limelight_campaign[updated]' value='{$date}'></form>";
		}

		echo <<<EX
{$html}
<h2>Current Campaign</h2>
<table class="widefat" style="width:33%">
<thead>
	<tr>
		<th>Campaign ID</th>
		<th>Offer ID</th>
		<th>Last Updated</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>{$this->campaign['id']}</td>
		<td>{$this->campaign['offer']}</td>
		<td>{$this->campaign['updated']}</td>
	</tr>
</tbody>
</table>
EX;

		if ( $updated && ! $update_only ) {

			echo '<h2>Import Results</h2>';
			$this->delete_all_products();
			$this->import_products();
			$this->create_pages();

		} elseif ( $updated && $update_only ) {

			echo '<h2>Update Results</h2>';
			$this->import_products( 1 );
			$this->update_pages();
		}
	}

	public function settings() {

		register_setting( 'campaign_group', 'limelight_campaign',                    [ $this, 'sanitize' ] );
		add_settings_section( 'campaign_section', 'Configure LimeLightCRM Campaign', [ $this, 'print_section_info' ], 'campaign-settings' );
		add_settings_field( 'id', 'Campaign ID',                                     [ $this, 'id' ], 'campaign-settings', 'campaign_section' );
		add_settings_field( 'update_only', 'Update Only',                            [ $this, 'update_only' ], 'campaign-settings', 'campaign_section' );
		add_settings_field( 'offer', 'Offer ID',                                     [ $this, 'get_campaign_offers' ], 'campaign-settings', 'campaign_section' );
	}

	public function print_section_info() {
		print 'Select the campaign you\'d like to use:';
	}

	public function id() {

		$campaigns = $this->get_all_campaigns();
		parse_str( $campaigns, $parsed );
		preg_match( '/campaign_name=([^&]*)/', $campaigns, $names );

		$ids   = explode( ',', $parsed['campaign_id'] );
		$names = isset( $names[1] ) ? explode( ',', $names[1] ) : [];
		$html  = "<select id='campaign_id' name='limelight_campaign[id]'>";

		foreach ( $ids as $key => $v ) {

			$name   = urldecode( $names[ $key ] );
			$select = ( $this->campaign['id'] == $ids[ $key ] ) ? 'selected="selected"' : '';
			$html  .= "<option value='{$ids[$key]}' {$select}>{$ids[$key]}. {$name}</option>";
		}

		echo "{$html}</select>";
	}

	public function update_only() {

		echo $this->fill( $this->page_tpl( 'campaign_update_only' ), [
			'<!--checked-->' => isset( $this->campaign['update_only'] ) ? 'checked="checked"' : ''
		] );
	}

	public function get_offer_ids() {

		$id  = isset( $_POST['id'] ) ? $_POST['id'] : $this->campaign['id'];
		$res = limelight_curl( [
			'method'      => 'campaign_view',
			'campaign_id' => $id
		], 1, 0 );

		return isset( $res['offer_id'] ) ? $res['offer_id'] : '';
	}

	public function get_all_campaigns() {
		return limelight_curl( [ 'method' => 'campaign_find_active' ] );
	}

	public function additional_options( $input ) {

		foreach ( [ 'shipping_id', 'shipping_name', 'countries', 'payment_name', 'shipping_initial_price' ] as $option ) {

			$input_options = explode( ',', $input[ $option ] );

			foreach ( $input_options as $current ) {

				$opt[ $option ][] = $current;
			}
		}

		foreach ( $opt['payment_name'] as $name ) {

			switch ( $name ) {

				case 'amex':
					$payment_names[ $name ] = 'American Express';
					break;
				case 'visa':
					$payment_names[ $name ] = 'Visa';
					break;
				case 'master':
					$payment_names[ $name ] = 'Master Card';
					break;
				case 'discover':
					$payment_names[ $name ] = 'Discover Card';
					break;
			}
		}

		$opt['payment_name'] = ! empty( $payment_names ) ? $payment_names : [];
		$campaign_options    = array_merge( $this->campaign, $opt );
		update_option( 'limelight_campaign', $campaign_options );
		$this->save_states();
	}

	public function import_products( $mode = 0 ) {

		$success       = true;
		$campaign_view = limelight_curl( [
			'method'      => 'campaign_view',
			'campaign_id' => $this->campaign['id']
		], 1, 0 );

		$product_ids = $campaign_view['product_id'];

		if ( $this->campaign['offer'] ) {

			$offer_view = json_decode( limelight_curl( [
				'offer_id' => $this->campaign['offer'],
				'method'   => 'offer_view'
			] ), 1 );

			$product_ids = implode( ",", array_keys( $offer_view['data'][0]['products'] ));
		}

		$product_index = limelight_curl( [
			'product_id' => $product_ids,
			'method'     => 'product_index'
		] );

		$this->additional_options( $campaign_view );
		parse_str( $product_index, $product_data );

		foreach ( explode( ',', $product_data['response_code'] ) as $response ) {

			if ( ! in_array( $response, [ '100', '600' ] ) ) {
				$success = false;
			}
		}

		if ( $success == true ) {

			$html            = '';
			$names           = explode( ',', $product_data['product_name'] );
			$prices          = explode( ',', $product_data['product_price'] );
			$skus            = explode( ',', $product_data['product_sku'] );
			$max_quantitys   = explode( ',', $product_data['product_max_quantity'] );
			$rebill_prices   = isset( $product_data['product_price'] ) ? explode( ',', $product_data['product_price'] ) : '';
			$rebill_days     = isset( $product_data['product_rebill_day'] ) ? explode( ',', $product_data['product_rebill_day'] ) : '';
			$is_trials       = explode( ',', $product_data['product_is_trial'] );
			$category_names  = explode( ',', $product_data['product_category_name'] );
			$vertical_names  = explode( ',', $product_data['vertical_name'] );
			$costs           = explode( ',', $product_data['cost_of_goods_sold'] );
			$sub_types       = explode( ',', $product_data['subscription_type'] );
			$pres_rec_qtys   = explode( ',', $product_data['preserve_recurring_quantity'] );
			$is_shippables   = explode( ',', $product_data['product_is_shippable'] );
			$rebill_products = explode( ',', $product_data['product_rebill_product'] );
			$offer_ids       = $campaign_view['offer_id'] ? explode( ',', $campaign_view['offer_id'] ) : 0;

			preg_match( '/product_description=([^&]*)/', $product_index, $matches );
			$descriptions = isset( $matches[1] ) ? explode( ',', $matches[1] ) : [];
			$html         = '<h4>Products:</h4><table class="widefat"><tr><th>wp_id</th><th>product_id</th><th>name</th><th>category</th><th>base price</th><th>base sku</th></tr>';
			$product_ids  = explode( ',', $product_ids );

			foreach ( $product_ids as $k => $id ) {

				if ( ! isset( $id ) ) {
					break;
				}

				$insert_data = [
					'post_title'     => $names[ $k ],
					'post_name'      => '',
					'post_content'   => urldecode( $descriptions[ $k ] ) ? : 'No Description in CRM',
					'post_status'    => 'publish',
					'post_type'      => 'products',
					'ping_status'    => 'closed',
					'comment_status' => 'open'
				];

				$metas[ $id ] = [
					'id'                          => $id,
					'name'                        => $names[ $k ],
					'price'                       => $prices[ $k ],
					'sku'                         => $skus[ $k ],
					'max_quantity'                => $max_quantitys[ $k ],
					'category'                    => $category_names[ $k ],
					'description'                 => urldecode( $descriptions[ $k ] ),
					'has_rebill'                  => $rebill_prices[ $k ] ? 1 : 0,
					'rebill_price'                => $rebill_prices[ $k ] ? : '',
					'rebill_days'                 => isset( $rebill_days[ $k ] ) ? $rebill_days[ $k ] : '',
					'is_trial'                    => $is_trials[ $k ] ? : '',
					'cost'                        => $costs[ $k ] ? : '',
					'subscription_type'           => $sub_types[ $k ] ? : '',
					'preserve_recurring_quantity' => $pres_rec_qtys[ $k ] ? : '',
					'is_shippable'                => $is_shippables[ $k ] ? : '',
					'rebill_product'              => $rebill_products[ $k ] ? : ''
				];

				if ( $offer_ids ) {

					foreach ( $offer_ids as $oid ) {

						$offr = json_decode( limelight_curl( [
							'offer_id' => $oid,
							'method'   => 'offer_view'
						] ) );

						$billing_model_ids = array_keys( (array) $offr->data[0]->billing_models );

						$this->save_billing_model_details( $billing_model_ids );

						update_option( 'limelight_offers', [
							'offer_id'       => $offr->data[0]->id,
							'offer_name'     => $offr->data[0]->name,
							'billing_models' => $offr->data[0]->billing_models
						] );

						foreach ( $offr->data[0]->products as $key => $value ) {

							if ( $id == $key ) {

								$offer_meta[ $id ][ $oid ] = [
									'offer_id'       => $offr->data[0]->id,
									'offer_name'     => $offr->data[0]->name,
									'billing_models' => $offr->data[0]->billing_models
								];
							}
						}
					}

					if ( $this->campaign['offer'] ) {
						$metas[ $id ]['offers'] = json_encode( $offer_meta[ $id ] );
					}

				} else {
					update_option( 'limelight_offers', [] );
				}

				$data3 = limelight_curl( [
					'product_id' => $id,
					'method'     => 'product_attribute_index'
				], 1, 0 );

				if ( $variants = json_decode( $data3['data'] ) ) {

					foreach ( $variants as $key => $value ) {

						$variants_blob = [];

						foreach ( $value as $item ) {

							if ( is_array( $item ) && count( $item ) > 0 ) {
								$metas[ $id ]['variants_matrix'] = json_encode( $item );
							} else {
								$variants_blob[] = $item;
							}
						}
					}

					$metas[ $id ]['variants'] = json_encode( $variants_blob );

				} else {
					$metas[ $id ]['variants']        = '';
					$metas[ $id ]['variants_matrix'] = '';
				}

				$offer_meta = json_decode( $metas[ $id ]['offers'], 1 );

				if ( $mode == 1 ) {

					$produx = get_posts( [
						'post_type'      => 'products',
						'meta_key'       => 'id',
						'meta_value'     => $id,
						'post_status'    => 'any',
						'posts_per_page' => 1
					] );

					if ( ! empty( $produx[0]->post_title ) ) {
						unset( $insert_data['post_title'] );
					}

					if ( ! empty( $produx[0]->post_content ) ) {
						unset( $insert_data['post_content'] );
					}

					if ( ! empty( $produx[0]->ID ) ) {
						$wp_id = wp_update_post( array_merge( $insert_data, [ 'ID' => $produx[0]->ID ] ) );
					}

				} else {

					$produx = get_posts( [
						'post_type'      => 'products',
						'meta_key'       => 'id',
						'meta_value'     => $id,
						'post_status'    => 'any',
						'posts_per_page' => 1
					] );

					if ( ! isset( $produx[0]->ID ) ) {
						$page_id[ $id ] = wp_insert_post( $insert_data );
						$wp_id = $page_id[ $id ];
					} else {
						$wp_id = '';
					}
				}

				if ( ! empty( $wp_id ) ) {

					wp_set_object_terms( $wp_id, $this->create_categories( $category_names[ $k ] ), 'category' );

					$metas[ $id ]['wp_id'] = $wp_id;

					foreach ( $metas[ $id ] as $key => $val ) {

						if ( $key != 'description' ) {
							update_post_meta( $wp_id, $key, $val );
						}
					}

					$html .= $this->fill( $this->page_tpl( 'campaign_import_line' ), [
						'<!--wp_id-->'   => $wp_id,
						'<!--id-->'      => $id,
						'<!--names-->'   => $names[ $k ],
						'<!--c_names-->' => $category_names[ $k ],
						'<!--prices-->'  => $prices[ $k ],
						'<!--skus-->'    => $skus[ $k ]
					] );
				}
			}

			$html .= '</table>';
		}

		echo $html;
	}

	public function get_all_products() {

		return get_posts( [
			'post_type'   => 'products',
			'numberposts' => -1
		] );
	}

	public function delete_all_products() {

		foreach ( $this->get_all_products() as $product ) {
			wp_delete_post( $product->ID, true );
		}
	}

	public function delete_limelight_pages() {

		foreach ( $this->pages as $page ) {

			$posts = get_posts( [
				'post_type'      => 'page',
				'meta_key'       => 'll_page_type',
				'meta_value'     => str_replace( [ '[', ']' ], '', $page['content' ] ),
				'post_status'    => 'any',
				'posts_per_page' => 1
			] );

			if ( ! empty( $posts[0]->ID ) ) {
				wp_delete_post( $posts[0]->ID, true );
			}
		}
	}

	public function create_pages() {

		$this->delete_limelight_pages();
		$html = '<h4>Pages:</h4>';

		foreach ( $this->pages as $page ) {

			$id = wp_insert_post( [
				'post_title'     => $page['title'],
				'post_content'   => $page['content'],
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'ping_status'    => 'closed',
				'comment_status' => 'closed',
				'post_category'  => [0]
			] );

			add_post_meta( $id, 'll_page_type', str_replace( [ '[', ']' ], '', $page['content'] ) );
			$html .= $id . ' - ' . $page['title'] . '<hr>';
		}

		echo $html;
	}

	public function update_pages() {

		$html = '<h4>Pages:</h4>';

		foreach ( $this->pages as $page ) {

			$posts = get_posts( [
				'post_type'      => 'page',
				'meta_key'       => 'll_page_type',
				'meta_value'     => str_replace( [ '[', ']' ], '', $page['content'] ),
				'post_status'    => 'any',
				'posts_per_page' => 1
			] );

			if ( ! empty( $posts[0]->ID ) ) {

				wp_update_post( [
					'ID'             => $posts[0]->ID,
					'post_title'     => $page['title'],
					'post_content'   => $page['content'],
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'ping_status'    => 'closed',
					'comment_status' => 'closed'
				] );

				$html .= $posts[0]->ID . ' - ' . $page['title'] . '<hr>';
			}
		}

		echo $html;
	}

	protected function create_categories( $name )  {

		if ( $exist = get_cat_ID( 'Shop' ) ) {
			$cat_ids[] = $exist;
		} else {
			$cat_ids[] = wp_insert_category( [
				'cat_name' => 'Shop',
				'taxonomy' => 'category'
			] );
		}

		if ( $exist = get_cat_ID( $name ) ) {
			$cat_ids[] = $exist;
		} else {
			$cat_ids[] = wp_insert_category( [
				'cat_name'             => $name,
				'taxonomy'             => 'category',
				'category_parent'      => $cat_ids[0],
				'category_description' => 'Edit this category to change the description.'
			] );
		}

		return $cat_ids;
	}

	public function get_offers_javascript() {

		if ( isset( $_GET['page'] ) && $_GET['page'] == 'campaign-settings' ) {
			wp_enqueue_script( 'limelight-campaign', plugins_url( '/js/campaign.js' , __FILE__ ) );
		}
	}

	public function get_campaign_offers() {

		$html = '';

		if ( $offers = $this->get_offer_ids() ) {

			$offers = explode( ',', $offers );
			$html   = "<select id='offer_id' name='limelight_campaign[offer]'><option value=''>Select Offer</option>";

			foreach ( $offers as $key => $val ) {

				$name     = $this->get_offer_name( $val );
				$selected = ( $this->campaign['offer'] == $offers[ $key ] ) ? 'selected="selected"' : '';
				$html    .= "<option value='{$val}' {$selected}>{$val}. {$name}</option>";
			}

			$html .= '</select>';

		} else {
			$html .= '<i>(No offers available for selected campaign)</i><input name="limelight_campaign[offer]" type="hidden" value="">';
		}

		echo $html;

		if ( isset( $_POST['aj'] ) ) {
			wp_die();
		}
	}

	public function get_offer_name( $id ) {

		$res = json_decode( limelight_curl( [
			'offer_id' => $id,
			'method'   => 'offer_view'
		] ) );

		return $res->data[0]->name;
	}

	public function save_states() {

		$campaign = get_option( 'limelight_campaign' );
		$file     = plugins_url( 'limelight-storefront/assets/limelight_country_state_code_map.csv' );
		$items    = array_map( 'str_getcsv', file( $file ) );

		array_walk( $items, function ( &$a ) use ( $items ) {
			$a = array_combine( $items[0], $a );
		} );
		array_shift( $items );

		foreach ( $items as $item ) {

			foreach ( $campaign['countries'] as $country ) {

				if ( $country == $item['Abbreviation'] ) {
					$matrix[ $item['Abbreviation'] ] [ $item['State Code/Id'] ] = $item['State'];
				}
			}
		}

		update_option( 'limelight_states', $matrix );
	}

	public function save_billing_model_details( $ids = [] ) {

		$billing_models = [];

		if ( ! empty( $ids ) ) {

			foreach ( $ids as $id ) {

				$response = json_decode( limelight_curl( [
					'method' => 'billing_model_view',
					'id'     => $id
				] ), 1 );

				$billing_models[ $id ] = $response['data'];
			}
		}

		update_option( 'limelight_billing_models', $billing_models );
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
}