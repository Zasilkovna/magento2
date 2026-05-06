<?php

declare(strict_types=1);

namespace Packetery\Checkout\Block\Adminhtml\PacketSettings\Tab;

use Magento\Backend\Block\Widget\Tab\TabInterface;

class AutoSubmit extends \Magento\Backend\Block\Template implements TabInterface
{
    private const MAPPING_PATH = 'carriers/packetery/auto_submit_status_map';

    /** @var \Packetery\Checkout\Model\Config\Source\PaymentMethod */
    private $paymentMethodSource;

    /** @var \Magento\Sales\Model\Order\Config */
    private $orderConfig;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /** @var \Magento\Framework\Data\Form\FormKey */
    protected $formKey;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Packetery\Checkout\Model\Config\Source\PaymentMethod $paymentMethodSource,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Data\Form\FormKey $formKey,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->paymentMethodSource = $paymentMethodSource;
        $this->orderConfig = $orderConfig;
        $this->scopeConfig = $scopeConfig;
        $this->formKey = $formKey;
    }

    /** @return array<int, array{value: string, label: string}> */
    public function getPaymentMethods(): array
    {
        return array_values(array_filter(
            $this->paymentMethodSource->toOptionArray(),
            static fn($o) => $o['value'] !== ''
        ));
    }

    /** @return array<string, string> */
    public function getOrderStatuses(): array
    {
        return $this->orderConfig->getStatuses();
    }

    /** @return array<string, string> payment_method => order_status */
    public function getCurrentMapping(): array
    {
        $raw = $this->scopeConfig->getValue(self::MAPPING_PATH);
        if (empty($raw)) {
            return [];
        }
        $mapping = [];
        foreach (json_decode($raw, true) ?? [] as $row) {
            if (isset($row['payment_method'], $row['order_status'])) {
                $mapping[$row['payment_method']] = $row['order_status'];
            }
        }
        return $mapping;
    }

    public function getSaveUrl(): string
    {
        return $this->getUrl('packetery/autosubmit/save');
    }

    public function getFormKeyValue(): string
    {
        return $this->formKey->getFormKey();
    }

    public function getTabLabel(): \Magento\Framework\Phrase
    {
        return __('Automatické podání');
    }

    public function getTabTitle(): \Magento\Framework\Phrase
    {
        return __('Automatické podání');
    }

    public function canShowTab(): bool
    {
        return true;
    }

    public function isHidden(): bool
    {
        return false;
    }
}
