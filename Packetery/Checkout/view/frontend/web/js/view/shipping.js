define(
	[
		'jquery',
        'Magento_Checkout/js/model/quote',
		'mage/translate',
		'mage/storage',
		'mage/url'
	],function (
		$,
		quote,
		$t,
		storage,
		url) {
		'use strict';

        var packeteryPickupPointSelected = function() {
            var shippingMethod = quote.shippingMethod();
            if(shippingMethod && shippingMethod['method_code'] === 'pickupPointDelivery') {
                return true;
            }

            return false;
        };

        var packeteryToggleBoxes = function() {
            // to make sure our Open widget btn is properly displayed
            var shippingMethods = $('.packetery-method-wrapper [value^="packetery"][value*="pickupPointDelivery"]');
            shippingMethods.each(function() {
                var $item = $(this);
                var checked = $item.is(':checked');
                if(checked) {
                    $item.parents('.packetery-method-wrapper:first').next('.packetery-zas-box').show();
                } else {
                    $item.parents('.packetery-method-wrapper:first').next('.packetery-zas-box').hide();
                }
            });
        };

        function showSelectedPickupPoint(e, point) {
            var packetaButton = $(e.target);
            var zasBox = packetaButton.closest('.packetery-zas-box');
            var pickedDeliveryPlace = zasBox.find('.picked-delivery-place');

            pickedDeliveryPlace.text('');

            if(point) {
                var pointId = point.pickupPointType === 'external' ? point.carrierId : point.id;
                pickedDeliveryPlace.text(point ? point.name : "");

                // nastavíme, aby si pak pro založení objednávky převzal place-order.js, resp. OrderPlaceAfter.php
                window.packetaPoint = {
                    pointId: pointId ? pointId : null,
                    name: point.name ? point.name : null,
                    pickupPointType: point.pickupPointType ? point.pickupPointType : null,
                    carrierId: point.carrierId ? point.carrierId : null,
                    carrierPickupPointId: point.carrierPickupPointId ? point.carrierPickupPointId : null
                };
            } else {
                window.packetaPoint = {
                    pointId: null,
                    name: null,
                    pickupPointType: null,
                    carrierId: null,
                    carrierPickupPointId: null
                };
            }
        }

        var createZasBox = function($pickupPointInput) {
            if($pickupPointInput.length === 0) {
                return;
            }

            var methodWrapper = $pickupPointInput.closest('.row', $pickupPointInput.closest('.shipping-methods'));

            if(!methodWrapper.hasClass('packetery-method-wrapper')) {
                window.packetaPoint = {
                    pointId: null,
                    name: null,
                    pickupPointType: null,
                    carrierId: null,
                    carrierPickupPointId: null
                };

                methodWrapper.addClass('packetery-method-wrapper');
                packeteryToggleBoxes();
            }
        };

        var config = null;

        var loadStoreConfig = function () {
            return new Promise(function(resolve) {
                var serviceUrl = url.build('packetery/config/storeconfig');
                storage.get(serviceUrl).done(
                    function (response) {
                        if (response.success) {
                            config = JSON.parse(response.value);
                            resolve(config);
                        }
                    }
                ).fail(
                    function (response) {
                        return response.value
                    }
                );
            });
        };

        var packetaButtonClick = function (e) {
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

            Packeta.Widget.pick(packetaApiKey, function(point) {
                showSelectedPickupPoint(e, point);
            }, options);
        };

        $(document).ready(function() {
            $('body').on('change', '.methods-shipping [value^=packetery]', function() {
                packeteryToggleBoxes();
            }).on('click', '.methods-shipping .row', function() {
                packeteryToggleBoxes();
            }).on('click', '.packeta-widget-button', function(e) {
                packetaButtonClick(e);
            });
        });

		var mixin = {

            packeteryTemplateLoaded: function() {
                var $pickupPointInput = $('[value^="packetery"][value*="pickupPointDelivery"]');

                loadStoreConfig().then(function() {
                    createZasBox($pickupPointInput);
                });
            },

            setShippingInformation: function() {
                var packetaPoint = window.packetaPoint || {};
                if(packeteryPickupPointSelected() && !packetaPoint.pointId) {
                    var message = $t("Please select pickup point");
                    alert(message);
                    return;
                }

                return this._super();
            },
		};

		return function (target) { // target == Result that Magento_Ui/.../default returns.
			return target.extend(mixin); // new result that all other modules receive
		};
	});
