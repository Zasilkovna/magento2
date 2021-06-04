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

    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Packetery\Checkout\Model\ResourceModel\PricingruleRepository $pricingruleRepository
     */
    public function __construct(
        Context $context,
        \Packetery\Checkout\Model\ResourceModel\PricingruleRepository $pricingruleRepository
    ) {
        $this->pricingruleRepository = $pricingruleRepository;

        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        if (!$this->getRequest()->isPost()) {
            throw new NotFoundException(__('Page not found'));
        }

        throw new NotFoundException(__('Page not found')); //todo rm

        $postData = $this->getRequest()->getPostValue()['general'];

        foreach ($postData as &$carrierPriceRule) {
            $weightRules = ($carrierPriceRule['weightRules']['weightRules'] ?? []);
            unset($carrierPriceRule['weightRules']);

            if (empty($carrierPriceRule['free_shipment']) && !is_numeric($carrierPriceRule['free_shipment'])) {
                $carrierPriceRule['free_shipment'] = null; // empty string is casted to 0
            }

            try {
                $this->pricingruleRepository->savePricingRule($carrierPriceRule, $weightRules);
            } catch (\Packetery\Checkout\Model\Exception\DuplicateCountry $e) {
                $this->messageManager->addErrorMessage(__('Price rule for specified country already exists')); // todo what carrier does it relates to?
                return $this->createPricingRuleDetailRedirect((isset($carrierPriceRule['id']) ? $carrierPriceRule['id'] : null));
            } catch (\Packetery\Checkout\Model\Exception\InvalidMaxWeight $e) {
                $this->messageManager->addErrorMessage(__('The weight is invalid'));
                return $this->createPricingRuleDetailRedirect((isset($carrierPriceRule['id']) ? $carrierPriceRule['id'] : null));
            } catch (\Packetery\Checkout\Model\Exception\PricingRuleNotFound $e) {
                $this->messageManager->addErrorMessage(__('Pricing rule not found'));
                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('packetery/carrierCountry/index');
            } catch (\Packetery\Checkout\Model\Exception\WeightRuleMissing $e) {
                $this->messageManager->addErrorMessage(__('Weight rule is missing'));
                return $this->createPricingRuleDetailRedirect((isset($carrierPriceRule['id']) ? $carrierPriceRule['id'] : null));
            }
        }

        $this->messageManager->addSuccessMessage(
            __('Saved')
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('packetery/carrierCountry/index'); // todo move to pricerule controller ?
    }

    /**
     * @param $id
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function createPricingRuleDetailRedirect($id): Redirect
    {
        if ($id > 0) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('packetery/pricingrule/multiDetail/country/' . $id);
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('packetery/carrierCountry/index');
    }
}
