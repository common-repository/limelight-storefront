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

class LimelightShop {

	private $options;

	public $info = [
		'name'     => '',
		'phone'    => '',
		'address1' => '',
		'address2' => '',
		'city'     => '',
		'state'    => '',
		'zip'      => ''
	];

	public function __construct() {
		$this->info = get_option( 'limelight_shop' );
	}

	public function build_admin_menu() {
		add_action( 'admin_menu', [ $this, 'menu' ] );
		add_action( 'admin_init', [ $this, 'settings' ] );
	}

	public function menu() {
		add_submenu_page( 'limelight-admin', 'Shop Settings', 'Shop Settings', 'manage_options', 'shop-settings', [ $this, 'page' ] );
	}

	public function page()	{

		$html = '';

		if ( ! empty( $_GET['settings-updated'] ) ) {

			$notification = [
				'msg'  => 'Your Shop Settings have been updated successfully',
				'type' => 'success'
			];

			$html = <<<EX
<div class="notice notice-{$notification['type']} is-dismissible"> 
	<p><strong>{$notification['msg']}</strong></p>
</div>
EX;
		}

		echo <<<EX
{$html}
<div class="wrap">
	<h1>LimeLight Shop Settings</h1>
	<form method="post" action="options.php">
EX;

		settings_fields( 'shop_group' );
		do_settings_sections( 'shop-settings' );
		submit_button();

		echo '</form></div>';
	}

	public function settings() {

		register_setting( 'shop_group',       'limelight_shop', [ $this, 'sanitize' ] );
		add_settings_section( 'shop_section', 'Shop Details',   [ $this, 'print_section_info' ], 'shop-settings' );
		add_settings_field( 'name',           'Name',           [ $this, 'name' ], 'shop-settings', 'shop_section' );
		add_settings_field( 'phone',          'Phone',          [ $this, 'phone' ], 'shop-settings', 'shop_section' );
		add_settings_field( 'address1',       'Address 1',      [ $this, 'address1' ], 'shop-settings', 'shop_section' );
		add_settings_field( 'address2',       'Address 2',      [ $this, 'address2' ], 'shop-settings', 'shop_section' );
		add_settings_field( 'city',           'City',           [ $this, 'city' ], 'shop-settings', 'shop_section' );
		add_settings_field( 'state',          'State',          [ $this, 'state' ], 'shop-settings', 'shop_section' );
		add_settings_field( 'zip',            'Zip',            [ $this, 'zip' ], 'shop-settings', 'shop_section' );
	}

	public function print_section_info() {
		print 'You can use these as shortcodes (on the right) to show the values throughout your site:';
	}

	public function name()	{
		printf( '<input type="text" id="name" name="limelight_shop[name]" value="%s" /> &nbsp; <i>[limelight_shop_name]</i>', isset( $this->info['name'] ) ? esc_attr( $this->info['name'] ) : '' );
	}

	public function phone() {
		printf( '<input type="text" id="phone" name="limelight_shop[phone]" value="%s" /> &nbsp; <i>[limelight_shop_phone]</i>', isset( $this->info['phone'] ) ? esc_attr( $this->info['phone'] ) : '' );
	}

	public function address1() {
		printf( '<input type="text" id="address1" name="limelight_shop[address1]" value="%s" /> &nbsp; <i>[limelight_shop_address1]</i>', isset( $this->info['address1'] ) ? esc_attr( $this->info['address1'] ) : '' );
	}

	public function address2()	{
		printf( '<input type="text" id="address2" name="limelight_shop[address2]" value="%s" /> &nbsp; <i>[limelight_shop_address2]</i>', isset( $this->info['address2'] ) ? esc_attr( $this->info['address2'] ) : '' );
	}

	public function city()	{
		printf( '<input type="text" id="city" name="limelight_shop[city]" value="%s" /> &nbsp; <i>[limelight_shop_city]</i>', isset( $this->info['city'] ) ? esc_attr( $this->info['city'] ) : '' );
	}

	public function state() {
		printf( '<input type="text" id="state" name="limelight_shop[state]" value="%s" /> &nbsp; <i>[limelight_shop_state]</i>', isset( $this->info['state'] ) ? esc_attr( $this->info['state'] ) : '' );
	}

	public function zip() {
		printf( '<input type="text" id="zip" name="limelight_shop[zip]" value="%s" /> &nbsp; <i>[limelight_shop_zip]</i>', isset( $this->info['zip'] ) ? esc_attr( $this->info['zip'] ) : '' );
	}

	public function sc_name()	{
		return $this->info['name'];
	}

	public function sc_phone() {
		return $this->info['phone'];
	}

	public function sc_address1()	{
		return $this->info['address1'];
	}

	public function sc_address2()	{
		return $this->info['address2'];
	}

	public function sc_city()	{
		return $this->info['city'];
	}

	public function sc_state() {
		return $this->info['state'];
	}

	public function sc_zip() {
		return $this->info['zip'];
	}
}
?>