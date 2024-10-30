jQuery( '#ll-yes' ).click( function() {

	jQuery( '#ll-choice' ).val( 'yes' );
	limelightUpsellProcess();
} );

jQuery( '#ll-no' ).click( function() {

	jQuery( '#ll-choice' ).val( 'no' );
	limelightUpsellProcess();
} );