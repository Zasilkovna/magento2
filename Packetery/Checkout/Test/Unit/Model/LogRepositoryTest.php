<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Model;

use Packetery\Checkout\Model\Log;
use Packetery\Checkout\Model\LogFactory;
use Packetery\Checkout\Model\LogRepository;
use Packetery\Checkout\Model\ResourceModel\Log as LogResource;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class LogRepositoryTest extends BaseTest
{
    private const LOG_ID = 7;

    public function testFindByIdReturnsLoadedRecord(): void
    {
        $log = $this->createMockWithProps(Log::class);
        $log->method('getId')->willReturn(self::LOG_ID);

        $logResource = $this->createMockWithProps(LogResource::class);
        $logResource->expects($this->once())
            ->method('load')
            ->with($log, self::LOG_ID);

        $repository = new LogRepository($this->createFactory($log), $logResource);

        $this->assertSame($log, $repository->findById(self::LOG_ID));
    }

    public function testFindByIdReturnsNullWhenRecordMissing(): void
    {
        $log = $this->createMockWithProps(Log::class);
        $log->method('getId')->willReturn(null);

        $logResource = $this->createMockWithProps(LogResource::class);

        $repository = new LogRepository($this->createFactory($log), $logResource);

        $this->assertNull($repository->findById(self::LOG_ID));
    }

    public function testSaveReturnsPersistedRecord(): void
    {
        $log = $this->createMockWithProps(Log::class);

        $logResource = $this->createMockWithProps(LogResource::class);
        $logResource->expects($this->once())
            ->method('save')
            ->with($log);

        $repository = new LogRepository($this->createFactory($log), $logResource);

        $this->assertSame($log, $repository->save($log));
    }

    public function testDeleteOlderThanDelegatesToResource(): void
    {
        $logResource = $this->createMockWithProps(LogResource::class);
        $logResource->expects($this->once())
            ->method('deleteOlderThan')
            ->with('2026-05-09 00:00:00')
            ->willReturn(3);

        $repository = new LogRepository($this->createFactory($this->createMockWithProps(Log::class)), $logResource);

        $this->assertSame(3, $repository->deleteOlderThan('2026-05-09 00:00:00'));
    }

    private function createFactory(Log $log): LogFactory
    {
        $factory = $this->createStub(LogFactory::class);
        $factory->method('create')->willReturn($log);

        return $factory;
    }
}
