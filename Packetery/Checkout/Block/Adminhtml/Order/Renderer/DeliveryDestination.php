<?php

namespace Packetery\Checkout\Block\Adminhtml\Order\Renderer;

use Magento\Framework\DataObject;
use Magento\Backend\Block\Context;
use Packetery\Checkout\Model\Pricing;

class DeliveryDestination extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /** @var Pricing\Service */
    private $pricingService;

    /** @var \Packetery\Checkout\Model\Config\Source\MethodSelect */
    private $methodSelect;

    /**
     * @param Context $context
     * @param Pricing\Service $pricingService
     * @param \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect
     * @param array $data
     */
    public function __construct(Context $context, Pricing\Service $pricingService, \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect, array $data = [])
    {
        parent::__construct($context, $data);
        $this->pricingService = $pricingService;
        $this->methodSelect = $methodSelect;
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
            return ($this->methodSelect->getLabelByValue((string)$branchName) ?: $branchName);
        }

        return sprintf("%s (%s)", $branchName, $branchId);
    }
}
