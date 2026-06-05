<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Ui\Component\Box\Listing;

use Magento\Framework\DB\Select;
use Packetery\Checkout\Model\Box;
use Packetery\Checkout\Test\BaseTest;
use Packetery\Checkout\Ui\Component\Box\Listing\SearchResult;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class SearchResultTest extends BaseTest
{
    public function testRenderFiltersBeforeHidesDeletedBoxes(): void
    {
        $select = $this->createMockWithProps(Select::class);
        $select->expects($this->once())
            ->method('where')
            ->with(Box::DELETED . ' = ?', 0);

        /** @var SearchResult $searchResult */
        $searchResult = $this->createProxy(
            SearchResult::class,
            [],
            ['getSelect' => $select]
        );

        $this->invokeMethod($searchResult, '_renderFiltersBefore');
    }
}
