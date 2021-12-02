define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract',
    'ko',
    'mage/translate',
    'mage/storage',
    'mage/url'
], function(
    _,
    uiRegistry,
    Component,
    ko,
    $t,
    storage,
    frontUrlBuilder
) {
    'use strict';

    var mixin = {
        isPickupPointDelivery: function() {
            var method = uiRegistry.get('inputName = general[misc][method]').value();
            return method === 'pickupPointDelivery' || method === 'packetery';
        },

        initialize: function () {
            this._super();

            var address = uiRegistry.get('inputName = general[misc][method]');
            if(mixin.isPickupPointDelivery()){
                address.show();
            } else {
                address.hide();
            }

            return this;
        }
    };

    return Component.extend(mixin);
});
