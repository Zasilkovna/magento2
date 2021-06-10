<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\CarrierCountry\Listing;

class SearchResult extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    protected function _initSelect() {
        $packeteryCarrierTable = $this->getTable('packetery_carrier');
        $this->getSelect()
            ->from(['main_table' => $packeteryCarrierTable])
            ->columns(
                [
                    'country',
                ]
            )
            ->group(
                [
                    'country',
                ]
            );

        return $this;
    }
}
