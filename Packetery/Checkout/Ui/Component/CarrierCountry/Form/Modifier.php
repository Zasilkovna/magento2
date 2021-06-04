<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\CarrierCountry\Form;

use Magento\Ui\Component\Form;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Packetery\Checkout\Model\Carrier\Methods;

class Modifier implements ModifierInterface
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory */
    private $carrierCollectionFactory;

    /** @var \Magento\Framework\App\RequestInterface */
    private $request;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier */
    private $packeteryCarrier;

    /**
     * Modifier constructor.
     *
     * @param \Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory $carrierCollectionFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packeteryCarrier
     */
    public function __construct(\Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory $carrierCollectionFactory, \Magento\Framework\App\RequestInterface $request, \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packeteryCarrier) {
        $this->carrierCollectionFactory = $carrierCollectionFactory;
        $this->request = $request;
        $this->packeteryCarrier = $packeteryCarrier;
    }

    public function modifyMeta(array $meta) {
        $country = $this->request->getParam('country');
        $countryId = strtoupper($country);

        /** @var \Packetery\Checkout\Model\ResourceModel\Carrier\Collection $collection */
        $collection = $this->carrierCollectionFactory->create();
        $collection->addFilter('country', $country);
        $collection->addFilter('is_pickup_points', 0);
        $collection->addFilter('deleted', 0); // The shopkeeper wants to change the price, but he can't. He goes to do something else. In an hour, the Packeta will turn on his carrier and it will have the old price.
        $carriers = $collection->getItems();

        if ($this->packeteryCarrier->getPacketeryBrain()->resolvePointId(Methods::ADDRESS_DELIVERY, $countryId)) {
            $packetaCarrier = $collection->getNewEmptyItem();
            $packetaCarrier->setData(
                [
                    'carrier_id' => null,
                    'carrier_code' => 'packetery',
                    'method' => Methods::ADDRESS_DELIVERY,
                    'method_code' => Methods::ADDRESS_DELIVERY,
                    'name' => $countryId . ' Packeta HD',
                ]
            );

            array_unshift($carriers, $packetaCarrier);
        }

        $packetaCarrier = $collection->getNewEmptyItem();
        $packetaCarrier->setData(
            [
                'carrier_id' => null,
                'carrier_code' => 'packetery',
                'method' => Methods::PICKUP_POINT_DELIVERY,
                'method_code' => Methods::PICKUP_POINT_DELIVERY,
                'name' => $countryId . ' Packeta PP',
            ]
        );

        array_unshift($carriers, $packetaCarrier);

        $newMeta = [];
        foreach ($carriers as $carrier) {
            if (empty($carrier->getData('carrier_code'))) {
                $carrier->setData('carrier_code', 'packeteryDynamic'); // todo create Magento class
            }

            if (empty($carrier->getData('method'))) {
                if ($carrier->getData('is_pickup_points') === '1') {
                    $carrier->setData('method', Methods::PICKUP_POINT_DELIVERY);
                } else {
                    $carrier->setData('method', Methods::ADDRESS_DELIVERY);
                }
            }
            $method = $carrier->getData('method');

            if (empty($carrier->getData('method_code'))) {
                if ($carrier->getData('carrier_id')) {
                    $carrier->setData('method_code', $carrier->getData('carrier_id') . '-' . $method);
                } else {
                    $carrier->setData('method_code', $method);
                }
            }

            $carrierFieldName = $carrier->getData('carrier_code') . '_' . $carrier->getData('method_code'); // pure number wont work
            $newMeta[$carrierFieldName] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => $carrier->getData('name'),
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
                                    'required' => false,
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
                                            [
                                                "value" => '0',
                                                "actions" => [
                                                    [
                                                        "target" => "packetery_pricingrule_multiDetail.areas.shipping_methods.shipping_methods.{$carrierFieldName}.pricing_rule",
                                                        "callback" => "hide",
                                                    ],
                                                ],
                                            ],
                                            [
                                                "value" => '1',
                                                "actions" => [
                                                    [
                                                        "target" => "packetery_pricingrule_multiDetail.areas.shipping_methods.shipping_methods.{$carrierFieldName}.pricing_rule",
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
                    'pricing_rule' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'componentType' => 'container',
                                    'component' => 'Packetery_Checkout/js/view/multidetail-carrier-data-container',
                                    'visible' => true,
//                                    'additionalClasses' => 'packetery-settings-fieldset'
                                ],
                            ],
                        ],
                        'children' => $this->getPricingRuleFields($carrier, $country, $method),
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

    protected function getPricingRuleFields($carrier, $country, $method): array {
        return [
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
                            'value' => strtoupper($country),
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
                            'value' => $method,
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
                            'value' => null, // todo needed?
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
        return $data;
    }
}
