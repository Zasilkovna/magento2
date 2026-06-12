<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

class OrderCurrencyResolver
{
    /**
     * The Packet keeps its own snapshot of currency.
     * Fallback to the Magento order currency when it is missing, so the detail form and packet submission stay the same.
     */
    public function resolve(?Order $packeteryOrder, ?\Magento\Sales\Api\Data\OrderInterface $magentoOrder): ?string
    {
        if ($packeteryOrder !== null) {
            $currency = $packeteryOrder->getCurrency();
            if ($currency !== null && $currency !== '') {
                return $currency;
            }
        }

        if ($magentoOrder === null) {
            return null;
        }

        $currency = (string) $magentoOrder->getOrderCurrencyCode();

        return $currency !== '' ? $currency : null;
    }
}
