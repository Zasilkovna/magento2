<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Packetery\Checkout\Model\Box;
use Packetery\Checkout\Model\BoxRepository;
use Packetery\Checkout\Model\ResourceModel\Box as BoxResource;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class BoxRepositoryTest extends BaseTest
{
    private const BOX_ID = 5;

    /**
     * When row is set as default, other defaults will be unset
     *
     * @throws LocalizedException
     */
    public function testSetAsDefaultUnsetsOthers(): void
    {
        $box = $this->createMockWithProps(Box::class);
        $box->method('getId')
            ->willReturn(self::BOX_ID);
        $box->expects($this->once())
            ->method('setIsDefault')
            ->with(true);

        $connection = $this->createMockWithProps(AdapterInterface::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())
            ->method('commit');
        $connection->expects($this->never())
            ->method('rollBack');

        $boxResource = $this->createMockWithProps(BoxResource::class);
        $boxResource->method('getConnection')
            ->willReturn($connection);
        $boxResource->expects($this->once())
            ->method('unsetDefaultFlag');
        $boxResource
            ->expects($this->once())
            ->method('save')
            ->with($box);

        /** @var BoxRepository $repository */
        $repository = $this->createProxy(
            BoxRepository::class,
            ['boxResource' => $boxResource, 'instances' => []],
            ['getById' => $box]
        );

        $this->assertSame($box, $repository->setAsDefault(self::BOX_ID));

        // The bulk UPDATE changed the DB, so boxes loaded earlier no longer match it
        // check new default box in $instances
        $instances = (new \ReflectionProperty(BoxRepository::class, 'instances'))->getValue($repository);
        $this->assertSame([self::BOX_ID => $box], $instances);
    }

    /**
     * Soft delete adds the deleted flag and persists via save(), but the row is never removed.
     */
    public function testSoftDelete(): void
    {
        $box = $this->createMockWithProps(Box::class);

        $box->method('getId')
            ->willReturn(self::BOX_ID);
        $box->expects($this->once())
            ->method('setDeleted')
            ->with(true);

        $boxResource = $this->createMockWithProps(BoxResource::class);
        $boxResource->expects($this->once())
            ->method('save')
            ->with($box);
        $boxResource->expects($this->never())
            ->method('delete');

        /** @var BoxRepository $repository */
        $repository = $this->createProxy(
            BoxRepository::class,
            ['boxResource' => $boxResource, 'instances' => []],
            ['getById' => $box]
        );

        $this->assertTrue($repository->deleteById(self::BOX_ID));
    }

    /**
     * Forces save() to fail mid-transaction to prove the bulk default reset is rolled back and the error wrapped.
     */
    public function testSetAsDefaultRollsBackOnFailure(): void
    {
        $box = $this->createMockWithProps(Box::class);
        $box->method('getId')
            ->willReturn(self::BOX_ID);
        $box->method('getDeleted')
            ->willReturn(false);

        $connection = $this->createMockWithProps(AdapterInterface::class);
        $connection->expects($this->once())
            ->method('beginTransaction');
        $connection->expects($this->never())
            ->method('commit');
        $connection->expects($this->once())
            ->method('rollBack');

        $boxResource = $this->createMockWithProps(BoxResource::class);
        $boxResource->method('getConnection')
            ->willReturn($connection);
        $boxResource->expects($this->once())
            ->method('unsetDefaultFlag');
        $boxResource->method('save')
            ->willThrowException(new \Exception('save failed'));

        /** @var BoxRepository $repository */
        $repository = $this->createProxy(
            BoxRepository::class,
            ['boxResource' => $boxResource, 'instances' => []],
            ['getById' => $box]
        );

        $this->expectException(CouldNotSaveException::class);

        $repository->setAsDefault(self::BOX_ID);
    }

    /**
     * Deleted box cannot be set as default
     */
    public function testSetAsDefaultRejectsDeletedBox(): void
    {
        $box = $this->createMockWithProps(Box::class);
        $box->method('getDeleted')
            ->willReturn(true);

        $boxResource = $this->createMockWithProps(BoxResource::class);
        $boxResource->expects($this->never())
            ->method('getConnection');
        $boxResource->expects($this->never())
            ->method('save');

        /** @var BoxRepository $repository */
        $repository = $this->createProxy(
            BoxRepository::class,
            ['boxResource' => $boxResource, 'instances' => []],
            ['getById' => $box]
        );

        $this->expectException(LocalizedException::class);

        $repository->setAsDefault(self::BOX_ID);
    }

    /**
     * A newly created box becomes the default automatically when none exists yet.
     */
    public function testSaveAssignsFirstBoxAsDefault(): void
    {
        $box = $this->createMockWithProps(Box::class);
        $box->method('getId')
            ->willReturn(null);

        $box->expects($this->once())
            ->method('setIsDefault')
            ->with(true);

        $boxResource = $this->mockBoxResourceWithDefaultLookup(false);
        $boxResource->expects($this->once())
            ->method('save')
            ->with($box);

        /** @var BoxRepository $repository */
        $repository = $this->createProxy(
            BoxRepository::class,
            ['boxResource' => $boxResource, 'instances' => []]
        );

        $this->assertSame($box, $repository->save($box));
    }

    /**
     * A newly created box is not auto-defaulted when a default box already exists.
     */
    public function testSaveDoesNotReassignDefaultWhenOneExists(): void
    {
        $box = $this->createMockWithProps(Box::class);
        $box->method('getId')
            ->willReturn(null);

        $box->expects($this->never())
            ->method('setIsDefault');

        $boxResource = $this->mockBoxResourceWithDefaultLookup(true);
        $boxResource->expects($this->once())
            ->method('save')
            ->with($box);

        /** @var BoxRepository $repository */
        $repository = $this->createProxy(
            BoxRepository::class,
            ['boxResource' => $boxResource, 'instances' => []]
        );

        $this->assertSame($box, $repository->save($box));
    }

    /**
     * Builds a BoxResource mock whose connection answers the "is there a default box?" lookup.
     *
     * @return BoxResource&\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockBoxResourceWithDefaultLookup(bool $defaultExists)
    {
        $select = $this->createMockWithProps(Select::class);
        $select->method('from')
            ->willReturnSelf();
        $select->method('where')
            ->willReturnSelf();
        $select->method('limit')
            ->willReturnSelf();

        $connection = $this->createMockWithProps(AdapterInterface::class);
        $connection->method('select')
            ->willReturn($select);
        $connection->method('fetchOne')
            ->willReturn($defaultExists ? '1' : false);

        $boxResource = $this->createMockWithProps(BoxResource::class);
        $boxResource->method('getConnection')
            ->willReturn($connection);
        $boxResource->method('getTable')
            ->willReturn(Box::TABLE_NAME);

        return $boxResource;
    }

    /**
     * Updating an existing box never touches the default flag (auto-default is create-only).
     */
    public function testSaveDoesNotAssignDefaultOnUpdate(): void
    {
        $box = $this->createMockWithProps(Box::class);
        $box->method('getId')
            ->willReturn(self::BOX_ID);

        $box->expects($this->never())
            ->method('setIsDefault');

        $boxResource = $this->createMockWithProps(BoxResource::class);
        $boxResource->expects($this->once())
            ->method('save')
            ->with($box);

        $boxResource->expects($this->never())
            ->method('getConnection');

        /** @var BoxRepository $repository */
        $repository = $this->createProxy(
            BoxRepository::class,
            ['boxResource' => $boxResource, 'instances' => []]
        );

        $this->assertSame($box, $repository->save($box));
    }
}
