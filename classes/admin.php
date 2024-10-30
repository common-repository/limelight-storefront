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

require 'errors.php';
require 'orders.php';
require 'subscriptions.php';
require 'customers.php';
require 'feedback.php';
require 'campaign.php';
require 'advanced.php';

class LimelightAdmin {

	private $creds;

	public function __construct() {

		$this->creds = get_option( 'limelight_api' );
		$this->valid = get_option( 'limelight_validated' );

		add_action( 'admin_menu',   [ $this, 'menu' ] );
		add_action( 'admin_init',   [ $this, 'settings' ] );
		add_action( 'admin_footer', [ $this, 'green_titles' ] );

		new LimelightCampaign();
		new LimelightAdvanced();
		new LimelightOrders();
		new LimelightSubscriptions();
		new LimelightCustomers();
		new LimelightFeedback();
		new LimelightErrors();

		$this->shop = new LimelightShop();
		$this->shop->build_admin_menu();
	}

	public function menu() {

		add_menu_page(
			'LimeLight',
			'LimeLight Storefront',
			'manage_options',
			'limelight-admin',
			[ $this, 'page' ],
			plugins_url( 'limelight-storefront/assets/img/limelight-small.png' ),
			2
		);

		add_submenu_page(
			'limelight-admin',
			'API Credentials',
			'API Credentials',
			'manage_options',
			'limelight-admin'
		);
	}

	public function page() {

		$notice = [
			'msg'  => "Please check your <a href='admin.php?page=limelight-admin'>API Credentials</a>. Couldn't connect to LimeLightCRM.",
			'type' => 'error'
		];

		if ( $this->validate_credentials() ) {
			$notice = [
				'msg'  => 'You are successfully connected to LimeLightCRM. You can now configure your <a href="admin.php?page=campaign-settings">Campaign Settings</a>',
				'type' => 'success'
			];
		}
?>
		<div class="notice notice-<?php echo $notice['type']; ?> is-dismissible"> 
			<p><strong><?php echo $notice['msg']; ?></strong></p>
		</div>
		<div class="wrap">
			<h1>LimeLight API Credentials</h1>
			<form method="post" action="options.php">
			<?php
				settings_fields( 'api_group' );
				do_settings_sections( 'api-settings' );
				submit_button();
?>
			</form>
		</div>
		<?php
	}

	public function settings() {

		register_setting( 'api_group', 'limelight_api', [ $this, 'sanitize' ] );
		add_settings_section( 'api_section', 'Connect To Your LimeLightCRM API', [ $this, 'print_section_info' ], 'api-settings' );
		add_settings_field( 'user', 'Username', [ $this, 'user' ],   'api-settings', 'api_section' );
		add_settings_field( 'pass', 'Password', [ $this, 'pass' ],   'api-settings', 'api_section' );
		add_settings_field( 'appkey', 'AppKey', [ $this, 'appkey' ], 'api-settings', 'api_section' );
	}

	public function sanitize( $input ) {

		$new_input = [];
		if ( isset( $input['user'] ) ) $new_input['user']     = sanitize_text_field( $input['user'] );
		if ( isset( $input['pass'] ) ) $new_input['pass']     = sanitize_text_field( $input['pass'] );
		if ( isset( $input['appkey'] ) ) $new_input['appkey'] = sanitize_text_field( $input['appkey'] );
		return $new_input;
	}

	public function print_section_info() {
		print 'Enter your API credentials below:';
	}

	public function user() {
		printf( '<input type="text" id="user" name="limelight_api[user]" value="%s" />', isset( $this->creds['user'] ) ? esc_attr( $this->creds['user'] ) : '' );
	}

	public function pass() {
		printf( '<input type="password" id="pass" name="limelight_api[pass]" value="%s" />', isset( $this->creds['pass'] ) ? esc_attr( $this->creds['pass'] ) : '' );
	}

	public function appkey() {
		printf( '<input type="text" id="appkey" name="limelight_api[appkey]" value="%s" />', isset( $this->creds['appkey'] ) ? esc_attr( $this->creds['appkey'] ) : '' );
	}

	public function validate_credentials() {

		$res = limelight_curl( [ 'method' => 'validate_credentials' ] ) == '100' ? 1 : 0;
		update_option( 'limelight_validated', $res );
		return $res;
	}

	public function green_titles() {
?>
	<script type="text/javascript">
		var string = jQuery( 'h1' ).text().replace( 'LimeLight ', '<b style="color:#1fc93f;">LimeLight</b> ' );
		jQuery( 'h1' ).html( string );
	</script>
	<?php
	}
}