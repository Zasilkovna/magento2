define(
    [
        'Magento_Checkout/js/model/quote',
        'mage/translate',
        'mage/storage',
        'mage/url',
        'ko',
        'Magento_Checkout/js/model/url-builder'
    ], function(
        quote,
        $t,
        storage,
        url,
        ko,
        urlBuilder
    ) {
        'use strict';

        var config = null;
        var mixin = {
            isStoreConfigLoaded: ko.observable(false),
            pickedDeliveryPlace: ko.observable(''),
            pickedValidatedAddress: ko.observable(''), // address from widget
            shippingMethodConfig: ko.observable(null), // extra config for selected delivery method

            getDestinationAddress: function() {
                var destinationAddress = window.packetaValidatedAddress || quote.shippingAddress() || quote.billingAddress();
                return {
                    country: (destinationAddress.countryId).toLocaleLowerCase(),
                    countryId: destinationAddress.countryId,
                    houseNumber: destinationAddress.houseNumber,
                    postcode: destinationAddress.postcode,
                    street: destinationAddress.street.join(' '),
                    city: destinationAddress.city
                };
            },

            loadShippingMethodConfig: function() {
                // var methodCode = shippingMethod['method_code']; // e.g.: directAddressDelivery-106
                // var carrierCode = shippingMethod['carrier_code']; // packetery

                // if(shippingMethod && shippingMethod['method_code'] === 'pickupPointDelivery') {

                mixin.shippingMethodConfig(null); // make sure old data is not used

                setTimeout(function() {
                    var data = {
                        shippingMethod: quote.shippingMethod(),
                        config: {
                            carrierId: 131
                        } // todo dynamically fill with extension extra data
                    };

                    mixin.shippingMethodConfig(data);
                }, 1000);
            },

            packetaButtonClick: function() {
                if(config === null) {
                    return; // config not yet loaded
                }

                var packetaApiKey = config.apiKey;
                var countryCode = (quote.shippingAddress().countryId).toLocaleLowerCase();

                var options = {
                    webUrl: config.packetaOptions.webUrl,
                    appIdentity: config.packetaOptions.appIdentity,
                    country: countryCode,
                    language: config.packetaOptions.language,
                };

                Packeta.Widget.pick(packetaApiKey, showSelectedPickupPoint, options);
            },

            packetaHDButtonClick: function() {
                var packetaApiKey = config.apiKey;
                var destinationAddress = mixin.getDestinationAddress();
                var shippingMethodConfig = mixin.shippingMethodConfig();

                var options = {
                    country: destinationAddress.country,
                    language: config.packetaOptions.language,
                    layout: 'hd',
                    street: destinationAddress.street,
                    city: destinationAddress.city,
                    postcode: destinationAddress.postcode,
                    houseNumber: destinationAddress.houseNumber || '',
                    carrierId: shippingMethodConfig.config.carrierId, // todo
                };

                PacketaHD.Widget.pick(packetaApiKey, showSelectedAddress, options);
            },

            validateShippingInformation: function() {
                var packetaPoint = window.packetaPoint || {};
                if(packeteryPickupPointSelected() && !packetaPoint.pointId) {
                    var message = $t("Please select pickup point");
                    this.errorValidationMessage(message);
                    return false;
                }

                return this._super();
            },

            getShippingMethodCode: function(shippingMethod) {
                shippingMethod = shippingMethod || {};
                return shippingMethod.carrier_code + '_' + shippingMethod.method_code; // todo use in html
            }
        };

        var resetPickedPacketaPoint = function() {
            mixin.pickedDeliveryPlace('');
            window.packetaPoint = {
                pointId: null,
                name: null,
                pickupPointType: null,
                carrierId: null,
                carrierPickupPointId: null
            };
        };

        var resetPickedValidatedAddress = function() {
            mixin.pickedValidatedAddress('');
            window.packetaValidatedAddress = null;
        };

        var createChangeSubscriber = function(callback, comparator) {
            var lastVal = null;
            var init = true;

            return function (value) {
                if(init || comparator(lastVal, value)) {
                    init = false;
                    lastVal = value;
                    callback(value);
                }

                init = false;
                lastVal = value;
            };
        };

        resetPickedPacketaPoint();
        quote.shippingAddress.subscribe(createChangeSubscriber(resetPickedPacketaPoint, function(lastValue, value) {
            return lastValue.countryId !== value.countryId;
        }));

        resetPickedValidatedAddress();
        quote.shippingAddress.subscribe(createChangeSubscriber(resetPickedValidatedAddress, function(lastValue, value) {
            return lastValue.countryId !== value.countryId;
        }));

        quote.shippingMethod.subscribe(createChangeSubscriber(mixin.loadShippingMethodConfig, function(lastValue, value) {
            return mixin.getShippingMethodCode(lastValue) !== mixin.getShippingMethodCode(value);
        }));

        var packeteryPickupPointSelected = function() {
            var shippingMethod = quote.shippingMethod();
            if(shippingMethod && shippingMethod['method_code'] === 'pickupPointDelivery') {
                return true;
            }

            return false;
        };

        var showSelectedPickupPoint = function(point) {
            if(point) {
                var pointId = point.pickupPointType === 'external' ? point.carrierId : point.id;
                mixin.pickedDeliveryPlace(point ? point.name : "");

                // nastavíme, aby si pak pro založení objednávky převzal place-order.js, resp. OrderPlaceAfter.php
                window.packetaPoint = {
                    pointId: pointId ? pointId : null,
                    name: point.name ? point.name : null,
                    pickupPointType: point.pickupPointType ? point.pickupPointType : null,
                    carrierId: point.carrierId ? point.carrierId : null,
                    carrierPickupPointId: point.carrierPickupPointId ? point.carrierPickupPointId : null
                };
            } else {
                resetPickedPacketaPoint();
            }
        }

        var showSelectedAddress = function(result) {
            console.log(result);

            if (!result) {
                mixin.errorValidationMessage($t("Address validation is out of order."));
                return;
            }

            if (!result.address) {
                return; // widget closed
            }

            resetPickedValidatedAddress();
            var destinationAddress = mixin.getDestinationAddress();
            var address = result.address;

            if (address.country !== destinationAddress.country) {
                mixin.errorValidationMessage($t("Please select address from specified country."));
                return;
            }

            // todo override shipping address with validated address

            mixin.pickedValidatedAddress([ address.street, address.houseNumber, address.city ].filter(function(value) {
                return !!value;
            }).join(' '));

            window.packetaValidatedAddress = {
                city: address.city,
                street: [ address.street ],
                houseNumber: address.houseNumber,
                postcode: address.postcode,
                countryId: destinationAddress.countryId,
                county: address.county,
                longitude: address.longitude,
                latitude: address.latitude,
            };
        }

        var loadStoreConfig = function(onSuccess) {
            var serviceUrl = url.build('packetery/config/storeconfig');
            storage.get(serviceUrl).done(
                function(response) {
                    if(response.success) {
                        config = JSON.parse(response.value);
                        onSuccess(config);
                    }
                }
            ).fail(
                function(response) {
                    return response.value
                }
            );
        };

        var loadShippingRateConfig = function(onSuccess) {
            var serviceUrl = url.build('packetery/config/shippingRateConfig');
            storage.post(
                serviceUrl,
                JSON.stringify({
                    countryId: 'CZ',
                })
            ).done(
                function(response) {
                    if(response.success) {
                        config = JSON.parse(response.value);
                        onSuccess(config);
                    }
                }
            ).fail(
                function(response) {
                    return response.value
                }
            );
        };

        // todo load rate config
        loadStoreConfig(function() {
            mixin.isStoreConfigLoaded(true);
        });

        return function(target) { // target == Result that Magento_Ui/.../default returns.
            return target.extend(mixin); // new result that all other modules receive
        };
    });
