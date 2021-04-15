<?php

namespace Packetery\Checkout\Block\Adminhtml\Order\Renderer;

use Magento\Framework\DataObject;
use Magento\Backend\Block\Context;
use Packetery\Checkout\Model\Pricing;

class DeliveryDestination extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /** @var Pricing\Service */
    private $pricingService;

    /**
     * @param Context $context
     * @param Pricing\Service $pricingService
     * @param array $data
     */
    public function __construct(Context $context, Pricing\Service $pricingService, array $data = [])
    {
        $this->_authorization = $context->getAuthorization();
        $this->pricingService = $pricingService;
        parent::__construct($context, $data);
    }

    /**
     * render address
     * @param  DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $branchName = $row->getData('point_name');
        $branchId = $row->getData('point_id');

        if ($this->pricingService->isResolvablePointId((int)$branchId)) {
            return __($branchName);
        }

        return sprintf("%s (%s)", $branchName, $branchId);
    }
}
