<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\CarrierCountry\Form;

use Magento\Ui\Component\Form;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Packetery\Checkout\Model\Carrier;
use Packetery\Checkout\Model\Carrier\Methods;
use Packetery\Checkout\Model\HybridCarrier;

/**
 * Modifies multi detail pricing rule form xml structure and provides data for the form
 */
class Modifier implements ModifierInterface
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory */
    private $carrierCollectionFactory;

    /** @var \Magento\Framework\App\RequestInterface */
    private $request;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier */
    private $packeteryCarrier;

    /** @var \Packetery\Checkout\Model\Pricing\Service */
    private $pricingService;

    /** @var \Packetery\Checkout\Model\Carrier\Facade */
    private $carrierFacade;

    /**
     * Modifier constructor.
     *
     * @param \Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory $carrierCollectionFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packeteryCarrier
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     * @param \Packetery\Checkout\Model\Carrier\Facade $carrierFacade
     */
    public function __construct(\Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory $carrierCollectionFactory, \Magento\Framework\App\RequestInterface $request, \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packeteryCarrier, \Packetery\Checkout\Model\Pricing\Service $pricingService, \Packetery\Checkout\Model\Carrier\Facade $carrierFacade) {
        $this->carrierCollectionFactory = $carrierCollectionFactory;
        $this->request = $request;
        $this->packeteryCarrier = $packeteryCarrier;
        $this->pricingService = $pricingService;
        $this->carrierFacade = $carrierFacade;
    }

    /**
     * @return \Packetery\Checkout\Model\HybridCarrier[]
     */
    private function getCarriersByParams(): array {
        $country = $this->request->getParam('country');
        return $this->getCarriers($country);
    }

    /**
     * @return \Packetery\Checkout\Model\HybridCarrier[]
     */
    public function getCarriers(string $country): array {
        /** @var \Packetery\Checkout\Model\ResourceModel\Carrier\Collection $collection */
        $collection = $this->carrierCollectionFactory->create();
        $collection->configurableOnly();
        $collection->whereCountry($country);
        $collection->forDeliveryMethod(Methods::ADDRESS_DELIVERY);
        $carriers = $collection->getItems();

        $carriers = array_map(
            function (Carrier $carrier) {
                return HybridCarrier::fromDynamic($carrier);
            },
            $carriers
        );

        if ($this->packeteryCarrier->getPacketeryBrain()->resolvePointId(Methods::ADDRESS_DELIVERY, $country)) {
            $packetaCarrier = HybridCarrier::fromAbstract($this->packeteryCarrier, Methods::ADDRESS_DELIVERY, $country);
            array_unshift($carriers, $packetaCarrier);
        }

        $packeteryCarrierPPCountries = $this->packeteryCarrier->getPacketeryBrain()->getAvailableCountries([Methods::PICKUP_POINT_DELIVERY]);
        if (in_array($country, $packeteryCarrierPPCountries)) {
            $packetaCarrier = HybridCarrier::fromAbstract($this->packeteryCarrier, Methods::PICKUP_POINT_DELIVERY, $country);
            array_unshift($carriers, $packetaCarrier);
        }

        return $carriers;
    }

    /**
     * @param \Packetery\Checkout\Model\HybridCarrier $carrier
     * @return string
     */
    private function getCarrierFieldName(HybridCarrier $carrier): string {
        return $carrier->getData('carrier_code') . '_' . $carrier->getData('method_code'); // pure number wont work
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta) {
        $countryId = $this->request->getParam('country');

        $carriers = $this->getCarriersByParams();

        $newMeta = [];
        foreach ($carriers as $carrier) {
            $carrierFieldName = $this->getCarrierFieldName($carrier);
            $isDynamic = $this->carrierFacade->isDynamicCarrier($carrier->getData('carrier_code'), $carrier->getData('carrier_id'));
            $resolvedPricingRule = $this->pricingService->resolvePricingRule($carrier->getMethod(), $carrier->getCountry(), $carrier->getCarrierCode(), $carrier->getCarrierId());
            $carrierFieldLabel = $carrier->getFieldsetTitle($resolvedPricingRule);
            $newMeta[$carrierFieldName] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => $carrierFieldLabel,
                            'componentType' => 'fieldset',
                            'collapsible' => true,
                        ],
                    ],
                ],
                'children' => [
                    'enabled' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'dataType' => 'boolean',
                                    'formElement' => 'checkbox',
                                    'componentType' => 'field',
                                    'visible' => true,
                                    'label' => __('Use carrier?'),
                                    'globalScope' => false,
                                    'prefer' => 'toggle',
                                    'valueMap' => [
                                        'true' => '1',
                                        'false' => '0',
                                    ],
                                    'additionalClasses' => 'packetery-checkbox',
                                    'switcherConfig' => [
                                        'rules' => [
                                            '0' => [
                                                "value" => '0',
                                                "actions" => [
                                                    '0' => [
                                                        "target" => "packetery_pricingrule_multiDetail.areas.shipping_methods.shipping_methods.{$carrierFieldName}.pricing_rule",
                                                        "callback" => "hide",
                                                    ],
                                                    '1' => [
                                                        "target" => "packetery_pricingrule_multiDetail.areas.shipping_methods.shipping_methods.{$carrierFieldName}.carrier_name",
                                                        "callback" => "hide",
                                                    ],
                                                ],
                                            ],
                                            '1' => [
                                                "value" => '1',
                                                "actions" => [
                                                    '0' => [
                                                        "target" => "packetery_pricingrule_multiDetail.areas.shipping_methods.shipping_methods.{$carrierFieldName}.pricing_rule",
                                                        "callback" => "show",
                                                    ],
                                                    '1' => [
                                                        "target" => "packetery_pricingrule_multiDetail.areas.shipping_methods.shipping_methods.{$carrierFieldName}.carrier_name",
                                                        "callback" => "show",
                                                    ],
                                                ],
                                            ],
                                        ],
                                        'enabled' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'carrier_name' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'formElement' => 'input',
                                    'dataType' => 'text',
                                    'componentType' => 'field',
                                    'label' => __('Carrier name'),
                                    'visible' => $isDynamic,
                                    'validation' => [
                                        'required-entry' => $isDynamic,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'pricing_rule' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'componentType' => 'container',
                                    'component' => 'Packetery_Checkout/js/view/multidetail-carrier-data-container',
                                ],
                            ],
                        ],
                        'children' => $this->getPricingRuleFields($carrier, $countryId),
                    ],
                ],
            ];
        }

        $meta = array_replace_recursive(
            $meta,
            [
                'shipping_methods' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => '',
                                'componentType' => 'fieldset',
                                'collapsible' => false,
                            ],
                        ],
                    ],
                    'children' => $newMeta,
                ],
            ]
        );

        return $meta;
    }

    private function getPricingRuleFields($carrier, $countryId): array {
        return [
            'id' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => 'input',
                            'dataType' => 'text',
                            'componentType' => 'field',
                            'visible' => false,
                            'required' => true,
                        ],
                    ],
                ],
            ],
            'carrier_id' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => 'input',
                            'dataType' => 'text',
                            'componentType' => 'field',
                            'visible' => false,
                            'required' => true,
                            'value' => $carrier->getData('carrier_id'), // Mordor ID
                        ],
                    ],
                ],
            ],
            'carrier_code' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => 'input',
                            'dataType' => 'text',
                            'componentType' => 'field',
                            'visible' => false,
                            'required' => true,
                            'value' => $carrier->getData('carrier_code'), // Magento carrier code
                        ],
                    ],
                ],
            ],
            'country_id' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => 'input',
                            'dataType' => 'text',
                            'componentType' => 'field',
                            'visible' => false,
                            'required' => true,
                            'value' => $countryId,
                        ],
                    ],
                ],
            ],
            'method' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => 'input',
                            'dataType' => 'text',
                            'componentType' => 'field',
                            'visible' => false,
                            'required' => true,
                            'value' => $carrier->getData('method'),
                        ],
                    ],
                ],
            ],
            'free_shipment' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Free shipment threshold'),
                            'formElement' => 'input',
                            'dataType' => 'text',
                            'componentType' => 'field',
                            'visible' => true,
                            'required' => false,
                            'validation' => [
                                'required-entry' => false,
                            ],
                        ],
                    ],
                ],
            ],
            'weight_rules' => $this->getWeightRules(),
        ];
    }

    /**
     * @return array
     */
    private function getWeightRules(): array {
        $configRow = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'addButtonLabel' => __('Add weight rule'),
                        'componentType' => 'dynamicRows',
                        'identificationProperty' => 'id',
                        'defaultRecord' => 'true',
                        'additionalClasses' => 'admin__field-wide',
                    ],
                ],
            ],
            'children' => [
                'record' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => 'container',
                                'component' => 'Magento_Ui/js/dynamic-rows/record',
                                'isTemplate' => true,
                                'is_collection' => true,
                            ],
                        ],
                    ],
                    'children' => [
                        'id' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => Form\Field::NAME,
                                        'dataType' => Form\Element\DataType\Text::NAME,
                                        'label' => __('ID'),
                                        'visible' => false,
                                        'formElement' => Form\Element\Input::NAME,
                                        'dataScope' => 'id',
                                    ],
                                ],
                            ],
                        ],
                        'max_weight' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => Form\Field::NAME,
                                        'dataType' => Form\Element\DataType\Text::NAME,
                                        'label' => __('Max. weight'),
                                        'visible' => true,
                                        'formElement' => Form\Element\Input::NAME,
                                        'dataScope' => 'max_weight',
                                        'fit' => false,
                                        'notice' => __('Empty value is going to fallback to global max weight'),
                                        'validation' => [
                                            'required-entry' => false,
                                            'validate-number' => true,
                                            'validate-greater-than-zero' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'price' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => Form\Field::NAME,
                                        'dataType' => Form\Element\DataType\Text::NAME,
                                        'label' => __('Price'),
                                        'visible' => true,
                                        'formElement' => Form\Element\Input::NAME,
                                        'dataScope' => 'price',
                                        'fit' => false,
                                        'validation' => [
                                            'required-entry' => true,
                                            'validate-number' => true,
                                            'validate-greater-than-zero' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'action_delete' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => 'actionDelete',
                                        'dataType' => 'text',
                                        'fit' => false,
                                        'label' => __('Actions'),
                                        'additionalClasses' => 'data-grid-actions-cell',
                                        'template' => 'Magento_Backend/dynamic-rows/cells/action-delete',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        return $configRow;
    }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data) {
        $country = $this->request->getParam('country');

        $result = [
            'shipping_methods' => [],
        ];

        $carriers = $this->getCarriersByParams();
        foreach ($carriers as $carrier) {
            $shippingMethod = [];
            $pricingRule = [];

            $carrierCode = $carrier->getData('carrier_code');
            $method = $carrier->getData('method');
            $carrierId = $carrier->getData('carrier_id') ? (int)$carrier->getData('carrier_id') : null;

            $shippingMethod['carrier_name'] = $carrier->getFinalCarrierName();
            $resolvedPricingRule = $this->pricingService->resolvePricingRule($method, $carrier->getCountry(), $carrierCode, $carrierId);

            $shippingMethod['enabled'] = '0';
            $pricingRule['carrier_code'] = $carrierCode;
            $pricingRule['carrier_id'] = $carrierId;
            $pricingRule['country_id'] = $country;
            $pricingRule['method'] = $method;

            if ($resolvedPricingRule !== null) {
                $shippingMethod['enabled'] = ($resolvedPricingRule->getEnabled() ? '1' : '0');
                $pricingRule['id'] = $resolvedPricingRule->getId();
                $pricingRule['free_shipment'] = $resolvedPricingRule->getFreeShipment();

                $weightRules = $this->pricingService->getWeightRulesByPricingRule($resolvedPricingRule);
                $pricingRule['weight_rules']['weight_rules'] = [];
                foreach ($weightRules as $weightRule) {
                    $pricingRule['weight_rules']['weight_rules'][] = $weightRule->getData();
                }
            }

            $shippingMethod['pricing_rule'] = $pricingRule;
            $result['shipping_methods'][$this->getCarrierFieldName($carrier)] = $shippingMethod;
        }

        return [$country => $result];
    }
}
