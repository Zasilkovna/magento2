<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\OrderCollection;

class SenderAddressProvider
{
    private \Magento\Store\Model\Information $storeInformation;

    private \Magento\Store\Model\StoreManagerInterface $storeManager;

    public function __construct(
        \Magento\Store\Model\Information $storeInformation,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->storeInformation = $storeInformation;
        $this->storeManager = $storeManager;
    }

    public function forStore(int $storeId): SenderAddress
    {
        $store = $this->storeManager->getStore($storeId);
        $info = $this->storeInformation->getStoreInformationObject($store);

        $streetLine1 = (string) $info->getData('street_line1');
        $streetLine2 = (string) $info->getData('street_line2');
        $street = trim($streetLine1 . ($streetLine2 !== '' ? ' ' . $streetLine2 : ''));

        return new SenderAddress(
            (string) $info->getData('name'),
            $street,
            (string) $info->getData('postcode'),
            (string) $info->getData('city'),
            (string) $info->getData('country_id')
        );
    }
}
