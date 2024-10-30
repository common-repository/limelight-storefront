function getURLParameter( name, source ) {
	return decodeURIComponent( ( new RegExp( '[?|&amp;|#]' + name + '=' + '([^&;]+?)(&|#|;|$)' ).exec( source ) || [, ""] )[1].replace( /\+/g, '%20' ) ) || null;
}

var accessToken = getURLParameter( 'access_token', location.hash );

if ( typeof accessToken === 'string' && accessToken.match( /^Atza/ ) ) {
	document.cookie = 'amazon_Login_accessToken=' + accessToken + ';path=/;secure';
}

window.onAmazonLoginReady = function() {
	amazon.Login.setClientId( '<!--client_id-->' );
};

window.onAmazonPaymentsReady = function() {
	showLoginButton();
};

document.getElementById( 'Logout' ).onclick = function() {
	amazon.Login.logout();
	document.cookie = 'amazon_Login_accessToken=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
	window.location.reload();
};