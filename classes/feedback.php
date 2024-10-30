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

class LimelightFeedback {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'menu' ] );
	}

	public function menu() {
		add_submenu_page( 'limelight-admin', 'Submit Feedback', 'Submit Feedback', 'manage_options', 'submit-feedback', [ $this, 'page' ] );
	}

	public function page() {

		$html   = '';
		$notice = '';
		$this->options = get_option( 'feedback' );

		if ( isset( $_POST['feedback'] ) ) {

			$notice = [
				'msg'  => 'Your feedback was successfully sent.',
				'type' => 'success'
			];

			if ( $this->send_feedback() == false ) {

				$notice = [
					'msg'  => 'There was a problem sending your feedback. Please check your WP mail settings.',
					'type' => 'error'
				];

			}
		}

		if ( ! empty( $notice ) ) {

			$html = <<<EX
<div class="notice notice-{$notice['type']} is-dismissible">
	<p><strong>{$notice['msg']}</strong></p>
</div>
EX;
		}

		echo <<<EX
{$html}
<div class="wrap">
	<h1>LimeLight Submit Feedback</h1>
	<form method="post">
<h2>Get In Contact With LimeLightCRM</h2>
Send us your questions, comments & feedback on our plugin:
<table class="form-table">
	<tbody>
		<tr><th scope="row">Email</th><td><input type="email" id="email" name="feedback[email]"></td></tr>
		<tr><th scope="row">Subject</th><td><input type="text" id="subject" name="feedback[subject]"></td></tr>
		<tr><th scope="row">Message</th><td><textarea rows="5" cols="50" id="message" name="feedback[message]"></textarea></td></tr>
	</tbody>
</table>
EX;
		submit_button( 'Send Feedback' );
		echo "</form></div>";
	}

	public function send_feedback() {

		$subject = ! empty( $_POST['feedback']['subject'] ) ? "WP Feedback: {$_POST['feedback']['subject']}" : 'No Subject';
		$body    = ! empty( $_POST['feedback']['email'] ) ? "{$_POST['feedback']['email']}<br>" : '';
		$body    = ! empty( $_POST['feedback']['subject'] ) ? "{$body}{$_POST['feedback']['subject']}<br>" : $body;
		$body    = ! empty( $_POST['feedback']['message'] ) ? "{$body}{$_POST['feedback']['message']}" : $body;

		return wp_mail( 'support@limelightcrm.com', $subject, $body, [ 'Content-Type: text/html; charset=UTF-8' ] );
	}
}