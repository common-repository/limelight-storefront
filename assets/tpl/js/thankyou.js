function limelightGetNow() {

	const monthNames = [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ];

	let now    = new Date();
	let dd     = now.getDate();
	let mm     = now.getMonth();
	let yyyy   = now.getFullYear();
	let hour   = now.getHours();
	let min    = now.getMinutes();
	let period = 'am';

	if ( dd < 10 ) {
		dd = '0' + dd;
	}

	if ( min < 10 ) {
		min = '0' + min;
	}

	if ( hour > 12 ) {
		hour   = +hour - 12;
		period = 'pm';
	}

	let time = hour + ':' + min
	return monthNames[ mm ] + ' ' + dd + ', ' + yyyy + ' - ' + time + ' ' + period;
}

jQuery( document ).ready( function() {

	if ( lime_order_summary.length === 0 ) {
		window.location = ll_shop_category;
	}

	let cart_items;
	let result          = localStorage;
	let items           = [];
	let order_summary   = result['limelight_order_summary']  ? JSON.parse( result['limelight_order_summary'] ) : [];
	let upsell_summary  = result['limelight_upsell_summary'] ? JSON.parse( result['limelight_upsell_summary'] ) : [];
	let upsells_display = 'none';
	let now             = limelightGetNow();
	let subtotal        = result['limelight_subtotal'] || 0;
	let shipping_cost   = result['limelight_shipping'] || 0;
	let coupon_amount   = result['limelight_coupon_amount'] || '';
	let coupon_code     = result['limelight_coupon_code']   || '';
	let other_discounts = 0;
	let bp_order_total  = 0;
	let bp_order_tax    = 0;
	let bp_order_id     = '';
	let order_id        = order_summary['orderId'] || order_summary['order_id'];

	jQuery( '#ll-main-date' ).html( now );
	jQuery( '#ll-main-subtotal' ).html( '$' + subtotal );
	jQuery( '#ll-main-shipping' ).html( '$' + shipping_cost );

	if ( ! result['limelight_boltpay'] ) {

		jQuery( '#ll-main-id' ).html( order_id );
		jQuery( '#ll-main-customer-id' ).html( order_summary['customerId'] );
		jQuery( '#ll-main-total' ).html( '$' + order_summary['orderTotal'] );

	} else {

		jQuery.post( ll_ajax_url, { 'action': 'boltpay_order_view', 'order_id': order_summary['order_id'] }, function( res ) {

			res = JSON.parse( res );

			bp_order_id    = order_summary['order_id']
			bp_customer_id = res['customer_id'];
			bp_order_total = res['order_total'];

			jQuery( '#ll-main-id' ).html( bp_order_id );
			jQuery( '#ll-main-customer-id' ).html( bp_customer_id );
			jQuery( '#ll-main-total' ).html( '$' + bp_order_total );
			jQuery( '#ll-main-grandtotal' ).html( '$' + bp_order_total );
		} );
	}

	if ( result['limelight_onepage'] ) {

		//onepage
		jQuery.post( ll_ajax_url, { 'action': 'onepage_products', 'aj': '1' }, function( res ) {
			jQuery( '#ll-thankyou-main tbody' ).append( res );
		} ).done( function() {

			other_discounts = ( +subtotal + +shipping_cost - +coupon_amount ) - +order_summary['orderTotal'];

			if ( other_discounts != '' && other_discounts > 0 ) {
				jQuery( '#ll-main-other-discounts-area' ).css( { 'display': 'table-row' } );
				jQuery( '#ll-main-other-discounts-amount' ).html( '-$' + limelightNumberFormat( other_discounts ) );
			}
		} );

	} else {

		//cart
		cart_items = JSON.parse( result['limelight_cart'] );
		items      = [];

		let clean_price;

		jQuery.each( cart_items, function( i, field ) {

			field['line_total'] = limelightNumberFormat( +field['qty'] * field['price'].replace( /[^0-9.]/g, '' ) );
			subtotal           += +field['line_total'].replace( /[^0-9.]/g, '' );
			field['line_total'] = '$' + field['line_total'];
			field['price']      = '$' + field['price'].replace( /[^0-9.]/g, '' );

			let variant_html = '';
			let counter = 0;

			while ( ! ( field['variant_name_' + counter ] === undefined ) ) {
				variant_html += field[ 'variant_name_' + counter ] + ': ' + field[ 'variant_value_' + counter ] + '<br>';
				counter++;
			}

			field['variants'] = variant_html;

			items.push( field );
		} );
	}

	//totaling
	if ( ! result['limelight_onepage'] ) {

		if ( order_summary['shipping_cost'] ) {

			shipping_cost = order_summary['shipping_cost'];
			jQuery( '#ll-main-shipping' ).html( '$' + limelightNumberFormat( shipping_cost ) );
			jQuery( '#ll-main-subtotal' ).html( '$' + limelightNumberFormat( subtotal ) );

			other_discounts = +order_summary['orderTotal'] - ( +subtotal + +shipping_cost - +coupon_amount );

			if ( other_discounts != '' && other_discounts > 0 ) {
				jQuery( '#ll-main-other-discounts-area' ).css( { 'display': 'table-row' } );
				jQuery( '#ll-main-other-discounts-amount' ).html( '-$' + limelightNumberFormat( other_discounts ) );
			}
		}
	}

	if ( ! result['limelight_boltpay'] ) {

		//total + tax (non-boltpay)
		jQuery( '#ll-main-grandtotal' ).html( '$' + order_summary['orderTotal'] );
		jQuery( '#ll-main-tax' ).html( '$' + order_summary['orderSalesTaxAmount'] );
		jQuery( '#ll-main-tax-percent' ).html( order_summary['orderSalesTaxPercent'] );

	} else {

		//total + tax (boltpay)
		jQuery( '#ll-main-total' ).html( '$' + bp_order_total );
		jQuery( '#ll-main-grandtotal' ).html( '$' + bp_order_total );
		jQuery( '#ll-main-tax' ).html( '$0.00' );
		jQuery( '#ll-main-tax-percent' ).html( '0' );

	}

	if ( coupon_amount != '' && coupon_amount > 0 ) {
		jQuery( '#ll-main-coupon-area' ).css( { 'display': 'table-row' } );
		jQuery( '#ll-main-coupon-code' ).html( coupon_code );
		jQuery( '#ll-main-coupon-amount' ).html( '-$' + coupon_amount );
	}

	//template
	jQuery( '#ll-thankyou-main' ).loadTemplate( jQuery( '#ll-thankyou-item' ), items, { append: true } );

	//upsold
	let upsolds = ( result['limelight_upsolds'] ) ? JSON.parse( result['limelight_upsolds'] ) : [];
	items       = [];

	jQuery( '#ll-upsell-date' )       .html( now );
	jQuery( '#ll-upsell-customer-id' ).html( order_summary['customerId'] );

	let grand_total = 0;
	let tax_amount  = 0;
	let tax_percent = 0;
	let order_ids   = [];

	jQuery.each( upsell_summary, function( i, upsell ) {

		order_ids.push( upsell['order_id'] );
		grand_total += +upsell['orderTotal'];
		tax_amount  += +upsell['orderSalesTaxAmount'];
		tax_percent  = upsell['orderSalesTaxPercent'];
	} );

	if ( upsolds.length > 0 ) {

		subtotal = 0;

		jQuery.each( upsolds, function( i, field ) {

			field['quantity']   = field['quantity'];
			field['line_total'] = field['price'].replace( /\,/g, '' );
			subtotal           += +field['line_total'].replace( /\,/g, '' );
			field['price']      = '$' + field['price'];
			field['line_total'] = '$' + limelightNumberFormat(+field['line_total'] * +field['quantity']);

			items.push( field );
		} );

		//totalling (upsells)
		shipping_cost = +grand_total - +subtotal;

		jQuery( '#ll-upsell-shipping' )   .html( '$' + limelightNumberFormat( shipping_cost ) );
		jQuery( '#ll-upsell-subtotal' )   .html( '$' + limelightNumberFormat( subtotal ) );
		jQuery( '#ll-upsell-grandtotal' ) .html( '$' + limelightNumberFormat( grand_total ) );
		jQuery( '#ll-upsell-id' )         .html( order_ids.join( ',' ) );
		jQuery( '#ll-upsell-total' )      .html( '$' + limelightNumberFormat( grand_total ) );
		jQuery( '#ll-upsell-tax' )        .html( '$' + limelightNumberFormat( tax_amount ) );
		jQuery( '#ll-upsell-tax-percent' ).html( tax_percent );

		//template
		jQuery( '#ll-thankyou-upsold' ).loadTemplate( jQuery( '#ll-thankyou-item' ), items, { append: true } );

		upsells_display = 'block';
		jQuery( '#ll-upsell-area' ).css( { 'display' : upsells_display } );
	}

	let localkeys = [
		'limelight_order_summary',
		'limelight_upsell_summary',
		'limelight_upsells',
		'limelight_upsolds',
		'limelight_onepage',
		'limelight_cart',
		'limelight_subtotal',
		'limelight_shipping',
		'limelight_square_token',
		'limelight_square_nonce',
	];

	jQuery.each( localkeys, function( i, val ) {
		localStorage.removeItem( val );
	} );
} );