define([
    'jquery',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract',
    'ko',
    'mage/translate',
    'mage/storage',
    'mage/url'
], function(
    $,
    _,
    uiRegistry,
    Component,
    ko,
    $t,
    storage,
    frontUrlBuilder
) {
    'use strict';

    frontUrlBuilder.setBaseUrl(window.packetery.baseUrl);

    return Component.extend({
        defaults: {
            template: 'Packetery_Checkout/form/element/point-pick-button',
        },
        /** @inheritdoc */
        initialize: function() {
            this._super();

            var methodField = uiRegistry.get('index = method');
            var methodValue = methodField.initialValue;

            if(methodValue !== 'pickupPointDelivery' && methodValue !== 'packetery') {
                return this;
            }

            var config = {};
            var loadConfig = function() {
                return new Promise(function(resolve, reject) {
                    var configUrl = frontUrlBuilder.build('packetery/config/storeconfig');
                    storage.get(configUrl).done(
                        function(response) {
                            if(response.success) {
                                config = JSON.parse(response.value);
                                resolve(config);
                            } else {
                                console.error('Endpoint for packeta config returned non-success response');
                                reject(response);
                            }
                        }
                    ).fail(
                        function(response) {
                            console.error('Endpoint for packeta config failed');
                            reject(response);
                        }
                    );
                });
            };

            $('body').on('click', '.packetery-order-detail-form .packetery-admin-pickup-point-picker', function(e) {
                if(!config.apiKey) {
                    alert($t('The specified API key is not valid!')); // user does not have apiKey
                    return;
                }

                var packetaApiKey = config.apiKey;
                var countryId = uiRegistry.get('inputName = general[misc][country_id]').value();

                var options = {
                    webUrl: config.packetaOptions.webUrl,
                    appIdentity: config.packetaOptions.appIdentity,
                    country: countryId.toLocaleLowerCase(),
                    language: config.packetaOptions.language,
                };

                var pickupPointSelected = function(point) {
                    if(point) {
                        var pointId = (point.pickupPointType === 'external' ? point.carrierId : point.id);
                        var pointName = (point ? point.name : '');
                        var carrierId = (point.carrierId ? point.carrierId : null);
                        var carrierPickupPointId = (point.carrierPickupPointId ? point.carrierPickupPointId : null);

                        uiRegistry.get('inputName = general[point_id]').value(pointId);
                        uiRegistry.get('inputName = general[point_name]').value(pointName);
                        uiRegistry.get('inputName = general[is_carrier]').value(carrierId ? 1 : 0);
                        uiRegistry.get('inputName = general[carrier_pickup_point]').value(carrierPickupPointId);
                    }
                };

                Packeta.Widget.pick(packetaApiKey, pickupPointSelected, options);
            });

            $(document).ready(function() {
                loadConfig().then(function() {
                    $('.packetery-order-detail-form [data-index="point_picker_field"]').removeClass('hidden');
                });
            });

            return this;
        }
    });
});
