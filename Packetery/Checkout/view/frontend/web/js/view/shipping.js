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
            if($('.packetery-method-wrapper [value^="packetery"][value*="pickupPointDelivery"]:checked').length > 0) {
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
                methodWrapper.after('<tr class="packetery-zas-box">\n' +
                    '    <td colspan="4">\n' +
                    '        <img src="data:image/png;base64,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" class="icon"/>\n' +
                    '        <a href="javascript:void(0)" class="button action continue primary packeta-widget-button">\n' +
                    '            ' + $t('Select pick-up point') +
                    '        </a>\n' +
                    '        <br>\n' +
                    '        <span class="picked-delivery-place"></span>\n' +
                    '    </td>\n' +
                    '</tr>');

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
            configTriggered: false,

            getRegion: function() {
                var $pickupPointInput = $('[value^="packetery"][value*="pickupPointDelivery"]');
                if($pickupPointInput.length === 0) {
                    return this._super();
                }

                if(this.configTriggered === false && config === null) {
                    this.configTriggered = true;
                    loadStoreConfig().then(function() {
                        createZasBox($pickupPointInput);
                    });
                }

                if (config !== null) {
                    createZasBox($pickupPointInput);
                }

                return this._super();
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
