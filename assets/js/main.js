var lime_loading       = jQuery( '#ll-loading-overlay' );
var lime_alert         = jQuery( '#ll-response-message' );
var lime_error_message = jQuery( '#ll-error-message' );
var lime_error_overlay = jQuery( '#ll-error-overlay' );
var lime_cart          = localStorage['limelight_cart']          ? JSON.parse( localStorage['limelight_cart'] ) : [];
var lime_order_summary = localStorage['limelight_order_summary'] ? JSON.parse( localStorage['limelight_order_summary'] ) : [];
var lime_prospect      = localStorage['limelight_prospect']      || [];
var lime_affiliates    = localStorage['limelight_affiliates']    || [];
var lime_member_token  = localStorage['limelight_member_token']  || '';
var lime_customer_id   = localStorage['limelight_customer_ids']  || '';
var lime_coupon        = localStorage['limelight_coupon_code']   || '';

if ( ! String.prototype.startsWith ) {
	String.prototype.startsWith = function( search, pos ) {
		return this.substr( ! pos || pos < 0 ? 0 : +pos, search.length ) === search;
	};
}

function limelightNumberFormat( number ) {

	number    = number.toString().split( '.' );
	number[0] = number[0].replace( /[^0-9.]/g, '' );
	number    = number.join( '.' );

	return parseFloat( number ).toFixed( 2 ).replace( /(\d)(?=(\d\d\d)+(?!\d))/g, '$1,' );
}

function limelightTotalling() {

	let subtotal = jQuery( '#ll-sub-total' ).html().replace( ',', '' );
	let selected = jQuery( '#ll-shipping-id option:selected' ).html().split( ' - $' );
	let shipping = selected[1];
	let total    = +shipping + +subtotal.replace( /\,/g, '' );

	jQuery( '#ll-shipping-cost' ).html( selected[1] );
	jQuery( '#ll-grand-total' ).html( limelightNumberFormat( total ) );
}

function limelightValidateCoupon() {

	let code = jQuery( '#ll-coupon-code' ).val();

	if ( code ) {

		lime_loading.show();
		let sid    = jQuery( '#ll-shipping-id option:selected' ).val();
		let email  = jQuery( '#ll-email' ).val();
		let matrix = jQuery( '#ll-matrix :input' ).serializeArray();
		let data   = {
			'action'     : 'validate_coupon',
			'sid'        : sid,
			'promo_code' : code,
			'email'      : email,
			'matrix'     : matrix
		}

		jQuery.post( ll_ajax_url, data, function( res ) {

			res = JSON.parse( res );

			if ( res['response_code'] == '100' && res['coupon_amount'] != '0' ) {

				let total     = jQuery( '#ll-grand-total' ).html().replace( /,/g, '' );
				let new_total = +total - +res['coupon_amount'].replace( /,/g, '' );
				new_total     = new_total.toFixed( 2 );

				jQuery( '#ll-coupon-apply' ).prop( 'disabled', true );
				jQuery( '#ll-grand-total' ).html( '<s>' + limelightNumberFormat( total ) + '</s><br>$' + limelightNumberFormat( new_total ) );

				localStorage.setItem( 'limelight_coupon_amount', res['coupon_amount'] );
				localStorage.setItem( 'limelight_coupon_code', code );

			} else {
				jQuery( '#ll-coupon-apply' ).prop( 'disabled', false );
			}

			if ( res['coupon_amount'] == 0 ) {
				jQuery( '#ll-coupon-code' ).val( '' );
			}

			jQuery( '#ll-coupon-area' ).css( { 'display' : 'block' } );
			jQuery( '#ll-coupon-area' ).html( res['message'] );

		} ).always( function() {
			lime_loading.hide();
		} );
	}
}

function limelightRemoveCoupon() {

	lime_loading.show();
	localStorage.removeItem( 'limelight_coupon_amount' );
	localStorage.removeItem( 'limelight_coupon_code' );
	window.location.reload();
}

function limelightArchiveAddToCart( id ) {

	lime_loading.show();

	let freq_name  = jQuery( "#ll-archive-form-" + id + " select[name='frequency'] option:selected" ).text();
	let freq_count = jQuery( "#ll-archive-form-" + id + " [name='frequency']" ).length;

	if ( +freq_count > 1 || freq_name != 'Straight Sale' ) {
		jQuery( "#ll-archive-form-" + id + " [name='frequency_name']" ).val( freq_name );
	}

	let fields         = jQuery( '#ll-archive-form-' + id ).serializeArray();
	let product        = {};
	let response       = { 'msg': 'Added To Cart &nbsp; <a class="badge badge-secondary badge-warning" href="' + ll_cart_page + '">View Cart</a>', 'success': 1 }
	let matrix         = '';
	let variant_names  = [];
	let variant_values = [];
	let match;
	let pick;

	jQuery.each( fields, function( i, field ) {

		product[ field.name ] = field.value;

		if ( field.name == 'variants_matrix' && field.value != '' ) {
			matrix = JSON.parse( field.value );
		}

		if ( ( field.name.startsWith( 'variant_value_' ) && field.value == '' ) || ( field.name == 'frequency' && field.value == '' ) ) {
			response = { 'msg': 'Please Select Options', 'success': 0 }
		}

		if ( field.name.startsWith( 'variant_name_' ) ) {
			index = field.name.replace( 'variant_name_', '' );
			variant_names[ index ] = JSON.stringify( field.value );
		}

		if ( field.name.startsWith( 'variant_value_' ) ) {
			index = field.name.replace( 'variant_value_', '' );
			variant_values[ index ] = JSON.stringify( field.value );
		}
	} );

	jQuery.each( matrix, function( x, line ) {

		match = 1;

		jQuery.each( variant_names, function( z, name ) {

			name = name.toLowerCase();

			if ( line[ JSON.parse( name ) ] != JSON.parse( variant_values[ z ] ) ) {
				match = 0;
			}
		} );

		if ( match == 1 ) {
			pick = line;
		}
	} );

	if ( pick ) {
		product['max_quantity'] = pick['max_quantity'];
		product['price']        = pick['price'];
		product['sku']          = pick['sku'];
		jQuery( '#ll-product-price-' + id ).html( '$' + pick['price'] );
	}

	if ( product['qty'] > product['max_quantity'] && product['max_quantity'] != '0' ) {
		product['qty']  = product['max_quantity'];
		response['msg'] = 'Max Quantity (' + product['qty'] + ') Added To Cart';
	}

	if ( lime_cart.length > 0 ) {

		let exists = false;

		jQuery.each( lime_cart, function( key, itm ) {

			if ( ( product['id'] == itm['id'] ) && ( product['frequency'] == itm['frequency'] ) ) {
				exists     = true;
				exists_key = key;
			}
		} );

		if ( exists ) {

			lime_cart[ exists_key ]['qty'] = +product['qty'] + +lime_cart[ exists_key ]['qty'];

			if ( lime_cart[ exists_key ]['qty'] > product['max_quantity'] && product['max_quantity'] != '0' ) {
				lime_cart[ exists_key ]['qty'] = product['max_quantity'];
			}
		} else {

			if ( response['success'] != 0 ) {
				lime_cart.push( product );
			}
		}
	} else {

		if ( response['success'] != 0 ) {
			lime_cart.push( product );
		}
	}

	localStorage.setItem( 'limelight_cart', JSON.stringify( lime_cart ) );
	lime_loading.hide();

	let lime_alert = jQuery( '#ll-response-message-' + id );
	lime_alert.show();
	lime_alert.html( response['msg'] );
	lime_alert.css( { 'display': 'block' } );

	if ( response['success'] == 1 ) {

		if ( loc = window[ ll_addtocart_redirect ] ) {
			window.location.href = loc;
		}
	}
}

function limelightQuantityUpdate( id, freq, qty ) {

	lime_loading.show();

	if ( id != 0 ) {

		let items = [];

		jQuery.each( lime_cart, function( i, field ) {

			if ( field['id'] == id && field['frequency'] == freq ) {
				field['qty'] = ( +qty > +field['max_quantity'] ) ? field['max_quantity'] : qty;
			}

			if ( field['qty'] != 0 ) {
				items.push( field );
			}
		} );

		localStorage.setItem( 'limelight_cart', JSON.stringify( items ) );
	}

	window.location.reload();
}

function limelightBuildStates( type ) {

	let country = jQuery( '#ll-' + type + '-country' ).val();
	let data    = {
		'action'       : 'field_states',
		'aj'           : '1',
		'country'      : country,
		'address_type' : type
	}

	jQuery.post( ll_ajax_url, data, function( res ) {
		jQuery( '#ll-' + type + '-state' ).replaceWith( res );
	} );
}

function limelightApplyProspect() {

	let prospect_fields  = JSON.parse( lime_prospect );
	let prospect_country = 'US';
	let prospect_state   = '';

	jQuery.each( prospect_fields, function( name, value ) {

		let field = jQuery( "input[name='" + name + "']" );

		if ( ! ( field === undefined ) ) {

			field.val( value );

			if ( name == 's_country' ) {
				prospect_country = value;
			}

			if ( name == 's_state' ) {
				prospect_state = value;
			}
		}
	} );

	if ( ! ( prospect_country === undefined ) ) {
		jQuery( '#ll-shipping-country option[value="' + prospect_country + '"]' ).prop( 'selected', true );
	}

	if ( ! ( prospect_state === undefined ) ) {
		jQuery( '#ll-shipping-state option[value="' + prospect_state + '"]' ).prop( 'selected', true );
	}
}

function limelightErrorDisplay( response ) {

	jQuery.post( ll_ajax_url, { 'action': 'get_error', 'response': response }, function( res ) {
		lime_error_message.html( res );
	} );
}

function limelightBuildAffiliates( form_id ) {

	let html       = '';
	let affiliates = JSON.parse( lime_affiliates );

	jQuery.each( affiliates, function( key, value ) {
		html += "<input type='hidden' name='affiliates_" + key + "' value ='" + value + "'>";
	} );

	jQuery( '#' + form_id ).append( html );
}

function limelightMyAccount() {

	lime_loading.show();

	let info = jQuery( '#ll-account-form' ).serializeArray();
	let data = {
		'action'      : 'account_page',
		'userinfo'    : info,
		'customer_id' : lime_customer_id,
		'aj'          : 1
	}

	jQuery.post( ll_ajax_url, data, function( res ) {

		if ( res ) {
			jQuery( '#ll-member-wrapper' ).replaceWith( res );
			lime_loading.hide();
		}
	} );
}

function limelightPagination( type ) {

	lime_loading.show();

	let page_num = jQuery( '#ll-page-num' ).val();
	let per_page = jQuery( '#ll-per-page' ).val();
	let data     = {
		'action'      : type + '_page',
		'page_num'    : page_num,
		'per_page'    : per_page,
		'customer_id' : lime_customer_id,
		'aj'          : 1
	}

	jQuery.post( ll_ajax_url, data, function( res ) {

		if ( res ) {
			jQuery( '#ll-member-wrapper' ).replaceWith( res );
			lime_loading.hide();
		}
	} );
}

function limelightUpsellProcess() {

	lime_loading.show();

	let next_page = ll_thankyou_page;
	let fields    = jQuery( '#ll-upsell-form' ).serializeArray();
	let post      = {}

	jQuery.each( fields, function( i, field ) {
		post[ field.name ] = field.value;
	} );

	let upsells = localStorage['limelight_upsells'] ? JSON.parse( localStorage['limelight_upsells'] ) : [];
	let upsolds = localStorage['limelight_upsolds'] ? JSON.parse( localStorage['limelight_upsolds'] ) : [];
	let square  = localStorage['limelight_square_nonce'] ? localStorage['limelight_square_nonce'] : '';
	let current = upsells.shift();

	localStorage.setItem( 'limelight_upsells', JSON.stringify( upsells ) );

	let upsold_matrix = '';

	//next
	if ( upsells.length > 0 ) {
		next_page = './?p=' + upsells[0]['ID'];
	}

	//ungrouped
	if ( post['choice'] == 'no' && ll_group_upsells !== 'on' ) {
		window.location.replace( next_page );
	}

	if ( post['choice'] == 'yes' ) {

		upsolds.push( post );
		upsold_matrix = JSON.stringify( upsolds );
		localStorage.setItem( 'limelight_upsolds', upsold_matrix );

		if ( ll_group_upsells !== 'on' ) {

			limelightTokenizeAuth( function( result ) {

				let req = {
					'previousOrderId'           : lime_order_summary['order_id'],
					'campaignId'                : post['campaign'],
					'shippingId'                : post['shipping'],
					'initializeNewSubscription' : 1,
					'offers'                    : [ {
						'offer_id'         : post['offer_id'],
						'product_id'       : post['product'],
						'billing_model_id' : post['billing_model_id'],
						'quantity'         : post['quantity']
					} ]
				};

				if ( square.len > 0 ) {
					req.square_token = square;
					req.creditCardType = 'square';
				}

				jQuery.ajax( {
					type       : 'POST',
					url        : 'https://' + ll_appkey + '.limelightcrm.com/api/v1/NewOrderCardOnFile',
					data       : req,
					dataType   : 'json',
					beforeSend : function( xhr ) {
						xhr.setRequestHeader( 'Authorization', 'Bearer ' + result['data']['token'] );
					},
					success    : function( res ) {
						let upsell_summary = localStorage['limelight_upsell_summary'] ? JSON.parse( localStorage['limelight_upsell_summary'] ) : [];
						upsell_summary.push( res );
						localStorage.setItem( 'limelight_upsell_summary', JSON.stringify( upsell_summary ) );
						window.location.replace( next_page );
					}
				} );
			}, 'https://' + ll_appkey + '.limelightcrm.com/api/v2/token/NewOrderCardOnFile' );
		}
	}

	//grouped
	if ( ll_group_upsells === 'on' ) {

		if ( upsells.length < 1 ) {

			limelightTokenizeAuth( function( result ) {

				let offers   = [];
				let count    = '';
				let campaign = upsolds[0] !== undefined && upsolds[0]['campaign'] !== undefined ? upsolds[0]['campaign'] : '';
				let shipping = upsolds[0] !== undefined && upsolds[0]['shipping'] !== undefined ? upsolds[0]['shipping'] : '';
				let primary  = upsolds[0] !== undefined && upsolds[0]['product'] !== undefined ? upsolds[0]['product'] : '';

				jQuery.each( upsolds, function( z, upsell_item ) {

					let att = upsell_item['attributes'].length ? JSON.parse(upsell_item['attributes']) : '';

					offers.push( {
						'offer_id'         : upsell_item['offer_id'],
						'product_id'       : upsell_item['product'],
						'billing_model_id' : upsell_item['billing_model_id'],
						'quantity'         : upsell_item['quantity'],
						'variants'         : att
					} );
				} );

				let req = {
					'previousOrderId'           : lime_order_summary['order_id'],
					'campaignId'                : campaign,
					'shippingId'                : shipping,
					'initializeNewSubscription' : 1,
					'offers'                    : offers
				};

				if ( square.length > 0 ) {
					req.square_token = square;
					req.creditCardType = 'square';
				}

				jQuery.ajax( {
					type       : 'POST',
					url        : 'https://' + ll_appkey + '.limelightcrm.com/api/v1/NewOrderCardOnFile',
					data       : req,
					dataType   : 'json',
					beforeSend : function( xhr ) {
						xhr.setRequestHeader( 'Authorization', 'Bearer ' + result['data']['token'] );
					},
					success    : function( res ) {
						let upsell_summary = localStorage['limelight_upsell_summary'] ? JSON.parse( localStorage['limelight_upsell_summary'] ) : [];
						upsell_summary.push( res );
						localStorage.setItem( 'limelight_upsell_summary', JSON.stringify( upsell_summary ) );
						window.location.replace( next_page );
					}
				} );
			}, 'https://' + ll_appkey + '.limelightcrm.com/api/v2/token/NewOrderCardOnFile' );

		} else {
			window.location.replace( next_page );
		}
	}
}

function limelightPickVariant( matrix, names, values ) {

	let pick;

	jQuery.each( matrix, function( x, line ) {

		let match = 1;

		jQuery.each( names, function( z, name ) {

			name = name.toLowerCase();

			if ( line[ JSON.parse( name ) ] != JSON.parse( values[ z ] ) ) {
				match = 0;
			}
		} );

		if ( match == 1 ) {
			pick = line;
		}
	} );

	return pick;
}

function limelightApplyVariantPrice( id ) {

	let form    = '';
	let element = '#ll-product-form';
	let matrix  = ( jQuery( "input[name='variants_matrix']" ).val() ) ? JSON.parse( jQuery( "input[name='variants_matrix']" ).val() ) : '';

	if ( id ) {

		form    = jQuery( form + ' ' + "input[name='variants_matrix']" ).val();
		element = '#ll-archive-form-' + id;

		if ( form ) {
			matrix = JSON.parse( form );
		}
	}

	let pick           = '';
	let price_element  = '';
	let fields         = jQuery( element ).serializeArray();
	let variant_names  = [];
	let variant_values = [];
	let product        = {};

	jQuery.each( fields, function( i, field ) {

		product[ field.name ] = field.value;

		if ( ( field.name.startsWith( 'variant_value_' ) && field.value == '' ) || ( field.name == 'frequency' && field.value == '' ) ) {
			response = { 'msg': 'Please Select Options', 'success': 0 }
		}

		if ( field.name.startsWith( 'variant_name_' ) ) {
			index = field.name.replace( 'variant_name_', '' );
			variant_names[ index ] = JSON.stringify( field.value );
		}

		if ( field.name.startsWith( 'variant_value_' ) ) {
			index = field.name.replace( 'variant_value_', '' );
			variant_values[ index ] = JSON.stringify( field.value );
		}
	} );

	if ( pick = limelightPickVariant( matrix, variant_names, variant_values ) ) {

		price_element = ( id ) ? '#ll-product-price-' + id : '#ll-product-price';
		jQuery( price_element ).html( '$' + pick['price'].replace( '"','' ) );

		product['max_quantity'] = pick['max_quantity'];
		product['price']        = pick['price'];
		product['sku']          = pick['sku'];
	}

	if ( product['qty'] > product['max_quantity'] && product['max_quantity'] != '0' ) {
		product['qty'] = product['max_quantity'];
	}
}

function limelightSubscriptionSkipNext( id, order_id, product_id ) {

	lime_loading.show();

	jQuery.post( ll_ajax_url, { 'action' : 'subscription_skip_next', 'id' : id }, function( res ) {

		res = JSON.parse( JSON.parse( res ) );

		if ( res['response_code'] == '100' ) {

			data = {
				'action'     : 'subscription_next_date',
				'id'         : id,
				'order_id'   : order_id,
				'product_id' : product_id
			}

			jQuery.post( ll_ajax_url, data, function( res ) {
				jQuery( '#ll-skip-next-' + id ).html( res );
			} );

		} else {
			lime_error_overlay.show();
			lime_error_message.html( limelightErrorDisplay( res ) );
		}
	} ).always( function() {
		lime_loading.hide();
	} );
}

function limelightSubscriptionQuantity( order_id, prod_id, recur_id, qty_new, qty_old, item ) {

	lime_loading.show();

	let data = {
		'action'     : 'subscription_quantity',
		'order_id'   : order_id,
		'product_id' : prod_id,
		'recur_id'   : recur_id,
		'qty'        : qty_new
	};

	jQuery.post( ll_ajax_url, data, ( res ) => {

		res = JSON.parse( JSON.parse( res ) );

		if ( res['response_code'] != '100' ) {
			item.val( qty_old );
			res['error_message'] = 'There was a problem updating the quantity';
			lime_error_overlay.show();
			lime_error_message.html( limelightErrorDisplay( res ) );
		}

	} ).always( () => {
		lime_loading.hide();
	} );
}

function limelightSubscriptionFrequency( order_id, product_id, subscription_id ) {

	lime_loading.show();

	let billing_model_id   = jQuery( '#ll-frequency-' + subscription_id + ' option:selected' ).val();
	let billing_model_name = jQuery( '#ll-frequency-' + subscription_id + ' option:selected' ).text();

	let data             = {
		'action'           : 'subscription_frequency',
		'order_id'         : order_id,
		'product_id'       : product_id,
		'billing_model_id' : billing_model_id
	}

	jQuery.post( ll_ajax_url, data, function( res ) {

		res = JSON.parse( JSON.parse( res ) );

		if ( res['response_code'] == '100' ) {

			jQuery( '#ll-frequency-name-' + subscription_id ).html( billing_model_name );
			jQuery( '#ll-frequency-area-' + subscription_id ).css( { 'display' : 'none' } );

			data = {
				'action'     : 'subscription_next_date',
				'id'         : subscription_id,
				'order_id'   : order_id,
				'product_id' : product_id
			}

			jQuery.post( ll_ajax_url, data, function( res ) {
				jQuery( '#ll-skip-next-' + subscription_id ).html( res );
			} );

		} else {
			lime_error_overlay.show();
			lime_error_message.html( limelightErrorDisplay( res ) );
		}

	} ).always( function() {
		lime_loading.hide();
	} );
}

function limelightSubscriptionToggle( order_id, product_id, recurring_status ) {

	lime_loading.show();

	let data = {
		'action'           : 'subscription_toggle',
		'order_id'         : order_id,
		'product_id'       : product_id,
		'recurring_status' : recurring_status
	}

	jQuery.post( ll_ajax_url, data, function( res ) {

		res = JSON.parse( JSON.parse( res ) );

		if ( res['response_code'] == '100' ) {
			window.location.reload();
		} else {
			res['error_message'] = 'This subscription was not reset, please contact support.';
			lime_error_overlay.show();
			lime_error_message.html( limelightErrorDisplay( res ) );
		}

	} ).always( function() {
		lime_loading.hide();
	} );
}

function limelightAddUpsell( order_id, obj ) {

	var error = '';

	if ( obj && obj !== 'null' && obj !== 'undefined' ) {

		var
			new_product_id       = jQuery( obj ).closest( '.sub_action_select_product_div' ).find( '.sub_action_select_product' ).val(),
			new_billing_model_id = jQuery( obj ).closest( '.sub_action_select_product_div' ).find( '.sub_action_select_bm_id' ).val();

		if ( new_product_id && new_product_id !== 'null' && new_product_id !== 'undefined' ) {

			lime_loading.show();

			let data = {
				'action'           : 'subscription_add_product',
				'order_id'         : order_id,
				'product_id'       : new_product_id,
				'billing_model_id' : new_billing_model_id
			}

			jQuery.post( ll_ajax_url, data, function( res ) {

				res = JSON.parse( JSON.parse( res ) );

				if ( res['response_code'] == '100' ) {
					window.location.reload();
				} else {
					error = res['message'];
				}

			} ).always( function() {
				lime_loading.hide();
			} );
		} else {
			error = 'Invalid product chosen.';
		}
	} else {
			error = 'Invalid product chosen.';
	}

	if ( error != '' ) {

		res['error_message'] = error;
		lime_error_overlay.show();
		lime_error_message.html( limelightErrorDisplay( res ) );
	}
}

function limelightToggleAddUpsell( element ) {

	if ( jQuery( element ).hasClass( 'sub_action_toggle_add_product' ) ) {

		jQuery( element ).addClass( 'd-none' );
		jQuery( element ).closest( '.sub_action_add_product_div' ).find( '.sub_action_select_product_div' ).removeClass( 'd-none' );
	}

	if ( jQuery( element ).hasClass( 'sub_action_select_cancel' ) ) {

		jQuery( element ).closest( '.sub_action_add_product_div' ).find( '.sub_action_toggle_add_product' ).removeClass( 'd-none' );
		jQuery( element ).closest( '.sub_action_add_product_div' ).find( '.sub_action_select_product_div' ).addClass( 'd-none' );
	}
}

function limelightAmazonProcess( event, form_id ) {

	event.preventDefault();
	lime_loading.show();

	let form = jQuery( '#' + form_id ).serializeArray();
	let data = {
		'action'    : 'amazonpay_process',
		'form_data' : form
	}

	jQuery.post( ll_ajax_url, data, function( response ) {

		let res  = JSON.parse( response );
		let body = res['body'];
		res      = JSON.parse( body );

		if ( +res['error_found'] === 0 && +res['response_code'] === 100 ) {

			if ( form_id == 'll-onepage-checkout-form' ) {
				localStorage.setItem( 'limelight_onepage', 1 );
			}

			let subtotal = jQuery( '#ll-sub-total' ).html();
			let shipping = jQuery( '#ll-shipping-cost' ).html();

			localStorage.setItem( 'limelight_subtotal', subtotal );
			localStorage.setItem( 'limelight_shipping', shipping );
			localStorage.setItem( 'limelight_order_summary', body );

			window.location = ll_thankyou_page;

		} else {
			lime_error_overlay.show();
			lime_error_message.html( limelightErrorDisplay( res ) );
		}

	} ).always( function() {
		lime_loading.hide();
	} );
}

function limelightTokenizeAuth( callback, url ) {

	jQuery.ajax( {
		type       : 'GET',
		url        : url,
		beforeSend : function( xhr ) {
			xhr.setRequestHeader( 'Authorization', 'Basic ' + ll_bearer );
		},
		success    : callback
	} );
}

function limelightTokenizePayment( callback, auth_token, fields ) {

	jQuery.ajax( {
		type       : 'POST',
		url        : 'https://' + ll_appkey + '.limelightcrm.com/api/v2/tokenize_payment',
		data       : fields,
		beforeSend : function( xhr ) {
			xhr.setRequestHeader( 'Authorization', 'Bearer ' + auth_token );
		},
		success    : callback,
		error      : function( err ) {
			lime_loading.hide();
		}
	} );
}

function limelightNewOrder( callback, url, auth_token, fields ) {

	jQuery.ajax( {
		type       : 'POST',
		url        : url,
		data       : fields,
		dataType   : 'json',
		beforeSend : function( xhr ) {
			xhr.setRequestHeader( 'Authorization', 'Bearer ' + auth_token );
		},
		success    : callback
	} );
}

function limelightCheckoutProcess( payment_token, auth_new_order ) {

	lime_loading.show();

	let form_id            = jQuery( '#ll-checkout-form' ).length ? 'll-checkout-form' : 'll-onepage-checkout-form';
	let field              = [];
	let ids                = [];
	let affiliate          = [];
	let offers             = [];
	let quantities         = [];
	let product_attributes = [];
	let gift               = '';
	let member_create      = '';
	let shipping_cost      = '';
	let product_id         = '';
	let upsell_ids         = '';
	let subtotal           = jQuery( '#ll-sub-total' ).html();
	let shipping           = jQuery( '#ll-shipping-cost' ).html();
	let checkout           = jQuery( '#' + form_id ).serializeArray();

	if ( form_id == 'll-onepage-checkout-form' ) {
		localStorage.setItem( 'limelight_onepage', 1 );
	}

	jQuery.each( checkout, function( index, val ) {

		field[ val['name'] ] = val['value'];

		//product ids
		if ( val['name'].startsWith( 'product_id_' ) ) {
			ids.push( val['name'].replace( 'product_id_', '' ) );
		}

		//affiliate
		if ( val['name'].startsWith( 'affiliates_' ) ) {
			affiliate[ val['name'].replace( 'affiliates_', '' ).toLowerCase() ] = val['value'];
		}

		//gift
		if ( val['name'] == 'gift_order' && val['value'] > 0 ) {
			gift = 1;
		}

		//member
		if ( val['name'] == 'account_create' && val['value'] > 0 ) {
			member_create = 1;
		}

		//shipping
		if ( val['name'] == 'shipping_cost' ) {
			shipping_cost = val['value'];
		}
	} );

	ids = jQuery.unique( ids );

	if ( ll_offer_id ) {

		let variant = [];

		jQuery.each( ids, function( index, id ) {

			let counter = 0;

			while ( field[ 'product_att_name_' + id + '_' + counter ] ) {

				variant.push( {
					'attribute_name'  : field[ 'product_att_name_' + id + '_' + counter ],
					'attribute_value' : field[ 'product_att_value_' + id + '_' + counter ]
				} );

				counter++;
			}

			if ( id && +field[ 'product_qty_' + id ] ) {

				offers[ id ] = {
					'product_id'       : id,
					'offer_id'         : field[ 'product_offer_id_' + id ],
					'billing_model_id' : field[ 'product_billing_model_id_' + id ],
					'quantity'         : field[ 'product_qty_' + id ],
					'variant'          : variant
				}
			}
		} );

		offers = offers.filter( function( el ) {
			return el != null;
		} );

	} else {

		jQuery.each( ids, function( index, id ) {

			quantities[ 'product_qty_' + id ] = field[ 'product_qty_' + id ];
			let counter = 0;

			while ( field[ 'product_att_name_' + id + '_' + counter ] ) {
				let att_name  = field[ 'product_att_name_' + id + '_' + counter ];
				let att_value = field[ 'product_att_value_' + id + '_' + counter ];
				product_attributes[ 'product_attribute[' + id + '][' + att_name + ']' ] = att_value;
				counter++;
			}
		} );

		product_id = ids.shift();
		upsell_ids = ids;
	}

	let notes = 'Order created by appkey ' + ll_appkey + ' at ' + ll_home_url + ' WP v' + ll_wp_version + '. Plugin v' + ll_plugin_version;

	let order_info = {
		'tranType'              : 'sale',
		'email'                 : field['email'],
		'phone'                 : field['phone'],
		'ipAddress'             : ll_client_ipaddress,
		'shippingId'            : field['shipping_id'],
		'campaignId'            : ll_campaign_id,
		'offers'                : offers,
		'payment_token'         : payment_token,
		'firstName'             : field['s_first'],
		'lastName'              : field['s_last'],
		'shippingAddress1'      : field['s_addr1'],
		'shippingAddress2'      : field['s_addr2'],
		'shippingCity'          : field['s_city'],
		'shippingState'         : field['s_state'],
		'shippingZip'           : field['s_zip'],
		'shippingCountry'       : field['s_country'],
		'billingFirstName'      : field['b_first'],
		'billingLastName'       : field['b_last'],
		'billingAddress1'       : field['b_addr1'],
		'billingAddress2'       : field['b_addr2'],
		'billingCity'           : field['b_city'],
		'billingState'          : field['b_state'],
		'billingZip'            : field['b_zip'],
		'billingCountry'        : field['b_country'],
		'billingSameAsShipping' : field['billing_same'] == 1 ? 'yes' : 'no',
		'promoCode'             : field['coupon'],
		'notes'                 : notes,
		'AFID'                  : affiliate['afid'],
		'SID'                   : affiliate['sid'],
		'AFFID'                 : affiliate['affid'],
		'C1'                    : affiliate['c1'],
		'C2'                    : affiliate['c2'],
		'C3'                    : affiliate['c3'],
		'AID'                   : affiliate['aid'],
		'OPT'                   : affiliate['opt'],
		'click_id'              : affiliate['click_id'],
		'create_member'         : member_create,
		'event_id'              : ll_membership_event
	}

	if ( ! ll_offer_id ) {

		order_info.productId        = product_id;
		order_info.upsellCount      = upsell_ids.length;
		order_info.upsellProductIds = upsell_ids.join(',');

		jQuery.extend( order_info, quantities );
		jQuery.extend( order_info, product_attributes );
	}

	//prospect
	let endpoint = 'https://' + ll_appkey + '.limelightcrm.com/api/v1/new_order';

	if ( localStorage.getItem( 'limelight_prospect' ) !== null ) {

		let prospect = JSON.parse( localStorage.getItem( 'limelight_prospect' ) );
		order_info['prospectId'] = prospect.prospectId;
		endpoint = 'https://' + ll_appkey + '.limelightcrm.com/api/v1/new_order_with_prospect';
	}

	//gift
	if ( gift ) {

		order_info['gift'] = {
			'email'   : field['gift_email'],
			'message' : field['gift_msg']
		}
	}

	//3d verify
	if ( ll_enable_threed ) {

		order_info['cavv'] = field['cavv'];
		order_info['eci']  = field['eci'];
		order_info['xid']  = field['xid'];
	}

	//api hit
	limelightNewOrder( function( result ) {

		let new_order = result;

		//edigital
		if ( ll_enable_edigital && new_order.error_found == 0 && field['edigital'] ) {
			limelightEdigitalProcess( new_order.order_id );
		}

		//response
		if ( new_order.error_found == 0 ) {

			localStorage.setItem( 'limelight_subtotal', subtotal );
			localStorage.setItem( 'limelight_shipping', shipping );
			localStorage.setItem( 'limelight_order_summary', JSON.stringify( new_order ) );
			let redirect = ll_thankyou_page;

			jQuery.post( ll_ajax_url, { action: "check_upsells", aj: 1 }, function( raw ) {

				let upsells = JSON.parse( raw );

				if ( upsells[0] !== undefined && upsells[0]['ID'] !== undefined ) {
					redirect = './?p=' + upsells[0]['ID'];
					localStorage.setItem( 'limelight_upsells', raw );
				}
				window.location = redirect;
			} );

		} else {
			lime_loading.hide();
			lime_error_overlay.show();
			lime_error_message.html( limelightErrorDisplay( new_order ) );
		}
	}, endpoint, auth_new_order, order_info );
}

function limelightApplyCart() {

	let items       = [];
	let total       = 0;
	let line_total  = 0;
	let clean_price = 0;

	jQuery.each( lime_cart, function( i, field ) {

		field['pic']                        = field['pic'] ? field['pic'] : '//dummyimage.com/50x50/f4f4f4/cccccc&text=';
		field['link']                       = field['link'];
		field['product_id_x']               = 'product_id_' + field['id'];
		field['product_qty_x']              = 'product_qty_' + field['id'];
		field['dynamic_product_price_x']    = 'dynamic_product_price_' + field['id'];
		field['product_billing_model_id_x'] = 'product_billing_model_id_' + field['id'];
		field['product_offer_id_x']         = 'product_offer_id_' + field['id'];
		clean_price                         = field['price'].replace( /[^0-9.]/g, '' );
		line_total                          = +field['qty'] * +clean_price;
		total                              += line_total;

		let v_html   = '';
		let v_matrix = '';
		let counter  = 0;

		while ( ! ( field['variant_name_' + counter ] === undefined ) ) {

			v_html   += field[ 'variant_name_' + counter ] + ': ' + field[ 'variant_value_' + counter ] + '<br>';
			v_matrix += "<input type='hidden' name='product_att_name_" + field['id'] + "_" + counter + "' value='" + field[ 'variant_name_' + counter ]+"'>";
			v_matrix += "<input type='hidden' name='product_att_value_" + field['id'] + "_" + counter + "' value='" + field[ 'variant_value_' + counter ] + "'>";
			counter++;
		}

		field['variants']       = v_html;
		field['variant_matrix'] = v_matrix;
		field['offer']          = ll_offer_id;
		field['line_total']     = '$' + limelightNumberFormat( line_total );
		field['dynamic_price']  = field['price'];
		field['price']          = '$' + limelightNumberFormat( clean_price );

		items.push( field );
	} );

	jQuery( '#ll-checkout-table' ).loadTemplate( jQuery( '#ll-checkout-item' ), items, { append: true } );

	for ( var i = 0; i < 3; i++ ) {
		jQuery( '#ll-checkout-table' ).append( jQuery( '#ll-checkout-table tr' ).eq( 1 ) );
	}

	jQuery( '#ll-matrix' ).loadTemplate( jQuery( '#ll-matrix-item' ), items, { append: true } );
	jQuery( '#ll-sub-total' ).html( limelightNumberFormat( total ) );
	jQuery( '#ll-grand-total' ).html( limelightNumberFormat( total ) );
	jQuery( '#ll-checkout-back' ).attr( 'href', ll_cart_page );
}

function limelightEdigitalProcess( order_id ) {

	//new_order_card_on_file
	limelightTokenizeAuth( function( result ) {

		jQuery.ajax( {
			type       : 'POST',
			url        : 'https://' + ll_appkey + '.limelightcrm.com/api/v1/new_order_card_on_file',
			data       : {
				'previousOrderId'           : order_id,
				'campaignId'                : ll_edigital_cid,
				'productId'                 : ll_edigital_pid,
				'shippingId'                : ll_edigital_sid,
				'ipAddress'                 : ll_client_ipaddress,
				'tranType'                  : 'sale',
				'initializeNewSubscription' : 1
			},
			dataType   : 'json',
			beforeSend : function( xhr ) {
				xhr.setRequestHeader( 'Authorization', 'Bearer ' + result['data']['token'] );
			}
		} );
	}, 'https://' + ll_appkey + '.limelightcrm.com/api/v2/token/new_order_card_on_file' );
}

