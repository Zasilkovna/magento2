<?php

declare(strict_types=1);

namespace Packetery\Checkout\Block\Adminhtml\Order\View\Tab;

use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class Packets extends Container implements TabInterface
{

    private const NAME = 'Packets';

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly RequestInterface $request,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _construct(
    ): void
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
        $order = $this->getOrder();
        if ($order) {
            $shippingMethod = $order->getShippingMethod();
            return $shippingMethod === 'your_shipping_method';
        }

        return false;
    }

    public function isHidden(): bool
    {
        return false;
    }

    private function getOrder(): ?OrderInterface
    {
        $orderId = (int) $this->request->getParam('order_id');

        return $this->orderRepository->get($orderId);
    }
}
