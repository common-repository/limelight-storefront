<?php
/************************************************************************
 * LimeLight Storefront - Wordpress Plugin
 * Copyright (C) 2017 Lime Light CRM, Inc.

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General private License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.

 * You should have received a copy of the GNU General private License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class LimelightErrors {

	const
		ERROR_101 = 'Order is 3DS and needs to go to bank url (use 3DS redirect method)',
		ERROR_123 = 'Prepaid Credit Cards Are Not Accepted',
		ERROR_200 = 'Invalid login credentials',
		ERROR_201 = 'three_d_redirect_url is required',
		ERROR_303 = 'Invalid upsell product Id of (XXX) found',
		ERROR_304 = 'Invalid first name of (XXX) found',
		ERROR_305 = 'Invalid last name of (XXX) found',
		ERROR_306 = 'Invalid shipping address1 of (XXX) found',
		ERROR_307 = 'Invalid shipping city of (XXX) found',
		ERROR_308 = 'Invalid shipping state of (XXX) found',
		ERROR_309 = 'Invalid shipping zip of (XXX) found',
		ERROR_310 = 'Invalid shipping country of (XXX) found',
		ERROR_311 = 'Invalid billing address1 of (XXX) found',
		ERROR_312 = 'Invalid billing city of (XXX) found',
		ERROR_313 = 'Invalid billing state of (XXX) found',
		ERROR_314 = 'Invalid billing zip of (XXX) found',
		ERROR_315 = 'Invalid billing country of (XXX) found',
		ERROR_316 = 'Invalid phone number of (XXX) found',
		ERROR_317 = 'Invalid email address of (XXX) found',
		ERROR_318 = 'Invalid credit card type of (XXX) found',
		ERROR_319 = 'Invalid credit card number of (XXX) found',
		ERROR_320 = 'Invalid expiration date of (XXX) found',
		ERROR_321 = 'Invalid IP address of (XXX) found',
		ERROR_322 = 'Invalid shipping id of (XXX) found',
		ERROR_323 = "CVV is required for tranType 'Sale'",
		ERROR_324 = 'Supplied CVV of (XXX) has an invalid length',
		ERROR_325 = 'Shipping state must be 2 characters for a shipping country of US',
		ERROR_326 = 'Billing state must be 2 characters for a billing country of US',
		ERROR_327 = 'Invalid payment type of XXX',
		ERROR_328 = 'Expiration month of (XXX) must be between 01 and 12',
		ERROR_329 = 'Expiration date of (XXX) must be 4 digits long',
		ERROR_330 = 'Could not find prospect record',
		ERROR_331 = 'Missing previous OrderId',
		ERROR_332 = 'Could not find original order Id',
		ERROR_333 = 'Order has been black listed',
		ERROR_334 = 'The credit card number or email address has already purchased this product(s)',
		ERROR_335 = 'Invalid Dynamic Price Format',
		ERROR_336 = 'checkRoutingNumber must be passed when checking is the payment type is checking or eft_germany',
		ERROR_337 = 'checkAccountNumber must be passed when checking is the payment type is checking or eft_germany',
		ERROR_338 = 'Invalid campaign to perform sale on.  No checking account on this campaign.',
		ERROR_339 = 'tranType missing or invalid',
		ERROR_340 = 'Invalid employee username of (XXX) found',
		ERROR_341 = 'Campaign Id (XXX) restricted to user (XXX)',
		ERROR_342 = 'The credit card has expired',
		ERROR_400 = 'Invalid campaign Id of (XXX) found',
		ERROR_411 = 'Invalid subscription field',
		ERROR_412 = 'Missing subscription field',
		ERROR_413 = 'Product is not subscription based',
		ERROR_414 = 'The product that is being purchased has a different subscription type than the next recurring product',
		ERROR_415 = 'Invalid subscription value',
		ERROR_600 = 'Invalid product Id of (XXX) found',
		ERROR_666 = 'User does not have permission to use this method',
		ERROR_667 = 'This user account is currently disabled',
		ERROR_668 = 'Unauthorized IP Address',
		ERROR_669 = 'Unauthorized to access campaign',
		ERROR_700 = 'Invalid method supplied',
		ERROR_705 = 'Order is not 3DS related',
		ERROR_800 = 'Transaction was declined',
		ERROR_900 = 'SSL is required to run a transaction',
		ERROR_901 = 'Alternative payment payer id is required for this payment type',
		ERROR_902 = 'Alternative payment token is required for this payment type',
		ERROR_1000 = 'Could not add record',
		ERROR_1001 = 'Invalid login credentials supplied',
		ERROR_1002 = 'Invalid method supplied';

	public $error, $errors, $error_codes = [];

	public function __construct( $error = 0 ) {

		$this->error       = $error;
		$this->errors      = get_option( 'limelight_errors' );
		$this->error_codes = $this->create_error_codes();

		add_action( 'admin_menu', [ $this, 'menu' ] );
		add_action( 'admin_init', [ $this, 'settings' ] );
	}

	public function menu() {
		add_submenu_page( 'limelight-admin', 'Error Responses', 'Error Responses', 'manage_options', 'error-responses', [ $this, 'error_page' ] );
	}

	public function error_page() {

		$html   = '';
		$notice = [];

		if ( ! empty( $_GET['settings-updated'] ) ) {

			$notice = [
				'msg'  => 'Your <b>Error Responses</b> have been updated successfully',
				'type' => 'success'
			];

			$html = <<<EX
<div class="notice notice-{$notice['type']} is-dismissible"> 
	<p><strong>{$notice['msg']}</strong></p>
</div>
EX;
		}

		echo <<<EX
{$html}
<div class="wrap">
<h1>LimeLight Error Responses</h1>
<form method="post" action="options.php">
EX;

		settings_fields( 'error_group' );
		do_settings_sections( 'error-settings' );
		submit_button();

		echo "</form></div>";
	}

	public function settings() {

		register_setting( 'error_group', 'limelight_errors',               [ $this, 'sanitize' ] );
		add_settings_section( 'error_section', 'Error Response Config',    [ $this, 'print_section_info' ], 'error-settings' );
		add_settings_field( 'show_error_code', 'Show Error Code #',        [ $this, 'code_num' ], 'error-settings', 'error_section' );
		add_settings_field( 'show_error_default', 'Show Default Messages', [ $this, 'default_msg' ], 'error-settings', 'error_section' );
		add_settings_section( 'error_section_sep', '',                     [ $this, 'print_section_info_sep' ], 'error-settings' );
		add_settings_section( 'error_section_b', 'Custom Messages',        [ $this, 'print_section_info_b' ], 'error-settings' );

		foreach ( $this->error_codes as $error => $message ) {

			$code = filter_var( $error, FILTER_SANITIZE_NUMBER_INT );
			add_settings_field( $error, 'Error Code ' . $code, [ $this, 'custom_msg' ], 'error-settings', 'error_section_b', [ 'num' => $code ] );
		}
	}

	public function print_section_info() {
		print 'Configure your error responses options:';
	}

	public function print_section_info_sep() {
		print '<hr>';
	}

	public function print_section_info_b() {
		print 'Customize your custom error response messages:';
	}

	protected function create_error_codes() {

		$return = [];
		$errors = [ 101, 123, 200, 201, 303, 304, 305, 306, 307, 308, 309, 310, 311, 312, 313, 314, 315, 316, 317, 318, 319, 320, 321, 322, 323, 324, 325, 326, 327, 328, 329, 330, 331, 332, 333, 334, 335, 336, 337, 338, 339, 340, 341, 342, 400, 411, 412, 413, 414, 415, 600, 666, 667, 668, 669, 700, 705, 800, 900, 901, 902, 1000, 1001, 1002 ];

		foreach ( $errors as $error ) {

			$message = '';
			$key     = "error_{$error}";

			if ( $current_errors = $this->errors ) {

				foreach ( $current_errors as $current_error => $current_message ) {

					if ( $current_error == $key ) {
						$message = $current_message;
					}
				}
			}

			$return[ $key ] = $message;
		}

		return $return;
	}

	public function code_num() {

		$checked = isset( $this->errors['show_error_code'] ) ? 'checked="checked"' : '';
		echo <<<EX
<input {$checked} id='show_error_code' name='limelight_errors[show_error_code]' type='checkbox' /> &nbsp; 
<i>Show the Error Code # with error messages</i>
EX;
	}

	public function default_msg() {

		$checked = isset( $this->errors['show_error_default'] ) ? 'checked="checked"' : '';
		echo <<<EX
<input {$checked} id='show_error_default' name='limelight_errors[show_error_default]' type='checkbox' /> &nbsp; 
<i>Show the default message along with your custom message</i>
EX;
	}

	public function custom_msg( $code ) {

		$error = "ERROR_{$code['num']}";
		$message = '<br><i>' . constant( 'self::' . $error ) . '</i>';
		printf('<input type="text" id="ERROR_' . $code['num'] . '" name="limelight_errors[ERROR_' . $code['num'] . ']" value="%s" />' . $message, isset( $this->errors['ERROR_' . $code['num'] ] ) ? esc_attr( $this->errors[ 'ERROR_' . $code['num'] ] ) : '' );
	}
}