jQuery( document ).ready( function() {

	if ( jQuery( '#ll-shipping-country option' ).length == 2 ) {
		jQuery( '#ll-shipping-country option:eq(1)' ).attr( 'selected', 'selected' );
	}
	limelightBuildStates( 'shipping' );

	jQuery( '#ll-shipping-country' ).on( 'change', function() {
		limelightBuildStates( 'shipping' );
	} );

	jQuery( '#ll-billing-country' ).on( 'change', function() {
		limelightBuildStates( 'billing' );
	} );

	jQuery( '#ll-billing-same' ).on( 'change', function() {

		let billing = jQuery( '#ll-billing-info' );

		if ( jQuery( 'input#ll-billing-same' ).is( ':checked' ) ) {
			billing.hide();
		} else {
			billing.show();
			jQuery( '#ll-billing-country' ).val( jQuery( '#ll-shipping-country' ).val() );
			limelightBuildStates( 'billing' );
		}
	} );

	jQuery( '#ll-gift-order' ).change( function() {

		let gift_area = jQuery( '#ll-gift-info' );

		if ( jQuery( 'input#ll-gift-order' ).is( ':checked' ) ) {
			gift_area.show();
		} else {
			gift_area.hide();
		}
	} );
} );