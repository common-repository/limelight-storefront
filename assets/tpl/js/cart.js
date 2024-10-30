jQuery( document ).ready( function() {

	if ( localStorage.getItem( 'limelight_cart' ) === null || lime_cart.length < 1 ) {
		window.location.href = ll_shop_category;
	}

	let items      = [];
	let total      = 0;
	let line_total = 0;

	jQuery.each( lime_cart, function( i, field ) {

		//display
		field['pic']            = ( field['pic'] ) ? field['pic'] : '//dummyimage.com/50x50/f4f4f4/cccccc&text=';
		field['link']           = field['link'];
		field['x_id']           = field['id'] + '_id';
		field['x_qty']          = field['id'] + '_qty';
		field['x_max_quantity'] = field['id'] + '_max_quantity';
		field['x_name']         = field['id'] + '_name';
		field['x_variants']     = field['id'] + '_variants';
		field['x_offer_id']     = field['id'] + '_offer_id';
		field['x_frequency']    = field['id'] + '_frequency';
		field['x_sku']          = field['id'] + '_sku';
		field['x_price']        = field['id'] + '_price';
		field['x_line_total']   = field['id'] + '_line_total';
		field['x_wp_id']        = field['id'] + '_wp_id';
		field['onchange']       = "limelightQuantityUpdate( " + field['id'] + ", " + field['frequency'] + ", jQuery( this ).val() );";
		let clean_price         = field['price'].replace(/[^0-9.]/g, '');
		line_total              = +field['qty'] * +clean_price;
		total                  += line_total;

		//matrix
		field['product_id_x']               = 'product_id_' + field['id'];
		field['product_qty_x']              = 'product_qty_' + field['id'];
		field['dynamic_product_price_x']    = 'dynamic_product_price_' + field['id'];
		field['product_billing_model_id_x'] = 'product_billing_model_id_' + field['id'];
		field['product_offer_id_x']         = 'product_offer_id_' + field['id'];

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
		field['line_total']     = '$' + limelightNumberFormat( line_total );
		field['dynamic_price']  = field['price']
		field['price']          = '$' + limelightNumberFormat( clean_price );
		items.push( field );
	} );
	let lastrow = jQuery( '#ll-cart-table tr:last' ).html();
	jQuery( '#ll-cart-table tr:last' ).remove();
	jQuery( '#ll-cart-table' ).loadTemplate( jQuery( '#ll-cart-item' ), items, { append: true } );
	jQuery( '#ll-matrix' ).loadTemplate( jQuery( '#ll-matrix-item' ), items, { append: true } );
	jQuery( '#ll-cart-table' ).append( '<tr>' + lastrow + '</tr>' );
	jQuery( '#ll-coupon-code' ).val( lime_coupon );
	jQuery( '#ll-grand-total' ).html( limelightNumberFormat( total ) );
	limelightValidateCoupon();

	//clear
	jQuery( '#ll-cart-clear' ).on( 'click', function( e ) {
		e.preventDefault();
		lime_loading.show();
		localStorage.removeItem( 'limelight_cart' );
		localStorage.removeItem( 'limelight_coupon_amount' );
		localStorage.removeItem( 'limelight_coupon_code' );
		window.location.reload();
	} );

	//submit
	jQuery( '#ll-cart-submit' ).on( 'click', function() {
		lime_loading.show();
		window.location.href = ll_checkout_page;
	} );
} );