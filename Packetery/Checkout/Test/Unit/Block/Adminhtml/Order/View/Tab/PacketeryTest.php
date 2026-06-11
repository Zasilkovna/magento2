<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Block\Adminhtml\Order\View\Tab;

use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order;
use Packetery\Checkout\Block\Adminhtml\Order\View\Tab\Packetery;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

#[AllowMockObjectsWithoutExpectations]
class PacketeryTest extends BaseTest
{
    /**
     * Read-only age verification must be offered exactly where the edit form offers it:
     * own pickup-point delivery into a Packeta base country, never on an external carrier's
     * pickup point (which does not accept the adultContent attribute) and nowhere else.
     */
    #[DataProvider('adultContentEligibilityProvider')]
    public function testIsAdultContentEligibleMatchesEditFormCondition(
        bool $hasOrder,
        ?string $shippingMethod,
        ?string $countryId,
        bool $isCarrier,
        bool $expected
    ): void {
        $order = null;
        if ($hasOrder) {
            $order = $this->createMock(Order::class);
            $order->method('getShippingMethod')
                ->willReturn($shippingMethod);

            $address = null;
            if ($countryId !== null) {
                $address = $this->createMock(OrderAddressInterface::class);
                $address->method('getCountryId')
                    ->willReturn($countryId);
            }
            $order->method('getShippingAddress')
                ->willReturn($address);
        }

        $packeteryOrder = $this->createMock(\Packetery\Checkout\Model\Order::class);
        $packeteryOrder->method('isCarrier')
            ->willReturn($isCarrier);

        $block = $this->createProxy(Packetery::class, [
            'magentoOrder' => $order,
            'magentoOrderLoaded' => true,
            'packeteryOrder' => $packeteryOrder,
            'packeteryOrderLoaded' => true,
        ]);

        $this->assertSame($expected, $block->isAdultContentEligible());
    }

    /**
     * @return array<string, array{bool, string|null, string|null, bool, bool}>
     */
    public static function adultContentEligibilityProvider(): array
    {
        return [
            'own pickup-point + CZ → eligible' => [true, 'packetery_pickupPointDelivery', 'CZ', false, true],
            'own pickup-point + SK → eligible' => [true, 'packetery_pickupPointDelivery', 'SK', false, true],
            'carrier pickup-point + CZ → not eligible' => [true, 'packetery_pickupPointDelivery', 'CZ', true, false],
            'pickup-point + UA → not eligible (non-base country)' => [true, 'packetery_pickupPointDelivery', 'UA', false, false],
            'address delivery (HD) + CZ → not eligible' => [true, 'packeteryPacketaDynamic_106-directAddressDelivery', 'CZ', false, false],
            'non-packetery method → not eligible' => [true, 'flatrate_flatrate', 'CZ', false, false],
            'no shipping address → not eligible' => [true, 'packetery_pickupPointDelivery', null, false, false],
            'no order → not eligible' => [false, null, null, false, false],
        ];
    }
}
