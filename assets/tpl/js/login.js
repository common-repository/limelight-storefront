jQuery( '#ll-login-form' ).on( 'submit', function( e ) {

	e.preventDefault();
	let email    = jQuery( '#ll-login-email' ).val();
	let pass     = jQuery( '#ll-login-pass' ).val();
	let pass_old = jQuery( '#ll-login-pass-old' ).val();
	let submit   = jQuery( '#ll-login-submit' );
	let data     = {
		'action'   : 'login_process',
		'email'    : email,
		'pass'     : pass,
		'pass_old' : pass_old,
		'aj'       : 1
	}

	lime_loading.show();

	jQuery.post( ll_ajax_url, data, function( res ) {

		res = JSON.parse( res );

		if ( res.response_code == '4010' ) {

			jQuery( '#ll-login-update' ).show();
			jQuery( '#ll-login-pass-label' ).text( 'New Password:' );
			jQuery( '#ll-login-pass' ).val( '' );
			jQuery( '#ll-login-pass-old' ).val( pass );
			submit.val( 'Update Password' ).prop( 'disabled', true );;

		} else if ( res.response_code != '100' ) {
			lime_error_overlay.show();
			lime_error_message.html( limelightErrorDisplay( res ) );
		} else {
			localStorage.setItem( 'limelight_member_token', res.data['member_token'] );
			localStorage.setItem( 'limelight_customer_ids', res.data['customer_ids'] );
			jQuery( 'body' ).append( res.redirect );
		}

		lime_loading.hide();
	} );
} );

jQuery( '#ll-login-pass-confirm' ).on( 'keyup', function() {

	let password   = jQuery( '#ll-login-pass' ).val();
	let password_v = jQuery( '#ll-login-pass-confirm' ).val();
	let submit     = jQuery( '#ll-login-submit' );
	let message    = jQuery( '#ll-login-msg' );

	if ( password == password_v && password.length > 7 ) {
		message.hide();
		submit.prop( 'disabled', false );
	} else {
		message.show();
		submit.prop( 'disabled', true );
	}
} );