<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing;

use Magento\Framework\DB\Sql\Expression;

class SearchResult extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    protected function _initSelect() {
        $mainTableQuoted = $this->getConnection()->quoteIdentifier('main_table');
        $packeteryOrderTable = $this->getTable('packetery_order');
        $orderTable = $this->getTable('sales_order');

        $this->getSelect()
            ->from(
                [
                    'main_table' => new Expression(
                        "(
                             SELECT
                             {$mainTableQuoted}.order_number AS order_number_reference,
                             CONCAT_WS('', {$mainTableQuoted}.recipient_firstname, ' ',{$mainTableQuoted}.recipient_lastname) AS recipient_fullname,
                             CONCAT_WS('', {$mainTableQuoted}.recipient_street, ' ', {$mainTableQuoted}.recipient_house_number, ' ', {$mainTableQuoted}.recipient_city, ' ', {$mainTableQuoted}.recipient_zip) AS recipient_address,
                             CONCAT_WS('', {$mainTableQuoted}.point_name, ' ', {$mainTableQuoted}.point_id) AS delivery_destination,
                             {$mainTableQuoted}.value AS value_transformed,
                             IF({$mainTableQuoted}.cod > 0, 1, 0) AS cod_transformed,
                             {$mainTableQuoted}.exported AS exported_transformed,
                             {$mainTableQuoted}.exported_at AS exported_at_transformed,
                             sales_order.status AS order_status,
                             {$mainTableQuoted}.*
                             FROM {$packeteryOrderTable} AS {$mainTableQuoted}
                             LEFT JOIN {$orderTable} AS sales_order ON sales_order.increment_id = {$mainTableQuoted}.order_number
                        )"
                    ),
                ]
            );

        return $this;
    }
}
