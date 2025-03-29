<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\CarrierCountry\Form;

use Magento\Ui\Component\Form;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Packetery\Checkout\Model\Carrier;
use Packetery\Checkout\Model\Carrier\Methods;
use Packetery\Checkout\Model\HybridCarrier;
use Packetery\Checkout\Model\Misc\ComboPhrase;

/**
 * Modifies multi detail pricing rule form xml structure and provides data for the form
 */
class Modifier implements ModifierInterface
{
    /** @var \Magento\Framework\App\RequestInterface */
    private $request;

    /** @var \Packetery\Checkout\Model\Pricing\Service */
    private $pricingService;

    /** @var \Packetery\Checkout\Model\Carrier\Facade */
    private $carrierFacade;

    /** @var \Packetery\Checkout\Model\AddressValidationSelect */
    private $addressValidationSelect;

    /** @var \Packetery\Checkout\Model\FeatureFlag\Manager  */
    private $featureFlagManager;

    /** @var \Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory */
    private $carrierCollectionFactory;

    /** @var Carrier\Imp\Packetery\Carrier */
    private $packeteryCarrier;

    /**
     * Modifier constructor.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     * @param \Packetery\Checkout\Model\Carrier\Facade $carrierFacade
     * @param \Packetery\Checkout\Model\AddressValidationSelect $addressValidationSelect
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        \Packetery\Checkout\Model\Carrier\Facade $carrierFacade,
        \Packetery\Checkout\Model\AddressValidationSelect $addressValidationSelect,
        \Packetery\Checkout\Model\FeatureFlag\Manager $featureFlagManager
    ) {
        $this->request = $request;
        $this->pricingService = $pricingService;
        $this->carrierFacade = $carrierFacade;
        $this->addressValidationSelect = $addressValidationSelect;
        $this->featureFlagManager = $featureFlagManager;
    }

    /**
     * @return \Packetery\Checkout\Model\HybridCarrier[]
     */
    public function getCarriers(string $country): array {
        $hybridCarriers = [];

        $staticCarriers = $this->carrierFacade->getPacketeryAbstractCarriers();
        usort(
            $staticCarriers,
            static function (Carrier\AbstractCarrier $staticCarrier): int {
                if ($staticCarrier instanceof Carrier\Imp\Packetery\Carrier) {
                    return 0; // Packetery is always first
                }

                return 1;
            }
        );

        foreach ($staticCarriers as $packeteryAbstractCarrier) {
            $packeteryAbstractCarrierBrain = $packeteryAbstractCarrier->getPacketeryBrain();
            $methods = $packeteryAbstractCarrierBrain->getMethodSelect()->getMethods();
            usort(
                $methods,
                static function (string $method): int {
                    if ($method === Methods::PICKUP_POINT_DELIVERY) {
                        return 0; // PP methods are first in list
                    }

                    return 1;
                }
            );

            foreach ($methods as $method) {
                // each hybrid carrier represent form fieldset as row
                $carriers = $packeteryAbstractCarrierBrain->findConfigurableDynamicCarriers($country, [$method]);
                $vendorCodeOptions = $this->carrierFacade->getVendorCodesOptions($carriers);

                if ($packeteryAbstractCarrierBrain->isAssignableToPricingRule()) {
                    // static carrier has no dynamic carriers
                    // static wrapping carriers are omitted
                    $availableCountries = $packeteryAbstractCarrierBrain->getAvailableCountries([$method]);
                    if (in_array($country, $availableCountries)) {
                        $hybridCarriers[] = HybridCarrier::fromAbstract($packeteryAbstractCarrier, $method, $country, $vendorCodeOptions);
                    }
                }

                foreach ($carriers as $carrier) {
                    $hybridCarriers[] = HybridCarrier::fromAbstractDynamic($packeteryAbstractCarrier, $carrier, $method, $country);
                }
            }
        }

        return $hybridCarriers;
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
        $carriers = $this->getCarriers($countryId);

        $newMeta = [];
        foreach ($carriers as $carrier) {
            $carrierFieldName = $this->getCarrierFieldName($carrier);
            $magentoCarrier = $this->carrierFacade->getMagentoCarrier($carrier->getData('carrier_code'));
            $carrierId = $carrier->getData('carrier_id');
            $dynamicCarrier = $magentoCarrier->getPacketeryBrain()->getDynamicCarrierById((is_numeric($carrierId) ? (int)$carrierId : null));
            $resolvedPricingRule = $this->pricingService->resolvePricingRule($carrier->getMethod(), $carrier->getCountry(), $carrier->getCarrierCode(), $carrier->getCarrierId());
            $carrierFieldLabel = $carrier->getFieldsetTitle($resolvedPricingRule);
            $isCarrierNameUpdatable = $dynamicCarrier !== null && $magentoCarrier->getPacketeryBrain() instanceof Carrier\IDynamicCarrierNameUpdater;
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
                                    'visible' => $isCarrierNameUpdatable,
                                    'validation' => [
                                        'required-entry' => $isCarrierNameUpdatable,
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

    /**
     * @param \Packetery\Checkout\Model\HybridCarrier $carrier
     * @param string $countryId
     * @return array
     */
    private function getPricingRuleFields(HybridCarrier $carrier, string $countryId): array {
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
            'vendor_groups' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Allowed pickup point types'),
                            'formElement' => 'checkboxset',
                            'dataType' => 'text',
                            'componentType' => 'field',
                            'additionalClasses' => 'packetery-checkboxset',
                            'visible' => $carrier->hasVendorGroupsOptions() && $this->featureFlagManager->isSplitActive(),
                            'disabled' => $carrier->hasNonInteractableVendorGroupsOptions(),
                            'required' => false,
                            'multiple' => true,
                            'options' => $carrier->getVendorGroupsOptions()
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
            'address_validation' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Address validation'),
                            'formElement' => 'select',
                            'dataType' => 'text',
                            'componentType' => 'field',
                            'visible' => Methods::isAnyAddressDelivery($carrier->getMethod()),
                            'required' => false,
                            'validation' => [
                                'required-entry' => false,
                            ],
                            'multiple' => false,
                            'options' => $this->addressValidationSelect->toOptionArray()
                        ],
                    ],
                ],
            ],
            'max_cod' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Max COD'),
                            'formElement' => 'input',
                            'dataType' => 'text',
                            'componentType' => 'field',
                            'visible' => true,
                            'required' => false,
                            'validation' => [
                                'required-entry' => false,
                                'validate-number' => true,
                                'validate-greater-than-zero' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'weight_rules' => $this->getWeightRules($carrier),
        ];
    }

    /**
     * @return array
     */
    private function getWeightRules(HybridCarrier $carrier): array {
        $weightUpperlimit = $this->carrierFacade->getMaxWeight($carrier->getCarrierCode(), $carrier->getCarrierId());

        $configRow = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'addButtonLabel' => __('Add'),
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
                                        'label' => new ComboPhrase(
                                            [
                                                __('Max. weight'),
                                                $weightUpperlimit === null ? '' : new ComboPhrase(['(max ', $weightUpperlimit, ')']),
                                            ],
                                            ' '
                                        ),
                                        'visible' => true,
                                        'formElement' => Form\Element\Input::NAME,
                                        'dataScope' => 'max_weight',
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
     * @param string $country
     * @param bool|null $enabled
     * @return array
     */
    public function getPricingRulesForCountry(string $country, ?bool $enabled = null): array {
        $data = $this->createData($country);

        $pricingRules = [];
        foreach ($data['shipping_methods'] as $shippingMethod) {
            if ($enabled !== null) {
                $enabledValue = ($enabled ? '1' : '0');
                if ($enabledValue !== $shippingMethod['enabled']) {
                    continue;
                }
            }

            if ($shippingMethod['pricing_rule']['id'] ?? false) {
                $pricingRules[] = $shippingMethod['pricing_rule']['id'];
            }
        }

        return $pricingRules;
    }

    /**
     * @param string $country
     * @return array
     */
    public function createData(string $country): array {
        $result = [
            'shipping_methods' => [],
        ];

        $carriers = $this->getCarriers($country);
        foreach ($carriers as $carrier) {
            $shippingMethod = [];
            $pricingRule = [];

            $carrierCode = $carrier->getData('carrier_code');
            $method = $carrier->getData('method');
            $carrierId = ($carrier->getData('carrier_id') ? (int)$carrier->getData('carrier_id') : null);

            $shippingMethod['carrier_name'] = $carrier->getFinalCarrierName();
            $resolvedPricingRule = $this->pricingService->resolvePricingRule($method, $country, $carrierCode, $carrierId);

            $shippingMethod['enabled'] = '0';
            $pricingRule['carrier_code'] = $carrierCode;
            $pricingRule['carrier_id'] = $carrierId;
            $pricingRule['country_id'] = $country;
            $pricingRule['method'] = $method;

            if ($resolvedPricingRule !== null) {
                $shippingMethod['enabled'] = ($resolvedPricingRule->getEnabled() ? '1' : '0');
                $pricingRule['id'] = $resolvedPricingRule->getId();
                $pricingRule['free_shipment'] = $resolvedPricingRule->getFreeShipment();
                $pricingRule['address_validation'] = $resolvedPricingRule->getAddressValidation();
                $pricingRule['max_cod'] = $resolvedPricingRule->getMaxCOD();
                $pricingRule['vendor_groups'] = $resolvedPricingRule->getVendorGroups() ?? [];

                $weightRules = $this->pricingService->getWeightRulesByPricingRule($resolvedPricingRule);
                $pricingRule['weight_rules']['weight_rules'] = [];
                foreach ($weightRules as $weightRule) {
                    $pricingRule['weight_rules']['weight_rules'][] = $weightRule->getData();
                }
            }

            if ($resolvedPricingRule === null && $carrier->hasNonInteractableVendorGroupsOptions()) {
                $pricingRule['vendor_groups'] = $carrier->getVendorCodesOptionsValues();
            }

            $shippingMethod['pricing_rule'] = $pricingRule;
            $result['shipping_methods'][$this->getCarrierFieldName($carrier)] = $shippingMethod;
        }

        return $result;
    }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data) {
        $country = $this->request->getParam('country');
        $result = $this->createData($country);
        return [$country => $result]; // see packetery_pricingrule_multiDetail.xml DataProvider
    }
}
