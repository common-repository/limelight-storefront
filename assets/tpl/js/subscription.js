jQuery( document ).on( 'change','.ll-subscription-qty', function() {

	let item     = jQuery( this );
	let prev     = item.data( 'val' );
	let curr     = item.val();
	let order_id = item.attr( 'data-order-id' );
	let prod_id  = item.attr( 'data-product-id' );
	let recur_id = item.attr( 'data-recur-id' );

	limelightSubscriptionQuantity( order_id, prod_id, recur_id, curr, prev, item );
} );