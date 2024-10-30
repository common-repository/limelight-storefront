jQuery( document ).ready( function() {

	let auth_payment;
	let auth_new_order;
	let payment_token;

	//tokenize_payment
	limelightTokenizeAuth( function( result ) {
		auth_payment = result['data']['token'];
	}, 'https://' + ll_appkey + '.limelightcrm.com/api/v2/token/tokenize_payment' ); 

	//new_order
	limelightTokenizeAuth( function( result ) {
		auth_new_order = result['data']['token'];
	}, 'https://' + ll_appkey + '.limelightcrm.com/api/v2/token/new_order' );

	//new_order_with_prospect
	if ( localStorage.getItem( 'limelight_prospect' ) !== null ) {

		limelightTokenizeAuth( function( result ) {
			auth_new_order = result['data']['token'];
		}, 'https://' + ll_appkey + '.limelightcrm.com/api/v2/token/new_order_with_prospect' );
	}

	let form_id = 'll-onepage-checkout-form';

	if ( jQuery( '#ll-checkout-form' ).length ) {

		if ( localStorage.getItem( 'limelight_cart' ) === null )
			window.location = ll_shop_category;

		form_id = 'll-checkout-form';
		limelightApplyCart();
	}

	if ( localStorage.getItem( 'limelight_affiliates' ) !== null )
		limelightBuildAffiliates( 'll-checkout-form' );

	if ( localStorage.getItem( 'limelight_prospect' ) !== null )
		limelightApplyProspect();

	jQuery( '#ll-coupon-code' ).val( lime_coupon );
	limelightValidateCoupon();
	limelightTotalling();

	jQuery( `#${form_id}` ).on( 'submit', function( e ) {

		lime_loading.show();
		e.preventDefault();

		let cc_num        = jQuery( '#ll-cc-num' ).val();
		let cc_type       = jQuery( '#ll-cc-type' ).val();
		let cc_mm         = jQuery( '#ll-expiry-month' ).val();
		let cc_yyyy       = jQuery( '#ll-expiry-year option:selected' ).text();
		let cc_cvv        = jQuery( '#ll-cvv' ).val();
		let cc_info       = {
			'card_number' : cc_num,
			'cvv'         : cc_cvv,
			'expiry'      : cc_mm + '-' + cc_yyyy,
			'brand'       : cc_type
		}

		limelightTokenizePayment( function( result ) {
			limelightCheckoutProcess( result['data']['token'], auth_new_order );
		}, auth_payment, cc_info );
	} );

	jQuery( '#ll-shipping-id' ).on( 'change', function() {
		limelightTotalling();

		if ( jQuery( '#ll-coupon-code' ).val() )
			limelightValidateCoupon();
	} );
} );