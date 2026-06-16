<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Model\Weight;

use Magento\Catalog\Model\Product;
use Packetery\Checkout\Model\Weight\Calculator;
use Packetery\Checkout\Model\Weight\Item;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class CalculatorTest extends BaseTest
{
    public function testSingleItem(): void
    {
        $items = [$this->simpleItem(1, 2.5, 1)];

        $this->assertSame(2.5, (new Calculator())->getItemsWeight($items));
    }

    public function testMultipleUnitsOfOneProduct(): void
    {
        $items = [$this->simpleItem(1, 2.0, 3)];

        $this->assertSame(6.0, (new Calculator())->getItemsWeight($items));
    }

    public function testMultipleProducts(): void
    {
        $items = [
            $this->simpleItem(1, 1.0, 1),
            $this->simpleItem(2, 0.5, 2),
        ];

        $this->assertSame(2.0, (new Calculator())->getItemsWeight($items));
    }

    /**
     * A configurable's weight is the weight of its ordered simple child
     * (times the child's own quantity); the wrapper line itself adds nothing
     */
    public function testConfigurableUsesChildWeightNotWrapper(): void
    {
        $configurable = $this->item(
            'configurable',
            $this->product(10, 0.0, false),
            1,
            [$this->simpleItem(11, 1.5, 2)]
        );

        $this->assertSame(3.0, (new Calculator())->getItemsWeight([$configurable]));
    }

    /**
     * Virtual products carry no weight, so a virtual simple item adds nothing to the total
     */
    public function testVirtualProductWeighsZero(): void
    {
        $items = [
            $this->simpleItem(1, 2.5, 1),
            $this->item('simple', $this->product(2, 9.9, true), 4),
        ];

        $this->assertSame(2.5, (new Calculator())->getItemsWeight($items));
    }

    /**
     * @param Item[] $children
     */
    private function item(
        string $productType,
        Product $product,
        float $quantity,
        array $children = []
    ): Item {
        return new Item(
            [
                'product_type' => $productType,
                'product' => $product,
                'qty' => $quantity,
                'children' => $children,
            ]
        );
    }

    private function simpleItem(
        int $productId,
        float $weight,
        float $quantity
    ): Item
    {
        return $this->item(
            'simple',
            $this->product($productId, $weight, false),
            $quantity
        );
    }

    private function product(int $id, float $weight, bool $isVirtual): Product
    {
        $product = $this->createStub(Product::class);
        $product->method('getId')
            ->willReturn($id);
        $product->method('getWeight')
            ->willReturn($weight);
        $product->method('isVirtual')
            ->willReturn($isVirtual);

        return $product;
    }
}
