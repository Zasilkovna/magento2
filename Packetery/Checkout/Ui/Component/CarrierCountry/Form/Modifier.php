<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\CarrierCountry\Form;

use Magento\Ui\Component\Form;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Packetery\Checkout\Model\Carrier\MethodCode;
use Packetery\Checkout\Model\Carrier\Methods;

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
     * @return \Packetery\Checkout\Model\Carrier[]
     */
    private function getCarriers(): array {
        $country = $this->request->getParam('country');
        $countryId = strtoupper($country);

        /** @var \Packetery\Checkout\Model\ResourceModel\Carrier\Collection $collection */
        $collection = $this->carrierCollectionFactory->create();
        $collection->addFilter('country', $country);
        $collection->addFilter('is_pickup_points', 0);
//        $collection->addFilter('deleted', 0); // The shopkeeper wants to change the price, but he can't. He goes to do something else. In an hour, the Packeta will turn on his carrier and it will have the old price.
        $carriers = $collection->getItems();

        if ($this->packeteryCarrier->getPacketeryBrain()->resolvePointId(Methods::ADDRESS_DELIVERY, $countryId)) {
            $packetaCarrier = $collection->getNewEmptyItem();
            $packetaCarrier->setData(
                [
                    'country' => $country,
                    'carrier_id' => null,
                    'carrier_code' => \Packetery\Checkout\Model\Carrier\Imp\Packetery\Brain::getCarrierCodeStatic(),
                    'method' => Methods::ADDRESS_DELIVERY,
                    'method_code' => (new MethodCode(Methods::ADDRESS_DELIVERY, null))->toString(),
                    'name' => $countryId . ' Packeta HD',
                ]
            );

            array_unshift($carriers, $packetaCarrier);
        }

        $packetaCarrier = $collection->getNewEmptyItem();
        $packetaCarrier->setData(
            [
                'country' => $country,
                'carrier_id' => null,
                'carrier_code' => \Packetery\Checkout\Model\Carrier\Imp\Packetery\Brain::getCarrierCodeStatic(),
                'method' => Methods::PICKUP_POINT_DELIVERY,
                'method_code' => (new MethodCode(Methods::PICKUP_POINT_DELIVERY, null))->toString(),
                'name' => $countryId . ' Packeta PP',
            ]
        );

        array_unshift($carriers, $packetaCarrier);
        return $carriers;
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier $carrier
     * @return string
     */
    private function getCarrierFieldName(\Packetery\Checkout\Model\Carrier $carrier): string {
        return $carrier->getData('carrier_code') . '_' . $carrier->getData('method_code');
    }

    public function modifyMeta(array $meta) {
        $country = $this->request->getParam('country');
        $countryId = strtoupper($country);

        $carriers = $this->getCarriers();

        $newMeta = [];
        foreach ($carriers as $carrier) {
            $this->enrichCarrierData($carrier);
            $carrierFieldName = $this->getCarrierFieldName($carrier); // pure number wont work
            $isDynamic = $this->carrierFacade->isDynamicCarrier($carrier->getData('carrier_code'), $carrier->getData('carrier_id'));

            $carrierFieldLabel = $carrier->getData('name');

            if ($carrier->getData('deleted')) {
                $carrierFieldLabel = '(disabled by Packeta) ' . $carrierFieldLabel;
            }

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
                    'country' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'formElement' => 'input',
                                    'dataType' => 'text',
                                    'componentType' => 'field',
                                    'visible' => false,
                                ],
                            ],
                        ],
                    ],
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

    protected function getPricingRuleFields($carrier, $countryId): array {
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

    protected function getWeightRules(): array {
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

    public function modifyData(array $data) {
        $country = $this->request->getParam('country');

        $result = [
            'shipping_methods' => [],
        ];

        $carriers = $this->getCarriers();
        foreach ($carriers as $carrier) {
            $shippingMethod = [];
            $pricingRule = [];

            $this->enrichCarrierData($carrier);
            $carrierCode = $carrier->getData('carrier_code'); // todo new class to represent this hybrid?
            $method = $carrier->getData('method');
            $carrierId = $carrier->getData('carrier_id') ? (int)$carrier->getData('carrier_id') : null;

            $shippingMethod['carrier_name'] = $carrier->getFinalCarrierName();
            $resolvedPricingRule = $this->pricingService->resolvePricingRule($method, $carrier->getCountryId(), $carrierCode, $carrierId);

            $shippingMethod['enabled'] = '0';
            $shippingMethod['country'] = $country;
            $pricingRule['carrier_code'] = $carrierCode;
            $pricingRule['carrier_id'] = $carrierId;
            $pricingRule['country_id'] = strtoupper($country);
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

    /**
     * @param \Packetery\Checkout\Model\Carrier $carrier
     */
    private function enrichCarrierData(\Packetery\Checkout\Model\Carrier $carrier): void {
        if (empty($carrier->getData('carrier_code'))) {
            $carrier->setData('carrier_code', \Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Brain::getCarrierCodeStatic());
        }

        // we are mixing dynamic carriers with fixed
        if (empty($carrier->getData('method'))) {
            $carrier->setData('method', $carrier->getMethod());
        }

        if (empty($carrier->getData('method_code'))) {
            $carrier->setData('method_code', (new MethodCode($carrier->getData('method'), $carrier->getCarrierId()))->toString());
        }
    }
}
