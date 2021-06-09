<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\CarrierCountry\Listing;

class SearchResult extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    private function getAvailableExpr(): \Zend_Db_Expr {
        // todo is it need when Packeta will be there if enabled?
        // todo fix
        return new \Zend_Db_Expr('1');
    }

    protected function _initSelect() {
        $packeteryCarrierTable = $this->getTable('packetery_carrier');
        $this->getSelect()
            ->from(['main_table' => $packeteryCarrierTable])
            ->columns(
                [
                    'country',
                    'available' => $this->getAvailableExpr(),
                ]
            )
            ->order(
                [
                    'country ASC',
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
