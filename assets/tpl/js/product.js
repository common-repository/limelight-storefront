jQuery( document ).ready( function() {

	limelightApplyVariantPrice();

	jQuery( '#ll-product-form' ).change( function() {
		limelightApplyVariantPrice();
	} );

	jQuery( '#ll-add-to-cart-button' ).click( function( e ) {

		e.preventDefault();
		lime_loading.show();

		let freq_name      = jQuery( "select[name='frequency'] option:selected" ).text();
		let freq_count     = jQuery( "select[name='frequency']" ).length;

		if ( +freq_count > 1 || freq_name != 'Straight Sale' ) {
			jQuery( "[name='frequency_name']" ).val( freq_name );
		}
		
		let fields         = jQuery( '#ll-product-form' ).serializeArray();
		let product        = {};
		let response       = { 'msg': 'Added To Cart &nbsp; <a class="badge badge-secondary badge-warning" href="' + ll_cart_page + '">View Cart</a>', 'success': 1 }
		let matrix         = ( jQuery( "input[name='variants_matrix']" ).val() ) ? JSON.parse( jQuery( "input[name='variants_matrix']" ).val() ) : '';
		let variant_names  = [];
		let variant_values = [];
		let pick;

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
			product['max_quantity'] = pick['max_quantity'];
			product['price']        = pick['price'];
			product['sku']          = pick['sku'];
		}

		if ( product['qty'] > product['max_quantity'] && product['max_quantity'] != '0' ) {
			product['qty']  = product['max_quantity'];
			response['msg'] = 'Max Quantity ('+ product['qty'] + ') Added To Cart';
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
				lime_cart.push( product );
			}
		} else {
			lime_cart.push( product );
		}

		localStorage.setItem( 'limelight_cart', JSON.stringify( lime_cart ) );
		lime_loading.hide();
		lime_alert.show();
		lime_alert.html( response['msg'] );
		lime_alert.css( { 'display': 'block' } );

		if ( response['success'] == 1 ) {

			if ( loc = window[ ll_addtocart_redirect ] ) {
				window.location.href = loc;
			}
		}
	} );
} );