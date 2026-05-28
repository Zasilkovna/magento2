<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test;

use PHPUnit\Framework\MockObject\MockObject;

abstract class BaseTest extends \PHPUnit\Framework\TestCase
{

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
}
