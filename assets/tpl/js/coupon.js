jQuery( '#ll-coupon-apply' ).on( 'click', function( event ) {

	event.preventDefault();
	limelightValidateCoupon();
} );

jQuery( '#ll-coupon-code' ).keypress( function( event ) {

	if ( ( event.keyCode ? event.keyCode : event.which ) == '13' ) {
		event.preventDefault();
		limelightValidateCoupon();
	}
} );