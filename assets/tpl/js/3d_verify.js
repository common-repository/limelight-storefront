var id       = document.querySelector( '[data-threeds=id]' );
function uniqueId() { return 'id-' + Math.random().toString( 36 ).substr( 2, 16 ); };
id.value     = uniqueId();
var tds      = new ThreeDS(
	'<!--form_id-->',
	'<!--apikey-->',
	null,
	{
		endpoint        : '<!--endpoint-->',
		verbose         : <!--verbose-->,
		addResultToForm : true
	}
);

function updateThreed( initial ) {

	if ( initial == 1 ) {
		let d = new Date();
		jQuery( 'select[name=expiry_month]' ).val( ( '00' + ( 1 + d.getMonth() ) ).slice( -2 ) );
	}
	let amount = jQuery( '#ll-grand-total' ).html();
	let month  = jQuery( '#ll-expiry-month option:selected' ).val();
	let year   = jQuery( '#ll-expiry-year option:selected' ).val();

	if ( amount.indexOf( '$' ) != -1 ) {
		amount = amount.split( '$' );
		amount = amount[1];
	}
	jQuery( 'input[data-threeds="amount"]' ).val( amount );
	jQuery( 'input[data-threeds="month"]' ).val( month );
	jQuery( 'input[data-threeds="year"]' ).val( year );
}
jQuery( '#<!--form_id-->' ).on( 'change', function() { updateThreed(); } );
updateThreed( 1 );