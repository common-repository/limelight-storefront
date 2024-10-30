function onlyNumbers( event ) {
	return ( event.charCode >= 48 && event.charCode <= 57 );
}

jQuery( '#ll-cvv' ).keypress( function( event ) { return onlyNumbers( event ); } );
jQuery( '#ll-phone' ).keypress( function( event ) { return onlyNumbers( event ); } );
jQuery( '#ll-cc-num' ).keypress( function( event ) { return onlyNumbers( event ); } );

let date = new Date();
jQuery( 'select[name=expiry_month]' ).val( ( '00' + ( 1 + date.getMonth() ) ).slice( -2 ) );
jQuery( '#ll-cc-num' ).keypress( function() {

	let data = {
		'3' : 'amex',
		'4' : 'visa',
		'5' : 'master',
		'6' : 'discover'
	}

	jQuery.each( data, function( k, cc_type ) {

		if ( jQuery( '#ll-cc-num' ).val().startsWith( k ) ) {
			jQuery( '#ll-cc-type' ).val( cc_type );
		}
	} );
} );

jQuery( '#ll-cc-num' ).on( 'blur', function() {
	jQuery( '#ll-cc-num' ).css( { '-webkit-text-security' : 'circle' } )
} );

jQuery( '#ll-cc-num' ).on( 'focus', function() {
	jQuery( '#ll-cc-num' ).css( { '-webkit-text-security' : 'none' } );
} );