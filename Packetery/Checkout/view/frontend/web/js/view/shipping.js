define(
    [
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/shipping-service',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/translate',
        'mage/storage',
        'mage/url',
        'ko'
    ], function(
        quote,
        shippingService,
        fullScreenLoader,
        $t,
        storage,
        url,
        ko
    ) {
        'use strict';

        var config = null;

        var getSelectedRateConfig = function() {
            var selectedShippingRateCode = mixin.getShippingRateCode(quote.shippingMethod());
            var config = mixin.shippingRatesConfig();
            return config[selectedShippingRateCode]; // rates config must be loaded at this time
        };

        var mixin = {
            isStoreConfigLoaded: ko.observable(false),
            pickedDeliveryPlace: ko.observable(''),
            pickedValidatedAddress: ko.observable(''), // address from widget
            shippingMethodConfig: ko.observable(null), // extra config for selected delivery method
            shippingRatesConfig: ko.observable(null),
            errorValidationMessage: ko.observable(''),

            getDestinationAddress: function() {
                var destinationAddress = window.packetaValidatedAddress || quote.shippingAddress() || quote.billingAddress(); // todo window not needed if shippingAddress will override
                return {
                    country: (destinationAddress.countryId).toLocaleLowerCase(),
                    countryId: destinationAddress.countryId,
                    houseNumber: destinationAddress.houseNumber,
                    postcode: destinationAddress.postcode,
                    street: destinationAddress.street.join(' '),
                    city: destinationAddress.city
                };
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
                var shippingRateConfig = getSelectedRateConfig();

                var options = {
                    country: destinationAddress.country,
                    language: config.packetaOptions.language,
                    layout: 'hd',
                    street: destinationAddress.street,
                    city: destinationAddress.city,
                    postcode: destinationAddress.postcode,
                    houseNumber: destinationAddress.houseNumber || '',
                    carrierId: shippingRateConfig.directionId,
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

                var selectedShippingRateConfig = getSelectedRateConfig();
                if(packeteryHDSelected() && selectedShippingRateConfig.addressValidation === 'required' && !window.packetaValidatedAddress) {
                    this.errorValidationMessage($t("Please select address via Packeta widget"));
                    return false;
                }

                return this._super();
            },

            getShippingRateCode: function(shippingRate) {
                shippingRate = shippingRate || {};
                return shippingRate.carrier_code + '_' + shippingRate.method_code;
            },

            getRateConfig: function(method) {
                var config = mixin.shippingRatesConfig();
                return config[mixin.getShippingRateCode(method)] || {};
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

        // when selected shipping method changes reset picked validated address because different carrier related to different rate may not support such address
        // it is up to widget to decide what address is valid for given carrierId
        // TODO: implement selected address history
        quote.shippingMethod.subscribe(createChangeSubscriber(resetPickedValidatedAddress, function(lastValue, value) {
            return lastValue !== value;
        }));

        var updateShippingRates = function(rates) {
            mixin.shippingRatesConfig(null);
            loadShippingRatesConfig(rates,function (responseValue) {
                mixin.shippingRatesConfig(responseValue.rates);
            });
        };

        var getShippingRateCollectionIdentificator = function(rates) {
            return rates.map(function(item) {
                return mixin.getShippingRateCode(item);
            }).join('+');
        };

        var shippingRatesSubscriber = createChangeSubscriber(updateShippingRates, function(lastValue, value) {
            return getShippingRateCollectionIdentificator(lastValue) !== getShippingRateCollectionIdentificator(value);
        });

        shippingService.getShippingRates().subscribe(shippingRatesSubscriber);

        var packeteryPickupPointSelected = function() {
            var shippingMethod = quote.shippingMethod();
            var selectedRateConfig = getSelectedRateConfig();
            if(shippingMethod && selectedRateConfig && selectedRateConfig.isPacketaRate && shippingMethod['method_code'] === 'pickupPointDelivery') {
                return true;
            }

            return false;
        };

        var packeteryHDSelected = function() {
            var selectedRateConfig = getSelectedRateConfig();
            if(selectedRateConfig && selectedRateConfig.isPacketaRate && selectedRateConfig.isAnyAddressDelivery) {
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
            mixin.errorValidationMessage('');

            if (!result) {
                mixin.errorValidationMessage($t("Address validation is out of order"));
                return;
            }

            if (!result.address) {
                return; // widget closed
            }

            resetPickedValidatedAddress();
            var destinationAddress = mixin.getDestinationAddress();
            var address = result.address;

            if (address.country !== destinationAddress.country) {
                mixin.errorValidationMessage($t("Please select address from specified country"));
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
            fullScreenLoader.startLoader();
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
                    return response.value;
                }
            ).always(
                function() {
                    fullScreenLoader.stopLoader();
                }
            );
        };

        var loadShippingRatesConfig = function(rates, onSuccess) {
            fullScreenLoader.startLoader();
            var serviceUrl = url.build('packetery/config/shippingRatesConfig');
            storage.post(
                serviceUrl,
                JSON.stringify({
                    rates: rates.map(function(rate) {
                        return {
                            rateCode: mixin.getShippingRateCode(rate),
                            carrierCode: rate.carrier_code,
                            methodCode: rate.method_code,
                            countryId: quote.shippingAddress().countryId, // countryId that Magento uses to collect shipping rates
                        };
                    }),
                })
            ).done(
                function(response) {
                    if(response.success) {
                        onSuccess(response.value);
                    }
                }
            ).fail(
                function(response) {
                    return response.value;
                }
            ).always(
                function() {
                    fullScreenLoader.stopLoader();
                }
            );
        };

        loadStoreConfig(function() {
            mixin.isStoreConfigLoaded(true);
        });

        shippingRatesSubscriber(shippingService.getShippingRates()()); // shippingService.getShippingRates() returns observable object

        return function(target) { // target == Result that Magento_Ui/.../default returns.
            return target.extend(mixin); // new result that all other modules receive
        };
    });
