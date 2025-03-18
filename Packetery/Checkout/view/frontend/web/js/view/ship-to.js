define([
    'Packetery_Checkout/js/model/service',
    'ko'
], function(
    packeteryService,
    ko
) {
    'use strict';

    var mixin = {
        packetaValidatedAddress: packeteryService.packetaValidatedAddress,
        defaults: {
            template: 'Packetery_Checkout/shipping-information/address-renderer/default'
        }
    };

    return function(target) {
        return target.extend(mixin);
    };
});
