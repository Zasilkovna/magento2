<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Model\Packet;

use Packetery\Checkout\Model\Carrier\Imp\Packetery\Config;
use Packetery\Checkout\Model\Packet\SubmitPreconditions;
use Packetery\Checkout\Model\ResourceModel\Packet\Collection;
use Packetery\Checkout\Model\ResourceModel\Packet\CollectionFactory;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

#[AllowMockObjectsWithoutExpectations]
class SubmitPreconditionsTest extends BaseTest
{
    /** @return array<string, array{?string, ?string, bool}> */
    public static function configProvider(): array
    {
        return [
            'api password and sender set' => ['apiPassword', 'sender', true],
            'api password missing' => [null, 'sender', false],
            'sender missing' => ['apiPassword', null, false],
            'both missing' => [null, null, false],
        ];
    }

    #[DataProvider('configProvider')]
    public function testHasRequiredConfig(?string $apiPassword, ?string $sender, bool $expected): void
    {
        $config = $this->createStub(Config::class);
        $config->method('getApiPassword')->willReturn($apiPassword);
        $config->method('getSender')->willReturn($sender);

        $this->assertSame($expected, $this->createPreconditions()->hasRequiredConfig($config));
    }

    public function testHasRequiredConfigIsFalseWhenCarrierNotConfigured(): void
    {
        $this->assertFalse($this->createPreconditions()->hasRequiredConfig(null));
    }

    /** @return array<string, array{int, bool}> */
    public static function alreadySubmittedProvider(): array
    {
        return [
            'packet exists' => [1, true],
            'no packet' => [0, false],
        ];
    }

    #[DataProvider('alreadySubmittedProvider')]
    public function testIsAlreadySubmitted(int $size, bool $expected): void
    {
        $collection = $this->createMockWithProps(Collection::class);
        $collection->method('getSize')->willReturn($size);

        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        $this->assertSame($expected, (new SubmitPreconditions($collectionFactory))->isAlreadySubmitted('000000001'));
    }

    private function createPreconditions(): SubmitPreconditions
    {
        return new SubmitPreconditions($this->createStub(CollectionFactory::class));
    }
}
