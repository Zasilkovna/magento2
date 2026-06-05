<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Model\ResourceModel;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Packetery\Checkout\Model\Box;
use Packetery\Checkout\Model\ResourceModel\Box as BoxResource;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class BoxTest extends BaseTest
{
    /**
     * The default flag is cleared on every box in a single UPDATE.
     */
    public function testUnsetDefaultFlagClearsAllDefaults(): void
    {
        $connection = $this->createMockWithProps(AdapterInterface::class);
        $connection->expects($this->once())
            ->method('update')
            ->with(
                Box::TABLE_NAME,
                [Box::IS_DEFAULT => 0],
                [Box::IS_DEFAULT . ' = ?' => 1]
            );

        /** @var BoxResource $boxResource */
        $boxResource = $this->createProxy(
            BoxResource::class,
            [],
            ['getConnection' => $connection, 'getTable' => Box::TABLE_NAME]
        );

        $boxResource->unsetDefaultFlag();
    }
}
