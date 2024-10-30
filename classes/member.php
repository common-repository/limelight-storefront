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

class LimelightMember {

	public function __construct() {

	}

	public function login( $input ) {

		return limelight_curl( [
			'method'          => 'member_login',
			'email'           => $input['email'],
			'member_password' => $input['pass']
		] );
	}

	public function create( $input ) {

		return json_decode( limelight_curl( [
			'method'      => 'member_create',
			'email'       => $input['email'],
			'customer_id' => $input['customerId']
		] ), 1 );
	}

	public function update( $input ) {

		return limelight_curl( [
			'method'                  => 'member_update',
			'email'                   => $input['email'],
			'current_member_password' => $input['old'],
			'new_member_password'     => $input['new'],
		] );
	}
}
?>