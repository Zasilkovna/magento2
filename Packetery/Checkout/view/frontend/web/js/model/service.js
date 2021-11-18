define([], function () {
    'use strict';

    return {
        getPacketaPoint: function(defaultReturnValue) {
            if (localStorage.packetaPoint) {
                return JSON.parse(localStorage.packetaPoint);
            }

            return defaultReturnValue;
        },

        getPacketaValidatedAddress: function(defaultReturnValue) {
            if (localStorage.packetaValidatedAddress) {
                return JSON.parse(localStorage.packetaValidatedAddress);
            }

            return defaultReturnValue;
        }
    }
});
