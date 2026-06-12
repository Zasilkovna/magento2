<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Cron;

use Magento\Framework\App\DeploymentConfig;
use Packetery\Checkout\Cron\LogPurger;
use Packetery\Checkout\Model\LogRepository;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

#[AllowMockObjectsWithoutExpectations]
class LogPurgerTest extends BaseTest
{
    /**
     * @param int|string|null $configuredDays
     */
    #[DataProvider('activeRetentionDataProvider')]
    public function testPurgeDeletesOldRecordsWhenRetentionIsActive($configuredDays): void
    {
        $deploymentConfig = $this->createMockWithProps(DeploymentConfig::class);
        $deploymentConfig->method('get')
            ->with(LogPurger::CONFIG_PATH_RETENTION_DAYS)
            ->willReturn($configuredDays);

        $logRepository = $this->createMockWithProps(LogRepository::class);
        $logRepository->expects($this->once())
            ->method('deleteOlderThan')
            ->with($this->isString());

        (new LogPurger($deploymentConfig, $logRepository))->purgeOld();
    }

    /**
     * @return array<string, array{0: int|string|null}>
     */
    public static function activeRetentionDataProvider(): array
    {
        return [
            'unset falls back to default' => [null],
            'positive integer' => [7],
            'positive numeric string' => ['15'],
        ];
    }

    /**
     * @param int|string $configuredDays
     */
    #[DataProvider('disabledRetentionDataProvider')]
    public function testPurgeSkipsWhenRetentionIsDisabled($configuredDays): void
    {
        $deploymentConfig = $this->createMockWithProps(DeploymentConfig::class);
        $deploymentConfig->method('get')
            ->with(LogPurger::CONFIG_PATH_RETENTION_DAYS)
            ->willReturn($configuredDays);

        $logRepository = $this->createMockWithProps(LogRepository::class);
        $logRepository->expects($this->never())
            ->method('deleteOlderThan');

        (new LogPurger($deploymentConfig, $logRepository))->purgeOld();
    }

    /**
     * @return array<string, array{0: int|string}>
     */
    public static function disabledRetentionDataProvider(): array
    {
        return [
            'zero disables cleanup' => [0],
            'negative disables cleanup' => [-5],
            'zero string disables cleanup' => ['0'],
        ];
    }
}
