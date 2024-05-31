<?php


namespace Packetery\Checkout\Block\System\Config\Form;


use Magento\Framework\Phrase;

class ApiPasswordValidate extends \Magento\Framework\App\Config\Value
{
    const API_PASSWORD_LENGTH = 32;

    /**
     * @return void
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function beforeSave() {

        $apiPassword = $this->getValue();

        if(strlen($apiPassword) !== self::API_PASSWORD_LENGTH) {

            $message = _(sprintf("The API password length must have %d characters!", self::API_PASSWORD_LENGTH));
            throw new \Magento\Framework\Exception\ValidatorException(new Phrase($message));
        }

        parent::beforeSave();
    }
}
