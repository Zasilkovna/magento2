<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Weight;

class Resolver
{
    public function __construct(
        private readonly \Magento\Checkout\Model\Session $checkoutSession,
        private readonly \Packetery\Checkout\Model\Weight\Calculator $calculator,
        private readonly \Packetery\Checkout\Model\OrderFactory $orderFactory,
        private readonly \Packetery\Checkout\Model\ResourceModel\Order $orderResource,
        private readonly \Magento\Framework\App\RequestInterface $request
    ) {}

    /**
     * @throws \LogicException
     */
    public function resolve(): ?float
    {
        $isCheckoutParam = $this->request->getParam('packetery_is_checkout');
        $orderIdParam = $this->request->getParam('packetery_order_id');
        if ($isCheckoutParam === '1') {
            if ($orderIdParam !== null) {
                throw new \LogicException('packetery_order_id must not be set in checkout context.');
            }

            $quote = $this->checkoutSession->getQuote();
            if ($quote && $quote->getId()) {
                return $this->calculator->getQuoteWeight($quote);
            }
            return null;
        }

        if ($isCheckoutParam === '0') {
            $packeteryOrderId = (int)$orderIdParam;
            if ($packeteryOrderId <= 0) {
                throw new \LogicException('Valid packetery_order_id is required.');
            }

            $packeteryOrder = $this->orderFactory->create();
            $this->orderResource->load($packeteryOrder, $packeteryOrderId);
            if (!$packeteryOrder->getId()) {
                throw new \LogicException("Packetery order with ID $packeteryOrderId was not found.");
            }

            return (float)$packeteryOrder->getWeight();
        }

        throw new \LogicException('Unexpected value of packetery_is_checkout.');
    }
}
