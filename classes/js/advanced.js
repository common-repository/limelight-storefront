function onepageProds() {

	let onepage_ids = getMultiSelects( 'onepage' );

	jQuery.post( ajaxurl, { 'action': 'build_onepage', 'ids': onepage_ids }, function( res ) {
			jQuery( '#onepage-section' ).html( res == 0 ? '' : res );
	} );
}

function getMultiSelects( id ) {

	let ids = [];

	jQuery( '#' + id + ' :selected' ).each( function( index, sel ) {
		ids[ index ] = jQuery( sel ).val();
	} );

	return ids;
}

function memberEventSection() {

	if ( jQuery( '#member_account' ).is( ':checked' ) ) {

		jQuery.post( ajaxurl, { 'action': 'build_membership' }, function( res ) {
			jQuery( '#membership-event-section' ).html( res || '' );
		} );

	} else {
		jQuery( '#membership-event-section' ).html( '' );
	}
}

function threedSection() {

	if ( jQuery( '#enable_threed' ).is( ':checked' ) ) {

		jQuery.post( ajaxurl, { 'action': 'build_threed' }, function( res ) {
			jQuery( '#threedverify-section' ).html( res || '' );
		} );

	} else {
		jQuery( '#threedverify-section' ).html( '' );
	}
}

function eDigitalSection() {

	if ( jQuery( '#enable_edigital' ).is( ':checked' ) ) {

		jQuery.post( ajaxurl, { 'action': 'build_edigital' }, function( res ) {
			jQuery( '#edigital-section' ).html( res || '' );
		} ).done( function() {
			eDigitalProducts();
		} );

	} else {
		jQuery( '#edigital-section' ).html( '' );
	}
}

function eDigitalProducts() {

	let campaign_id = jQuery( '#edigital_campaign option:selected' ).val();

	jQuery.post( ajaxurl, { 'action': 'edigital_products', 'campaign': campaign_id }, function( res ) {
		jQuery( '#edigital_product' ).html( res );
	} );
}

function checkAltPay() {
	if ( jQuery( '#enable_altpay' ).is( ':checked' ) ) {
		jQuery( '#enable_altpay' ).closest( 'table' ).find( 'tr:not(:first)' ).show();
	} else {
		jQuery( '#enable_altpay' ).closest( 'table' ).find( 'tr:not(:first)' ).hide();
	}
}

jQuery( document ).ready( function() {

	onepageProds();
	threedSection();
	eDigitalSection();
	memberEventSection();
	checkAltPay();

	jQuery( '#member_account' ).on( 'change', function() {
		memberEventSection();
	} );

	jQuery( '#onepage' ).on( 'change', function() {
		onepageProds();
	} );

	jQuery( '#enable_threed' ).on( 'change', function() {
		threedSection();
	} );

	jQuery( '#enable_edigital' ).on( 'change', function() {
		eDigitalSection();
	} );

	jQuery( '#edigital-section' ).on( 'change', '#edigital_campaign', function() {
		eDigitalProducts();
	} );

	jQuery( '.payment-type' ).on( 'click', function() {
		let payment_type = jQuery( this ).val()
		jQuery.post( ajaxurl, { 'action': 'build_' + payment_type }, function( res ) {
			jQuery( '.payment-section' ).html( '' );
			jQuery( '#' + payment_type + '_area' ).html( res || '' );
		} );
	} );

	jQuery( "input[name='limelight_advanced[payment_type]']:checked" ).click();

	jQuery( '#enable_altpay' ).on( 'change', function() {
		checkAltPay();
	} );
} );