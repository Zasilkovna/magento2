<?php

namespace Packetery\Checkout\Block\System\Config\Form\AutoSubmitMapping;

class PaymentMethodSelect extends \Magento\Framework\View\Element\Html\Select
{
    /** @var \Packetery\Checkout\Model\Config\Source\PaymentMethod */
    private $paymentMethodSource;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Packetery\Checkout\Model\Config\Source\PaymentMethod $paymentMethodSource,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->paymentMethodSource = $paymentMethodSource;
    }

    public function setInputName(string $value): static
    {
        return $this->setName($value);
    }

    public function setInputId(string $value): static
    {
        return $this->setId($value);
    }

    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->paymentMethodSource->toOptionArray());
        }
        return parent::_toHtml();
    }
}
