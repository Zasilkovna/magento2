<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Ui\Component\Order;

use Packetery\Checkout\Model\Box;
use Packetery\Checkout\Model\Dimensions\Converter;
use Packetery\Checkout\Model\ResourceModel\Box\Collection;
use Packetery\Checkout\Model\ResourceModel\Box\CollectionFactory;
use Packetery\Checkout\Ui\Component\Order\BoxSelect;
use PHPUnit\Framework\TestCase;

class BoxSelectTest extends TestCase
{
    /**
     * Test return options array.
     * Soft-deleted box stays in the list but is flagged non-selectable / disabled
     *
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testToOptionArrayFlagsDeletedBoxesAsDisabled(): void
    {
        $active = $this->createBox(1, 'M', 30.0, 20.0, 10.0, false);
        $deleted = $this->createBox(2, 'XS', 30.0, 20.0, 10.0, true);

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
        $box = $this->createStub(Box::class);
        $box->method('getId')
            ->willReturn(7);
        $box->method('getName')
            ->willReturn('Partial');
        $box->method('getDepth')
            ->willReturn(null);
        $box->method('getWidth')
            ->willReturn(20.0);
        $box->method('getHeight')
            ->willReturn(10.0);
        $box->method('getDeleted')
            ->willReturn(false);

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

    private function createBox(
        int $id,
        string $name,
        float $depth,
        float $width,
        float $height,
        bool $deleted
    ): Box {
        $box = $this->createStub(Box::class);

        $box->method('getId')
            ->willReturn($id);
        $box->method('getName')
            ->willReturn($name);
        $box->method('getDepth')
            ->willReturn($depth);
        $box->method('getWidth')
            ->willReturn($width);
        $box->method('getHeight')
            ->willReturn($height);
        $box->method('getDeleted')
            ->willReturn($deleted);

        return $box;
    }
}
