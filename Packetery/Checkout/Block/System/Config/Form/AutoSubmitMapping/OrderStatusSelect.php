<?php

namespace Packetery\Checkout\Block\System\Config\Form\AutoSubmitMapping;

class OrderStatusSelect extends \Magento\Framework\View\Element\Html\Select
{
    /** @var \Magento\Sales\Model\Config\Source\Order\Status */
    private $orderStatusSource;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Sales\Model\Config\Source\Order\Status $orderStatusSource,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->orderStatusSource = $orderStatusSource;
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
            $this->setOptions($this->orderStatusSource->toOptionArray());
        }

        return parent::_toHtml();
    }
}
