<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Model\Log;

use Magento\Framework\Exception\CouldNotSaveException;
use Packetery\Checkout\Model\Log;
use Packetery\Checkout\Model\Log\LogWriter;
use Packetery\Checkout\Model\Log\RequestSanitizer;
use Packetery\Checkout\Model\LogFactory;
use Packetery\Checkout\Model\LogRepository;
use Packetery\Checkout\Test\BaseTest;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class LogWriterTest extends BaseTest
{
    public function testLogSuccessBuildsSuccessRecord(): void
    {
        $log = $this->createMockWithProps(Log::class);
        $log->expects($this->once())->method('setAction')->with(Log::ACTION_SUBMIT);
        $log->expects($this->once())->method('setStatus')->with(Log::STATUS_SUCCESS);
        $log->expects($this->once())->method('setOrderNumber')->with('100000123');
        $log->expects($this->once())->method('setCreatedAt')->with($this->isString());
        $log->expects($this->once())->method('setNote')->with('Submitted packet Z1.');
        $log->expects($this->never())->method('setParams');
        $log->expects($this->never())->method('setResponse');

        $logRepository = $this->createMockWithProps(LogRepository::class);
        $logRepository->expects($this->once())->method('save')->with($log);

        $logger = $this->createMockWithProps(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $writer = new LogWriter($this->createFactory($log), $logRepository, new RequestSanitizer(), $logger);
        $writer->logSuccess(Log::ACTION_SUBMIT, '100000123', new \Magento\Framework\Phrase('Submitted packet Z1.'));
    }

    public function testLogErrorBuildsErrorRecordWithRedactedParams(): void
    {
        $log = $this->createMockWithProps(Log::class);
        $log->expects($this->once())->method('setAction')->with(Log::ACTION_SUBMIT);
        $log->expects($this->once())->method('setStatus')->with(Log::STATUS_ERROR);
        $log->expects($this->once())->method('setOrderNumber')->with('100000123');
        $log->expects($this->once())->method('setCreatedAt')->with($this->isString());
        $log->expects($this->once())->method('setResponse')->with('Submission failed.');
        $log->expects($this->never())->method('setNote');
        $log->expects($this->once())
            ->method('setParams')
            ->with($this->callback(static function (string $json): bool {
                return !str_contains($json, 'topsecret') && str_contains($json, '***');
            }));

        $logRepository = $this->createMockWithProps(LogRepository::class);
        $logRepository->expects($this->once())->method('save')->with($log);

        $logger = $this->createMockWithProps(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $writer = new LogWriter($this->createFactory($log), $logRepository, new RequestSanitizer(), $logger);
        $writer->logError(
            Log::ACTION_SUBMIT,
            '100000123',
            ['number' => 'Z1', 'api_password' => 'topsecret'],
            'Submission failed.'
        );
    }

    public function testPersistenceFailureIsSwallowedAndLogged(): void
    {
        $log = $this->createMockWithProps(Log::class);

        $logRepository = $this->createMockWithProps(LogRepository::class);
        $logRepository->method('save')->willThrowException(new CouldNotSaveException(__('boom')));

        $logger = $this->createMockWithProps(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $writer = new LogWriter($this->createFactory($log), $logRepository, new RequestSanitizer(), $logger);
        $writer->logSuccess(Log::ACTION_SUBMIT, '100000123', new \Magento\Framework\Phrase('Submitted packet Z1.'));
    }

    private function createFactory(Log $log): LogFactory
    {
        $factory = $this->createStub(LogFactory::class);
        $factory->method('create')->willReturn($log);

        return $factory;
    }
}
