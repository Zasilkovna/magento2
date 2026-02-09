<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Block\Adminhtml\Order;

use Packetery\Checkout\Block\Adminhtml\Order\GridExport;
use Packetery\Checkout\Test\BaseTest;

class GridExportTest extends BaseTest
{
    public function testCreateCsvContentReturnsCsvWithVersionHeaderAndDataRows(): void
    {
        $block = $this->getMockBuilder(GridExport::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getExportRow'])
            ->getMock();
        $block->method('getExportRow')->willReturn(['col1', 'col2']);
        $collection = new \ArrayIterator([new \stdClass(), new \stdClass()]);
        $result = $this->invokeMethod($block, 'createCsvContent', [$collection]);

        $this->assertNotNull($result);
        $this->assertStringContainsString('version 8', $result);
        $this->assertStringContainsString('col1,col2', $result);
        $lines = explode("\n", trim($result));
        $this->assertGreaterThanOrEqual(4, count($lines));
    }

    public function testCreateCsvContentReturnsHeaderOnlyForEmptyCollection(): void
    {
        $headerRow = ['order_number', 'recipient_firstname', 'recipient_lastname', 'recipient_company', 'cod'];
        $block = $this->getMockBuilder(GridExport::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getExportRow'])
            ->getMock();
        $block->method('getExportRow')->willReturn($headerRow);
        $collection = new \ArrayIterator([]);
        $result = $this->invokeMethod($block, 'createCsvContent', [$collection]);

        $this->assertNotNull($result);
        $this->assertStringContainsString('version 8', $result);
        $lines = explode("\n", trim($result));
        $this->assertCount(1, $lines);
        $this->assertStringNotContainsString(implode(',', $headerRow), $result);
    }
}
