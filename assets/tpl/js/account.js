jQuery( document ).on( 'submit', '#ll-account-form', function( e ) {
	e.preventDefault();
	limelightMyAccount();
} );

jQuery( function() {
	if ( lime_member_token == '' && lime_customer_id == '' ) {
		window.location.href = 'login';
	}
	limelightMyAccount();
} );