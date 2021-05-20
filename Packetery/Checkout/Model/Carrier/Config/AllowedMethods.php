<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Config;

/**
 * Represents specific selected methods in any carrier config.
 */
class AllowedMethods
{
    /** @var string[] */
    private $allowedMethods;

    /**
     * AllowedMethods constructor.
     *
     * @param string[] $allowedMethods
     */
    public function __construct(array $allowedMethods)
    {
        $this->allowedMethods = $allowedMethods;
    }

    /**
     * @param string $method
     * @return bool
     */
    public function hasAllowed(string $method): bool
    {
        return empty($this->allowedMethods) || in_array($method, $this->allowedMethods);
    }

    /**
     * @return string[]
     */
    public function toArray(): array
    {
        return $this->allowedMethods;
    }
}
