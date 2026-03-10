<?php

declare(strict_types=1);

namespace Packetery\Checkout\Block\System\Config\Form;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Phrase;

class SenderValidate extends Value
{
    /**
     * @return void
     * @throws ValidatorException
     */
    public function beforeSave(): void
    {
        $value = $this->getValue();
        if ($value === null || (is_string($value) && trim($value) === '')) {
            throw new ValidatorException(new Phrase(__('Sender must be filled.')));
        }
        $this->setValue(trim((string) $value));
        parent::beforeSave();
    }
}
