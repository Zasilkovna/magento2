<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Model\Packet;

use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Packetery\Checkout\Model\Carrier\Facade;
use Packetery\Checkout\Model\Carrier\Imp\Packetery\Config;
use Packetery\Checkout\Model\Order;
use Packetery\Checkout\Model\OrderRepository;
use Packetery\Checkout\Model\Packet\BulkSubmitPlanner;
use Packetery\Checkout\Model\Packet\SubmitPreconditions;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class BulkSubmitPlannerTest extends BaseTest
{
    public function testPlanGroupsOrdersByExpectedOutcome(): void
    {
        $orderNumbersById = [
            1 => '0001',
            2 => '0002',
            3 => '0003',
            4 => '0004',
            5 => '0005',
        ];
        $storeByNumber = [
            '0001' => 1,
            '0002' => 2,
            '0003' => 2,
            '0004' => 3,
            '0005' => 2,
        ];
        $configuredConfig = $this->createStub(Config::class);

        $planner = new BulkSubmitPlanner(
            $this->createOrderRepository($orderNumbersById),
            $this->createMagentoOrderFactory($storeByNumber),
            $this->createCarrierFacade([1], $configuredConfig),
            $this->createStoreManager([2 => 'Store Two', 3 => 'Store Three']),
            $this->createSubmitPreconditions(['0002'], $configuredConfig)
        );

        $plan = $planner->plan([1, 2, 3, 4, 5, 99]);

        $this->assertSame([1], $plan->getQueueableOrderIds());
        $this->assertSame(1, $plan->getAlreadySubmittedCount());
        $this->assertSame(3, $plan->getMissingConfigCount());
        $this->assertSame(['Store Two', 'Store Three'], $plan->getMissingConfigStoreNames());
    }

    /**
     * @param array<int, string> $orderNumbersById
     */
    private function createOrderRepository(array $orderNumbersById): OrderRepository
    {
        $orderRepository = $this->createStub(OrderRepository::class);
        $orderRepository->method('findById')->willReturnCallback(
            function (int $orderId) use ($orderNumbersById): ?Order {
                if (!isset($orderNumbersById[$orderId])) {
                    return null;
                }

                $order = $this->createMockWithProps(Order::class);
                $order->method('getOrderNumber')->willReturn($orderNumbersById[$orderId]);

                return $order;
            }
        );

        return $orderRepository;
    }

    /**
     * @param array<string, int> $storeByNumber
     */
    private function createMagentoOrderFactory(array $storeByNumber): OrderFactory
    {
        $factory = $this->createStub(OrderFactory::class);
        $factory->method('create')->willReturnCallback(
            function () use ($storeByNumber): MagentoOrder {
                $captured = new \stdClass();
                $captured->number = '';

                $order = $this->createMockWithProps(MagentoOrder::class);
                $order->method('loadByIncrementId')->willReturnCallback(
                    function ($number) use ($captured, $order) {
                        $captured->number = (string) $number;

                        return $order;
                    }
                );
                $order->method('getId')->willReturnCallback(
                    fn() => isset($storeByNumber[$captured->number]) ? 5 : 0
                );
                $order->method('getStoreId')->willReturnCallback(
                    fn() => $storeByNumber[$captured->number] ?? 0
                );

                return $order;
            }
        );

        return $factory;
    }

    /**
     * @param int[] $configuredStoreIds
     */
    private function createCarrierFacade(array $configuredStoreIds, Config $configuredConfig): Facade
    {
        $facade = $this->createStub(Facade::class);
        $facade->method('getPacketeryCarrierConfig')->willReturnCallback(
            fn(int $storeId): ?Config => in_array($storeId, $configuredStoreIds, true) ? $configuredConfig : null
        );

        return $facade;
    }

    /**
     * @param array<int, string> $storeNamesById
     */
    private function createStoreManager(array $storeNamesById): StoreManagerInterface
    {
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturnCallback(
            function ($storeId) use ($storeNamesById): StoreInterface {
                $store = $this->createStub(StoreInterface::class);
                $store->method('getName')->willReturn($storeNamesById[(int) $storeId] ?? (string) $storeId);

                return $store;
            }
        );

        return $storeManager;
    }

    /**
     * @param string[] $submittedOrderNumbers
     */
    private function createSubmitPreconditions(array $submittedOrderNumbers, Config $configuredConfig): SubmitPreconditions
    {
        $preconditions = $this->createStub(SubmitPreconditions::class);
        $preconditions->method('isAlreadySubmitted')->willReturnCallback(
            fn(string $orderNumber): bool => in_array($orderNumber, $submittedOrderNumbers, true)
        );
        $preconditions->method('hasRequiredConfig')->willReturnCallback(
            fn(?Config $config): bool => $config === $configuredConfig
        );

        return $preconditions;
    }
}
