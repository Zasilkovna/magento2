<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test;

use PHPUnit\Framework\MockObject\MockObject;

abstract class BaseTest extends \PHPUnit\Framework\TestCase
{
    protected const SHIPPING_PICKUP_POINT = 'packetery_pickupPointDelivery';
    protected const SHIPPING_ADDRESS_DELIVERY = 'packeteryPacketaDynamic_106-directAddressDelivery';
    protected const SHIPPING_NON_PACKETERY = 'dummy_dummy';

    /**
     * @param $object
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws \ReflectionException
     */
    protected function invokeMethod($object, string $method, array $args = [])
    {
        $rc = new \ReflectionClass($object);
        $method = $rc->getMethod($method);
        return $method->invokeArgs($object, $args);
    }

    /**
     * @param array $callback
     * @param array $args
     * @return mixed
     * @throws \ReflectionException
     */
    protected function invokeCallback(array $callback, array $args = [])
    {
        $object = array_shift($callback);
        $method = array_shift($callback);
        return $this->invokeMethod($object, $method, $args);
    }

    /**
     * @param $className
     * @param $mock
     * @param string $property
     * @param $value
     * @throws \ReflectionException
     */
    protected function mockPrivateProperty($className, $mock, string $property, $value): void
    {
        $rc = new \ReflectionClass($className);

        $property = $rc->getProperty($property);
        $property->setValue($mock, $value);
    }

    /**
     * @param string $exceptionClass
     * @param callable $callback
     * @throws \PHPUnit\Exception
     */
    protected function assertException(string $exceptionClass, callable $callback): void
    {
        $e = null;
        try {
            call_user_func_array($callback, []);
        } catch (\PHPUnit\Exception $e) {
            throw $e;
        } catch (\Throwable $e) {
        }

        $actualExceptionClass = (is_object($e) ? get_class($e) : null);
        $this->assertEquals($exceptionClass, $actualExceptionClass, ($e instanceof \Throwable ? $e->getMessage() : ''));
    }

    /**
     * Returns a mock object for the specified class.
     *
     * @psalm-template RealInstanceType of object
     * @psalm-param class-string<RealInstanceType> $originalClassName
     * @psalm-return MockObject&RealInstanceType
     */
    protected function createMockWithProps($originalClassName, array $props = []): MockObject
    {
        $mock = $this->getMockBuilder($originalClassName)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();

        foreach ($props as $prop => $propValue) {
            $this->mockPrivateProperty($originalClassName, $mock, $prop, $propValue);
        }

        return $mock;
    }

    /**
     * @psalm-template RealInstanceType of object
     * @psalm-param class-string<RealInstanceType> $originalClassName
     * @psalm-return MockObject&RealInstanceType
     */
    protected function createProxy($originalClassName, $properties = [], $existingMethods = [])
    {
        $service = $this->createPartialMock($originalClassName, array_keys($existingMethods));

        foreach ($existingMethods as $existingMethod => $value) {
            $service->method($existingMethod)->willReturn($value);
        }

        foreach ($properties as $argName => $customArg) {
            $this->mockPrivateProperty(
                $originalClassName, $service, $argName, $customArg
            );
        }

        return $service;
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function prepareOrderMock(
        ?string $shippingMethod = null,
        ?string $shippingCountryId = null,
        int $storeId = 1
    ): \Magento\Sales\Model\Order&MockObject {
        $order = $this->createMock(\Magento\Sales\Model\Order::class);

        $order->method('getShippingMethod')
            ->willReturn($shippingMethod);
        $order->method('getStoreId')
            ->willReturn($storeId);

        $shippingAddress = null;
        if ($shippingCountryId !== null) {
            $shippingAddress = $this->createStub(\Magento\Sales\Api\Data\OrderAddressInterface::class);
            $shippingAddress->method('getCountryId')
                ->willReturn($shippingCountryId);
        }

        $order->method('getShippingAddress')
            ->willReturn($shippingAddress);

        return $order;
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function prepareBoxStub(
        int $id = 1,
        string $name = 'M',
        ?float $depth = null,
        ?float $width = null,
        ?float $height = null,
        bool $deleted = false
    ): \Packetery\Checkout\Model\Box {
        $box = $this->createStub(\Packetery\Checkout\Model\Box::class);

        $box->method('getId')->willReturn($id);
        $box->method('getName')->willReturn($name);
        $box->method('getDepth')->willReturn($depth);
        $box->method('getWidth')->willReturn($width);
        $box->method('getHeight')->willReturn($height);
        $box->method('getDeleted')->willReturn($deleted);

        return $box;
    }
}
