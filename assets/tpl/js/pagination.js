jQuery( document ).ready( function() {

	if ( lime_member_token == '' && lime_customer_id == '' ) {
		window.location.href = '<!--login-->';
	}
	limelightPagination( '<!--type-->' );
	
	jQuery( document.body ).on( 'change', '#ll-pagination', function() {
		limelightPagination( '<!--type-->' );
	} );
} );