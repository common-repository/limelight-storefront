function updateProduct() {
	jQuery.post( ajaxurl, {
		'action': 'get_price',
		'product_id': jQuery( '#product option:selected' ).val()
	}, function( res ) {
		jQuery( '#upsell_price' ).val( res );
	} );

	jQuery.post( ajaxurl, {
		'action': 'get_variants',
		'product_id': jQuery( '#product option:selected' ).val(),
		'post_id': jQuery( '#post_ID' ).val(),
		'aj': 1
	}, function( res ) {
		jQuery( '#variant' ).replaceWith( res );
	} );
}

jQuery( document ).ready( function() {

	jQuery( '.inside' ).on( 'change', '#campaign', function() {

		let cid = jQuery( '#campaign' ).val();
		jQuery( '#freq,#product' ).find( 'option' ).not( ':first' ).remove();

		jQuery.post( ajaxurl, {
			'action': 'get_products',
			'campaign_id': cid,
			'aj': 1
		}, function( res ) {
			jQuery( '#product' ).replaceWith( res );
		} );

		jQuery.post( ajaxurl, {
			'action': 'get_shippings',
			'campaign_id': cid,
			'aj': 1
		}, function( res ) {
			jQuery( '#shipping' ).replaceWith( res );
		} );

		jQuery.post( ajaxurl, {
			'action': 'get_offers',
			'campaign_id': cid,
			'aj': 1
		}, function( res ) {
			jQuery( '#offer' ).replaceWith( res );
		} );
	} );

	jQuery( '.inside' ).on( 'change', '#offer', function() {
		jQuery.post( ajaxurl, {
			'action': 'get_freqs',
			'offer_id': jQuery( '#offer' ).val(),
			'aj': 1
		}, function( res ) {
			jQuery( '#freq' ).replaceWith( res );
		} );
	} );

	jQuery( '.inside' ).on( 'change', '#shipping', function() {
		jQuery( '#upsell_sprice' ).val( jQuery( '#shipping option:selected' ).text().split( '$' )[1] );
	} );

	jQuery( '.inside' ).on( 'change load', '#product', function() {
		updateProduct();
	} );

	updateProduct();
} );