define([
	'Magento_Checkout/js/model/quote',
	'Magento_Checkout/js/model/url-builder',
	'Magento_Customer/js/model/customer',
	'Magento_Checkout/js/model/place-order',
	'jquery'
], function (quote, urlBuilder, customer, placeOrderService, $) {
	'use strict';

	var agreementsConfig = window.checkoutConfig.checkoutAgreements;
	return function (paymentData, messageContainer) {
		var serviceUrl, payload;

		let pointId = "";
		let pointName = "";

		if( window.packetaPointId !== undefined ){
			if ( window.packetaPointId != "" ){
				pointId = window.packetaPointId;
			}
		}
		if( window.packetaPointName !== undefined ){
			if ( window.packetaPointName != "" ){
				pointName = window.packetaPointName;
			}
		}
		

		var agreementForm,
		agreementData,
		agreementIds;

		if (!agreementsConfig.isEnabled) {
			return;
		}

		agreementForm = $('.payment-method._active div[data-role=checkout-agreements] input');
		agreementData = agreementForm.serializeArray();
		agreementIds = [];

		agreementData.forEach(function (item) {
			agreementIds.push(item.value);
		});

		if (paymentData['extension_attributes'] === undefined) {
			paymentData['extension_attributes'] = {};
		}

		paymentData['extension_attributes']['agreement_ids'] = agreementIds;


		payload = {
			cartId: quote.getQuoteId(),
			billingAddress: quote.billingAddress(),
			paymentMethod: paymentData,
			packetery:{
				id: pointId,
				name: pointName
			}
		};
		console.log(payload);
		console.log('----------');
		if (customer.isLoggedIn()) {
			serviceUrl = urlBuilder.createUrl('/carts/mine/payment-information', {});
		} else {
			serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/payment-information', {
				quoteId: quote.getQuoteId()
			});
			payload.email = quote.guestEmail;
		}

		return placeOrderService(serviceUrl, payload, messageContainer);
	};
});
