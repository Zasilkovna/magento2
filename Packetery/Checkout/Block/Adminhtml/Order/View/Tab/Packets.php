<?php

declare(strict_types=1);

namespace Packetery\Checkout\Block\Adminhtml\Order\View\Tab;

use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Block\Adminhtml\Order\View as OrderViewBlock;
use Packetery\Checkout\Model\Carrier\Imp\Packetery\Brain;

class Packets extends Container implements TabInterface
{
    private const NAME = 'Packets';

    public function __construct(
        Context $context,
        private readonly Brain $carrierBrain,
        private readonly OrderViewBlock $orderViewBlock,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _construct(): void
    {
        parent::_construct();
        $this->setTemplate('Packetery_Checkout::packets.phtml');
    }

    public function getTabLabel(): Phrase
    {
        return __(self::NAME);
    }

    public function getTabTitle(): Phrase
    {
        return $this->getTabLabel();
    }

    public function canShowTab(): bool
    {
        return $this->carrierBrain->isPacketeryShippingGroup($this->getOrder()->getShippingMethod());
    }

    public function isHidden(): bool
    {
        return !$this->canShowTab();
    }

    private function getOrder(): OrderInterface
    {
        return $this->orderViewBlock->getOrder();
    }
}
