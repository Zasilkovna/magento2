<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Log;

class ApiErrorFormatter
{
    /**
     * @param string[] $soapDetailErrors
     */
    public function format(string $message, array $soapDetailErrors): string
    {
        if ($soapDetailErrors === []) {
            return $message;
        }

        $details = implode(', ', $soapDetailErrors);
        if ($message === '') {
            return $details;
        }

        return "{$message} ({$details})";
    }
}
