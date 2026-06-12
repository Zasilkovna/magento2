<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Log;

class RequestSanitizer
{
    private const REDACTED = '***';

    /** @var string[] */
    private const SENSITIVE_KEY_NEEDLES = ['password', 'apipassword', 'secret', 'credential'];

    /**
     * @param array<string, scalar> $parameters
     * @return array<string, scalar>
     */
    public function redact(array $parameters): array
    {
        foreach ($parameters as $key => $value) {
            if ($this->isSensitiveKey($key)) {
                $parameters[$key] = self::REDACTED;
            }
        }

        return $parameters;
    }

    /**
     * @param array<string, scalar> $parameters
     */
    public function redactToJson(array $parameters): string
    {
        return (string) json_encode($this->redact($parameters), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalizedKey = str_replace(['_', '-'], '', strtolower($key));
        foreach (self::SENSITIVE_KEY_NEEDLES as $needle) {
            if (str_contains($normalizedKey, $needle)) {
                return true;
            }
        }

        return false;
    }
}
