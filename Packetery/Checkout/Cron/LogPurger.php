<?php

declare(strict_types=1);

namespace Packetery\Checkout\Cron;

class LogPurger
{
    /**
     * Deploy config path (config.php / env.php), e.g. ['packetery' => ['log' => ['retention_days' => 30]]].
     * It is a devops operational lever, intentionally not an admin setting.
     */
    public const CONFIG_PATH_RETENTION_DAYS = 'packetery/log/retention_days';

    public const DEFAULT_RETENTION_DAYS = 30;

    public function __construct(
        private readonly \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        private readonly \Packetery\Checkout\Model\LogRepository $logRepository
    ) {
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    public function purgeOld(): void
    {
        $retentionDays = $this->resolveRetentionDays();
        if ($retentionDays <= 0) {
            return;
        }

        $threshold = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->sub(new \DateInterval('P' . $retentionDays . 'D'))
            ->format('Y-m-d H:i:s');

        $this->logRepository->deleteOlderThan($threshold);
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    private function resolveRetentionDays(): int
    {
        $configured = $this->deploymentConfig->get(self::CONFIG_PATH_RETENTION_DAYS);
        if ($configured === null) {
            return self::DEFAULT_RETENTION_DAYS;
        }

        return (int) $configured;
    }
}
