<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Box\Listing;

use Packetery\Checkout\Model\Box;

class SearchResult extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    protected function _renderFiltersBefore(): void
    {
        $this->getSelect()
            ->where(Box::DELETED . ' = ?', 0);

        parent::_renderFiltersBefore();
    }
}
