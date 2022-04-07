config = {
	map: {
		'*': {
			'Amasty_Checkout/template/onepage/shipping/methods.html': 'Packetery_Checkout/template/override/amasty-checkoutCore-120-methods.html',
			'Amasty_CheckoutCore/template/onepage/shipping/methods.html': 'Packetery_Checkout/template/override/amasty-checkoutCore-120-methods.html'
		}
	},
	config: {
		mixins: {
			'Magento_Checkout/js/view/shipping': {
				'Packetery_Checkout/js/view/shipping': true
			},
            'Magento_Checkout/js/model/place-order': {
                'Packetery_Checkout/js/model/place-order-mixin': true
            }
		}
	}
}
