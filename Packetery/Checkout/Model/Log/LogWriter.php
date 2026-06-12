<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Log;

class LogWriter
{
    public function __construct(
        private readonly \Packetery\Checkout\Model\LogFactory $logFactory,
        private readonly \Packetery\Checkout\Model\LogRepository $logRepository,
        private readonly \Packetery\Checkout\Model\Log\RequestSanitizer $requestSanitizer,
        private readonly \Psr\Log\LoggerInterface $logger
    ) {
    }

    public function logSuccess(string $action, ?string $orderNumber, \Magento\Framework\Phrase $note): void
    {
        $log = $this->createLog($action, \Packetery\Checkout\Model\Log::STATUS_SUCCESS, $orderNumber);
        $log->setNote((string) $note);
        $this->persist($log);
    }

    /**
     * @param array<string, scalar> $callParameters
     */
    public function logError(string $action, ?string $orderNumber, array $callParameters, \Magento\Framework\Phrase|string $apiResponse): void
    {
        $log = $this->createLog($action, \Packetery\Checkout\Model\Log::STATUS_ERROR, $orderNumber);
        $log->setParams($this->requestSanitizer->redactToJson($callParameters));
        $log->setResponse((string) $apiResponse);
        $this->persist($log);
    }

    private function persist(\Packetery\Checkout\Model\Log $log): void
    {
        try {
            $this->logRepository->save($log);
        } catch (\Throwable $exception) {
            $this->logger->error('Could not write Packeta API log record.', ['exception' => $exception]);
        }
    }

    private function createLog(string $action, string $status, ?string $orderNumber): \Packetery\Checkout\Model\Log
    {
        $log = $this->logFactory->create();
        $log->setAction($action);
        $log->setStatus($status);
        $log->setOrderNumber($orderNumber);
        $log->setCreatedAt((new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));

        return $log;
    }
}
