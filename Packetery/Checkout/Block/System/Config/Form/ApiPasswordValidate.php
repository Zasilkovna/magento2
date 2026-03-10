<?php

declare(strict_types=1);

namespace Packetery\Checkout\Block\System\Config\Form;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Phrase;

class ApiPasswordValidate extends Value
{
    private const API_PASSWORD_LENGTH = 32;

    /**
     * @return void
     * @throws ValidatorException
     */
    public function beforeSave(): void
    {
        $value = $this->getValue();
        if ($value === null || (is_string($value) && trim($value) === '')) {
            throw new ValidatorException(new Phrase(__('API password must be filled.')));
        }
        if (strlen($value) !== self::API_PASSWORD_LENGTH) {
            $message = __("The API password length must have %1 characters!", [self::API_PASSWORD_LENGTH]);
            throw new ValidatorException(new Phrase($message));
        }
        parent::beforeSave();
    }
}
