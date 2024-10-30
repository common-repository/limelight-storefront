function showLoginButton() {

	var authRequest;

	OffAmazonPayments.Button( 'AmazonPayButton', '<!--seller_id-->', {

		type  : '<!--type-->',
		color : '<!--color-->',
		size  : '<!--size-->',

		authorization: function() {

			loginOptions = { scope: 'profile payments:widget payments:shipping_address', popup: true };
			authRequest  = amazon.Login.authorize( loginOptions, function( t ) {

				jQuery( '#access_token' ).val( t.access_token );

				showAddressBookWidget();

				jQuery( '#Logout' ).css( { 'display' : 'block' } );
				jQuery( '#addressBookWidgetDiv' ).css( { 'height' : '250px' } );
				jQuery( '#walletWidgetDiv' ).css( { 'height' : '250px' } );
				jQuery( '#consentWidgetDiv' ).css( { 'height' : '125px' } );
			} );
		}
	} );
}

function showAddressBookWidget() {

	new OffAmazonPayments.Widgets.AddressBook( {

		sellerId      : '<!--seller_id-->',
		agreementType : 'BillingAgreement',

		onReady: function( billingAgreement ) {

			var billingAgreementId = billingAgreement.getAmazonBillingAgreementId();
			var el;

			if ( ( el = document.getElementById( 'billingAgreementId' ) ) ) {
				el.value = billingAgreementId;
			}

			showWalletWidget( billingAgreementId );
			showConsentWidget( billingAgreement );
		},

		onAddressSelect: function( billingAgreement ) {
			// do stuff here like recalculate tax and/or shipping
		},

		design: {
			designMode: 'responsive'
		},

		onError: function( error ) {
			console.log( 'OffAmazonPayments.Widgets.AddressBook', error.getErrorCode(), error.getErrorMessage() );
		}

	} ).bind( 'addressBookWidgetDiv' );
}

function showWalletWidget( billingAgreementId ) {

	new OffAmazonPayments.Widgets.Wallet( {

		sellerId                 : '<!--seller_id-->',
		agreementType            : 'BillingAgreement',
		amazonBillingAgreementId : billingAgreementId,

		onReady: function( billingAgreement ) {
			jQuery( '#billing_agreement_id' ).val( billingAgreementId );
		},

		onPaymentSelect: function() {
			jQuery( '#billing_agreement_id' ).val( billingAgreementId );
		},

		design: {
			designMode: 'responsive'
		},

		onError: function( error ) {
			console.log( 'OffAmazonPayments.Widgets.Wallet', error.getErrorCode(), error.getErrorMessage() );
		}
	} ).bind( 'walletWidgetDiv' );
}

function showConsentWidget( billingAgreement ) {

	new OffAmazonPayments.Widgets.Consent( {

		sellerId                 : '<!--seller_id-->',
		amazonBillingAgreementId : billingAgreement.getAmazonBillingAgreementId(),

		onReady: function( billingAgreementConsentStatus ) {
			toggleCheckoutButton( billingAgreementConsentStatus.getConsentStatus(), billingAgreement.getAmazonBillingAgreementId() )
		},

		onConsent: function( billingAgreementConsentStatus ) {
			toggleCheckoutButton( billingAgreementConsentStatus.getConsentStatus(), billingAgreement.getAmazonBillingAgreementId() );
		},

		design: {
			designMode: 'responsive'
		},

		onError: function( error ) {
			console.log( 'OffAmazonPayments.Widgets.Consent', error.getErrorCode(), error.getErrorMessage() );
		}
	} ).bind( 'consentWidgetDiv' );
}

function toggleCheckoutButton( consent_status, billingAgreementId ) {

	consent_status = ( consent_status !== 'true' );
	let checkout_submit   = jQuery( '#ll-checkout-submit' );
	let billing_agreement = jQuery( '#billing_agreement_id' );

	checkout_submit.prop( 'disabled', consent_status )
	billing_agreement.val( billingAgreementId );
}