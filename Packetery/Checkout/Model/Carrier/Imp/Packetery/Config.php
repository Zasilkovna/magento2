<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\Packetery;

class Config extends \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
{
    public function __construct(\Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $carrier)
    {
        parent::__construct($carrier);
    }

    /**
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return ($this->carrier->getConfigData('api_key') ?: null);
    }

    /**
     * @return string[]
     */
    public function getCodMethods(): array
    {
        $value = $this->carrier->getConfigData('cod_methods');
        return (is_string($value) ? explode(',', $value) : []);
    }
}
