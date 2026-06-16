<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Ui\Component\Order;

use Packetery\Checkout\Model\Dimensions\Converter;
use Packetery\Checkout\Model\ResourceModel\Box\Collection;
use Packetery\Checkout\Model\ResourceModel\Box\CollectionFactory;
use Packetery\Checkout\Test\BaseTest;
use Packetery\Checkout\Ui\Component\Order\BoxSelect;

class BoxSelectTest extends BaseTest
{
    /**
     * Soft-deleted box stays in the list but is flagged non-selectable / disabled
     */
    public function testToOptionArrayFlagsDeletedBoxesAsDisabled(): void
    {
        $active = $this->prepareBoxStub(
            1,
            'M',
            30.0,
            20.0,
            10.0
        );

        $deleted = $this->prepareBoxStub(
            2,
            'XS',
            30.0,
            20.0,
            10.0,
            true
        );

        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')
            ->willReturn(new \ArrayIterator([$active, $deleted]));

        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')
            ->willReturn($collection);

        $options = (new BoxSelect($collectionFactory, new Converter()))->toOptionArray();

        $this->assertSame(
            [
                [
                    'label' => 'M (30 × 20 × 10 cm)',
                    'value' => 1,
                    'disabled' => false,
                ],
                [
                    'label' => 'XS (30 × 20 × 10 cm)',
                    'value' => 2,
                    'disabled' => true,
                ],
            ],
            $options
        );
    }

    /**
     * A box with any dimension missing (only reachable via direct DB edit) falls back to the bare name,
     * so Converter::label()'s non-null floats are never reached
     */
    public function testToOptionArrayUsesBareNameWhenDimensionMissing(): void
    {
        $box = $this->prepareBoxStub(
            7,
            'Partial',
            null,
            20.0,
            10.0
        );

        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')
            ->willReturn(new \ArrayIterator([$box]));

        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')
            ->willReturn($collection);

        $options = (new BoxSelect($collectionFactory, new Converter()))->toOptionArray();

        $this->assertSame(
            [
                [
                    'label' => 'Partial',
                    'value' => 7,
                    'disabled' => false,
                ],
            ],
            $options
        );
    }

    public function testToOptionArrayReturnsEmptyForEmptyCollection(): void
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')
            ->willReturn(new \ArrayIterator([]));

        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')
            ->willReturn($collection);

        $options = (new BoxSelect($collectionFactory, new Converter()))->toOptionArray();

        $this->assertSame([], $options);
    }

    public function testToOptionArrayRendersZeroDimensions(): void
    {
        $box = $this->prepareBoxStub(
            9,
            'Z',
            0.0,
            0.0,
            0.0
        );

        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')
            ->willReturn(new \ArrayIterator([$box]));

        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')
            ->willReturn($collection);

        $options = (new BoxSelect($collectionFactory, new Converter()))->toOptionArray();

        $this->assertSame(
            [
                [
                    'label' => 'Z (0 × 0 × 0 cm)',
                    'value' => 9,
                    'disabled' => false,
                ],
            ],
            $options
        );
    }
}
