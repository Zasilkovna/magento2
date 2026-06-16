<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Controller\Adminhtml\Order;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Sales\Model\OrderFactory;
use Packetery\Checkout\Controller\Adminhtml\Order\Save;
use Packetery\Checkout\Model\Order as PacketeryOrder;
use Packetery\Checkout\Model\Packet;
use Packetery\Checkout\Model\PacketRepository;
use Packetery\Checkout\Model\ResourceModel\Order\Collection;
use Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class SaveTest extends BaseTest
{
    private const ORDER_NUMBER = '000000001';

    /**
     * Happy path on a pickup-point order: the pickup-point fields persisted, numeric fields parsed,
     * age-verification checkbox stored as 1, the chosen box id cast to int, and the success message shown
     */
    public function testExecuteSavesPickupPointDetails(): void
    {
        $context = $this->makeContext(
            [
                'id' => '1',
                'value' => '100',
                'cod' => '',
                'weight' => '2.5',
                'adult_content' => '1',
                'box_id' => '7',
                'point_id' => '999',
                'point_name' => 'Praha 1',
                'is_carrier' => '0',
                'carrier_pickup_point' => '',
            ],
            null,
            self::SHIPPING_PICKUP_POINT
        );

        $context['collection']->expects($this->once())
            ->method('save');
        $context['messages']->expects($this->once())
            ->method('addSuccessMessage');
        $context['messages']->expects($this->never())
            ->method('addErrorMessage');

        $context['controller']->execute();

        $point = $this->payloadWithKey($context['payloads'], 'point_id');

        $this->assertSame('999', $point['point_id']);
        $this->assertSame('Praha 1', $point['point_name']);
        $this->assertFalse($point['is_carrier']);
        $this->assertNull($point['carrier_pickup_point']);
        $this->assertArrayNotHasKey('recipient_street', $point);

        $merged = $this->payloadWithKey($context['payloads'], 'adult_content');

        $this->assertSame(1, $merged['adult_content']);
        $this->assertSame(7, $merged['box_id']);
        $this->assertSame(100.0, $merged['value']);
        $this->assertSame(2.5, $merged['weight']);
        $this->assertArrayHasKey('cod', $merged);
        $this->assertNull($merged['cod']);
    }

    /**
     * Clearing the box ("-- Select a box --") stores null;
     * an unchecked age-verification box stores 0
     */
    public function testExecuteStoresClearedBoxAndUncheckedAdultContent(): void
    {
        $context = $this->makeContext(
            [
                'id' => '1',
                'value' => '0',
                'cod' => '',
                'weight' => '',
                'box_id' => '',
            ],
            null,
            self::SHIPPING_PICKUP_POINT
        );

        $context['collection']->expects($this->once())
            ->method('save');

        $context['controller']->execute();

        $merged = $this->payloadWithKey($context['payloads'], 'adult_content');
        $this->assertNull($merged['box_id']);
        $this->assertSame(0, $merged['adult_content']);
        $this->assertSame(0.0, $merged['value']);
        $this->assertNull($merged['weight']);
    }

    /**
     * The form blocks negative input client-side, so a negative number here is an altered request:
     * reject with the error message and persist nothing
     */
    public function testExecuteRejectsNegativeNumericValue(): void
    {
        $context = $this->makeContext(
            [
                'id' => '1',
                'value' => '100',
                'cod' => '',
                'weight' => '-5',
            ],
            null,
            self::SHIPPING_PICKUP_POINT
        );

        $context['collection']->expects($this->never())
            ->method('save');
        $context['messages']->expects($this->once())
            ->method('addErrorMessage');
        $context['messages']->expects($this->never())
            ->method('addSuccessMessage');

        $context['controller']->execute();
    }

    /**
     * The packet is the source of truth once submitted;
     * POST to the (hidden) form is a stale tab or an altered request and is rejected before any field is touched
     */
    public function testExecuteRejectsWhenPacketAlreadySubmitted(): void
    {
        $context = $this->makeContext(
            [
                'id' => '1',
                'value' => '100',
                'cod' => '',
                'weight' => '2.5',
            ],
            $this->createStub(Packet::class),
            self::SHIPPING_PICKUP_POINT
        );

        $context['collection']->expects($this->never())
            ->method('save');
        $context['messages']->expects($this->once())
            ->method('addErrorMessage');
        $context['messages']->expects($this->never())
            ->method('addSuccessMessage');

        $context['controller']->execute();
    }

    /**
     * On an address-delivery order the recipient address is persisted (not the pickup-point fields),
     * because the delivery type is taken from the order's real shipping method, not the posted flag
     */
    public function testExecuteSavesAddressDeliveryDetails(): void
    {
        $context = $this->makeContext(
            [
                'id' => '1',
                'value' => '100',
                'cod' => '',
                'weight' => '2.5',
                'recipient_street' => 'Václavské náměstí',
                'recipient_house_number' => '1',
                'recipient_city' => 'Praha',
                'recipient_zip' => '11000',
                'recipient_country_id' => 'CZ',
                'point_id' => '999',
                'point_name' => 'Praha 1',
            ],
            null,
            self::SHIPPING_ADDRESS_DELIVERY
        );

        $context['collection']->expects($this->once())
            ->method('save');
        $context['messages']->expects($this->once())
            ->method('addSuccessMessage');

        $context['controller']->execute();
        $delivery = $this->payloadWithKey($context['payloads'], 'recipient_street');

        $this->assertSame('Václavské náměstí', $delivery['recipient_street']);
        $this->assertSame('Praha', $delivery['recipient_city']);
        $this->assertSame('11000', $delivery['recipient_zip']);
        $this->assertSame('CZ', $delivery['recipient_country_id']);

        $this->assertArrayNotHasKey('point_id', $delivery);
        $this->assertArrayNotHasKey('point_name', $delivery);
    }

    /**
     * @param array<string, mixed> $post
     * @return array{
     *     controller: Save,
     *     collection: MockObject,
     *     messages: MockObject,
     *     payloads: \stdClass
     * }
     */
    private function makeContext(array $post, ?object $existingPacket, string $shippingMethod): array
    {
        $request = $this->createMock(Http::class);
        $request->method('isPost')
            ->willReturn(true);
        $request->method('getPostValue')
            ->willReturn(['general' => $post]);

        $item = $this->createStub(PacketeryOrder::class);
        $item->method('getId')
            ->willReturn(1);
        $item->method('getOrderNumber')
            ->willReturn(self::ORDER_NUMBER);

        $payloads = new \stdClass();
        $payloads->all = [];
        $collection = $this->createMock(Collection::class);
        $collection->method('getFirstItem')
            ->willReturn($item);
        $collection->method('setDataToAll')
            ->willReturnCallback(static function (array $data) use ($payloads): void {
                $payloads->all[] = $data;
            });

        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')
            ->willReturn($collection);

        $magentoOrder = $this->createMock(MagentoOrder::class);
        $magentoOrder->method('loadByIncrementId')
            ->willReturnSelf();
        $magentoOrder->method('getShippingMethod')
            ->willReturn($shippingMethod);
        $magentoOrder->method('getId')
            ->willReturn(1);

        $orderFactory = $this->createStub(OrderFactory::class);
        $orderFactory->method('create')
            ->willReturn($magentoOrder);

        $packetRepository = $this->createStub(PacketRepository::class);
        $packetRepository->method('findLatestByOrderNumber')
            ->willReturn($existingPacket);

        $messages = $this->createMock(ManagerInterface::class);

        $redirect = $this->createStub(Redirect::class);
        $redirect->method('setPath')
            ->willReturnSelf();
        $resultFactory = $this->createStub(ResultFactory::class);
        $resultFactory->method('create')
            ->willReturn($redirect);

        $localeFormat = $this->createStub(FormatInterface::class);
        $localeFormat->method('getNumber')
            ->willReturnCallback(static fn ($value): float => (float) $value);

        $controller = $this->createProxy(
            Save::class,
            [
                'orderCollectionFactory' => $collectionFactory,
                'orderFactory' => $orderFactory,
                'packetRepository' => $packetRepository,
                'logger' => $this->createStub(LoggerInterface::class),
                'localeFormat' => $localeFormat,
                '_request' => $request,
                'messageManager' => $messages,
                'resultFactory' => $resultFactory,
            ]
        );

        return [
            'controller' => $controller,
            'collection' => $collection,
            'messages' => $messages,
            'payloads' => $payloads,
        ];
    }

    /**
     * Returns the first captured setDataToAll payload carrying $key; execute() calls it twice
     * (delivery fields, then the numeric+box+adult merge)
     *
     * @return array<string, mixed>
     */
    private function payloadWithKey(\stdClass $payloads, string $key): array
    {
        foreach ($payloads->all as $payload) {
            if (array_key_exists($key, $payload)) {
                return $payload;
            }
        }

        $this->fail("No setDataToAll payload carrying '{$key}' was captured.");
    }
}
