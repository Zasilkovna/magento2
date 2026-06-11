<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Model;

use Packetery\Checkout\Model\OrderCurrencyResolver;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class OrderCurrencyResolverTest extends BaseTest
{
    public function testReturnsPacketeryCurrencyWhenSet(): void
    {
        $this->assertSame('EUR', $this->resolve('EUR', 'CZK'));
    }

    public function testFallsBackToMagentoWhenPacketeryCurrencyNull(): void
    {
        $this->assertSame('CZK', $this->resolve(null, 'CZK'));
    }

    public function testFallsBackToMagentoWhenPacketeryCurrencyEmpty(): void
    {
        $this->assertSame('CZK', $this->resolve('', 'CZK'));
    }

    public function testFallsBackToMagentoWhenNoPacketeryOrder(): void
    {
        $this->assertSame('CZK', $this->resolve(null, 'CZK', true));
    }

    public function testReturnsNullWhenNeitherResolves(): void
    {
        $this->assertNull($this->resolve(null, null));
    }

    public function testTreatsEmptyMagentoCurrencyAsNull(): void
    {
        $this->assertNull($this->resolve(null, ''));
    }

    public function testReturnsNullWhenBothOrdersMissing(): void
    {
        $this->assertNull($this->resolve(null, null, true, true));
    }

    private function resolve(
        ?string $packeteryCurrency,
        ?string $magentoCurrency,
        bool $packeteryOrderMissing = false,
        bool $magentoOrderMissing = false
    ): ?string {
        $packeteryOrder = null;
        if (!$packeteryOrderMissing) {
            $packeteryOrder = $this->createStub(\Packetery\Checkout\Model\Order::class);
            $packeteryOrder->method('getCurrency')->willReturn($packeteryCurrency);
        }

        $magentoOrder = null;
        if (!$magentoOrderMissing) {
            $magentoOrder = $this->createStub(\Magento\Sales\Model\Order::class);
            $magentoOrder->method('getOrderCurrencyCode')->willReturn($magentoCurrency);
        }

        return (new OrderCurrencyResolver())->resolve($packeteryOrder, $magentoOrder);
    }
}
