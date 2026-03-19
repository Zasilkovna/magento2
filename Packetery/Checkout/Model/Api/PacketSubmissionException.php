<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Api;

class PacketSubmissionException extends \Exception
{
    /** @var string[] */
    private array $soapDetailErrors;

    /**
     * @param string[] $soapDetailErrors
     */
    public function __construct(string $message, array $soapDetailErrors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->soapDetailErrors = $soapDetailErrors;
    }

    /**
     * @return string[]
     */
    public function getSoapDetailErrors(): array
    {
        return $this->soapDetailErrors;
    }
}
