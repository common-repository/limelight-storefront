jQuery( document ).ready( function() {

	if ( localStorage.getItem( 'limelight_affiliates' ) !== null )
		limelightBuildAffiliates( 'll-prospect-form' );

	jQuery( '#ll-prospect-form' ).on( 'submit', function( e ) {

		e.preventDefault();
		lime_loading.show();
		let prospect = {};
		let fields   = jQuery( '#ll-prospect-form' ).serializeArray();

		jQuery.each( fields, function( i, field ) {
			prospect[ field.name ] = field.value;
		} );

		jQuery.post( ll_ajax_url, { 'action': 'prospect_process', 'prospect': prospect }, function( res ) {

			res = JSON.parse( res );

			if ( res.errorFound == 0 ) {
				window.location.href = ll_onepage_checkout_page;
				prospect['prospectId'] = res.prospectId;
				localStorage.setItem( 'limelight_prospect', JSON.stringify( prospect ) );
			} else {
				lime_error_overlay.show();
				lime_error_message.html( limelightErrorDisplay( res ) );
			}
		} ).always( function() {
			lime_loading.hide();
		} );
	} );
} );