<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\Packetery;

use Packetery\Checkout\Model\Carrier\Methods;
use Packetery\Checkout\Model\Label\LabelFormats;

class Config extends \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
{
    public const CONFIG_PATH_SENDER = 'carriers/packetery/sender';

    /**
     * @return string[]
     */
    public function getAllowedMethods(): array
    {
        return [Methods::PICKUP_POINT_DELIVERY];
    }

    /**
     * @return string|null
     */
    public function getApiPassword(): ?string
    {
        return ($this->getConfigData('api_password') ?: null);
    }

    /**
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return ($this->getConfigData('api_key') ?: null);
    }

    /**
     * @return string[]
     */
    public function getCodMethods(): array
    {
        $value = $this->getConfigData('cod_methods');
        return (is_string($value) ? explode(',', $value) : []);
    }

    /**
     * @return string|null
     */
    public function getSender(): ?string
    {
        return ($this->getConfigData('sender') ?: null);
    }

    protected function normalizeLabelFormatValue(string $value): string
    {
        return LabelFormats::normalizePacketaFormat($value);
    }
}
