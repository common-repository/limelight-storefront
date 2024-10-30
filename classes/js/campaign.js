function limelightShowUpdateMsg() {

	if ( jQuery( 'input#update_only' ).is( ':checked' ) ) {
		jQuery( '#update_only_message' ).css( 'display', 'block' );
	} else {
		jQuery( '#update_only_message' ).css( 'display', 'none' );
	}
}

function limelightGetCampaignDetails() {

	jQuery( '#offer_id,#submit' ).prop( 'disabled', true );

	let id   = jQuery( '#campaign_id' ).val();
	let last = jQuery( '.form-table td:last' );
	let data = {
		'action' : 'get_campaign_offers',
		'id'     : id,
		'aj'     : '1'
	};

	jQuery.post( ajaxurl, data, function( res ) {
		if ( res ) {
			last.html( res );
		} else {
			last.html( '<i>(No offers available for selected campaign, check your API user permissions)</i><input name="limelight_campaign[offer]" type="hidden" value="">' );
		}
	} ).done( function() {
		jQuery( '#offer_id,#submit' ).prop( 'disabled', false );
	});
}

jQuery(document).ready( function() {
	limelightShowUpdateMsg();
	limelightGetCampaignDetails();
} );

jQuery( '.form-table' ).change( function() {
	limelightShowUpdateMsg();
} );

jQuery( '#campaign_id' ).change( function() {
	limelightGetCampaignDetails();
} );