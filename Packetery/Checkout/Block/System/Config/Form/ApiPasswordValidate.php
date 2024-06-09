<?php

namespace Packetery\Checkout\Block\System\Config\Form;

class ApiPasswordValidate extends \Magento\Framework\App\Config\Value
{
    private const API_PASSWORD_LENGTH = 32;

    /**
     * @return void
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function beforeSave() {

        $apiPassword = $this->getValue();

        if(strlen($apiPassword) !== self::API_PASSWORD_LENGTH) {

            $message = __("The API password length must have %1 characters!", self::API_PASSWORD_LENGTH);
            throw new \Magento\Framework\Exception\ValidatorException($message);
        }

        parent::beforeSave();
    }
}
