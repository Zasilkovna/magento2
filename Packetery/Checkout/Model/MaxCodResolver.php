<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

use Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory as CarrierCollectionFactory;

class MaxCodResolver
{
    /** @var CarrierCollectionFactory */
    private $carrierCollectionFactory;

    public function __construct(CarrierCollectionFactory $carrierCollectionFactory)
    {
        $this->carrierCollectionFactory = $carrierCollectionFactory;
    }

    public function resolve(Pricingrule $pricingRule): ?float
    {
        $maxCod = $pricingRule->getMaxCOD();
        if ($maxCod === null) {
            return null;
        }

        $carrierId = $pricingRule->getCarrierId();
        if ($carrierId === null) {
            return $maxCod;
        }

        $collection = $this->carrierCollectionFactory->create();
        /** @var Carrier|null $carrier */
        $carrier = $collection->getItemByColumnValue('carrier_id', $carrierId);
        if ($carrier === null) {
            return $maxCod;
        }

        if ($carrier->disallowsCod()) {
            return null;
        }

        return $maxCod;
    }
}

