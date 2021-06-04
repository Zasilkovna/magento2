<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\CarrierCountry\Listing;

class SearchResult extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    public function addFieldToFilter($field, $condition = null) {
        if ($field === 'available') {
            $field = $this->getAvailableExpr();
            $resultCondition = $this->_translateCondition($field, $condition);
            $this->_select->having($resultCondition, null, \Magento\Framework\DB\Select::TYPE_CONDITION);
            return $this;
        }

        return parent::addFieldToFilter($field, $condition);
    }

    private function getAvailableExpr(): \Zend_Db_Expr {
        return new \Zend_Db_Expr('sum(case when `deleted` = 1 then 0 else 1 end) > 0');
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
