<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Pricingrule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;

class MultiSave extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Packetery_Checkout::packetery';

    /** @var \Packetery\Checkout\Model\ResourceModel\PricingruleRepository */
    private $pricingruleRepository;

    /** @var \Packetery\Checkout\Model\Carrier\Facade */
    private $carrierFacade;

    /**
     * MultiSave constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Packetery\Checkout\Model\ResourceModel\PricingruleRepository $pricingruleRepository
     * @param \Packetery\Checkout\Model\Carrier\Facade $carrierFacade
     */
    public function __construct(
        Context $context,
        \Packetery\Checkout\Model\ResourceModel\PricingruleRepository $pricingruleRepository,
        \Packetery\Checkout\Model\Carrier\Facade $carrierFacade
    ) {
        parent::__construct($context);
        $this->pricingruleRepository = $pricingruleRepository;
        $this->carrierFacade = $carrierFacade;
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        if (!$this->getRequest()->isPost()) {
            throw new NotFoundException(__('Page not found'));
        }

        $country = null;

        $postData = $this->getRequest()->getPostValue()['shipping_methods'];

        foreach ($postData as &$carrierPriceRule) {
            $carrierEnabled = $carrierPriceRule['enabled'];
            $pricingRule = &$carrierPriceRule['pricing_rule'];
            $country = $pricingRule['country_id'];
            $pricingRule['enabled'] = (int)$carrierEnabled;
            $carrierCode = $pricingRule['carrier_code'];
            $carrierId = $pricingRule['carrier_id'] ?? null;
            $carrierId = $carrierId === null ? null : (int)$carrierId;
            $carrierName = $carrierPriceRule['carrier_name'] ?? null;
            $carrierPublicName = $this->carrierFacade->getPublicName($carrierCode, $carrierId);

            if (!$carrierEnabled) {
                if (isset($pricingRule['id'])) {
                    $this->pricingruleRepository->setPricingRuleEnabled((int)$pricingRule['id'], (bool)$carrierEnabled);
                }
                continue;
            }

            if ($carrierName && $this->carrierFacade->isDynamicCarrier($carrierCode, $carrierId)) {
                $this->carrierFacade->updateCarrierName($carrierName, $carrierCode, $carrierId);
            }

            $weightRules = $pricingRule['weight_rules']['weight_rules'] ?? [];
            unset($pricingRule['weight_rules']);

            if (empty($pricingRule['free_shipment']) && !is_numeric($pricingRule['free_shipment'])) {
                $pricingRule['free_shipment'] = null; // empty string is casted to 0
            }

            try {
                $this->pricingruleRepository->savePricingRule($pricingRule, $weightRules);
            } catch (\Packetery\Checkout\Model\Exception\DuplicateCountry $e) {
                $this->messageManager->addErrorMessage($carrierPublicName);
                $this->messageManager->addErrorMessage(__('Price rule for specified country already exists'));
                return $this->createRedirect($country);
            } catch (\Packetery\Checkout\Model\Exception\InvalidMaxWeight $e) {
                $this->messageManager->addErrorMessage($carrierPublicName);
                $this->messageManager->addErrorMessage(__('The weight is invalid'));
                return $this->createRedirect($country);
            } catch (\Packetery\Checkout\Model\Exception\PricingRuleNotFound $e) {
                $this->messageManager->addErrorMessage($carrierPublicName);
                $this->messageManager->addErrorMessage(__('Pricing rule not found'));
                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('pricingrule/carrierCountries');
            } catch (\Packetery\Checkout\Model\Exception\WeightRuleMissing $e) {
                $this->messageManager->addErrorMessage($carrierPublicName);
                $this->messageManager->addErrorMessage(__('Weight rule is missing'));
                return $this->createRedirect($country);
            }
        }

        $this->messageManager->addSuccessMessage(
            __('Saved')
        );

        return $this->createRedirect($country);
    }

    /**
     * @param $country
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function createRedirect($country = null): Redirect
    {
        if ($country) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('packetery/pricingrule/multiDetail/country/' . $country);
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('packetery/pricingrule/carrierCountries');
    }
}
