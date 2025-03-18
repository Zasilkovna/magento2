define([
    'ko'
], function(
    ko
) {
    'use strict';

    return {
        packetaValidatedAddress: ko.observable(null),
        getPacketaPoint: function(defaultReturnValue) {
            if(window.localStorage.packetaPoint) {
                return JSON.parse(window.localStorage.packetaPoint); // TODO: Store data in backend to avoid direct edit by unwanted actor. Issue was communicated with manager.
            }

            return defaultReturnValue;
        },

        getPacketaValidatedAddress: function(defaultReturnValue) {
            if(this.packetaValidatedAddress() === null && window.localStorage.packetaValidatedAddress) {
                this.packetaValidatedAddress(JSON.parse(window.localStorage.packetaValidatedAddress)); // TODO: Store data in backend to avoid direct edit by unwanted actor. Issue was communicated with manager.
            }

            if(this.packetaValidatedAddress() === null && !window.localStorage.packetaValidatedAddress) {
                return defaultReturnValue;
            }

            return this.packetaValidatedAddress();
        },

        setPacketaValidatedAddress: function(packetaValidatedAddress) {
            window.localStorage.packetaValidatedAddress = JSON.stringify(packetaValidatedAddress);
            this.packetaValidatedAddress(packetaValidatedAddress);
        },

        resetPacketaValidatedAddress: function() {
            window.localStorage.packetaValidatedAddress = '';
            this.packetaValidatedAddress(null);
        }
    }
});
