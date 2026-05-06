<?php

namespace Packetery\Checkout\Block\System\Config\Form;

class AutoSubmitMapping extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /** @var \Packetery\Checkout\Block\System\Config\Form\AutoSubmitMapping\PaymentMethodSelect */
    private $paymentMethodRenderer;

    /** @var \Packetery\Checkout\Block\System\Config\Form\AutoSubmitMapping\OrderStatusSelect */
    private $orderStatusRenderer;

    protected function _prepareToRender(): void
    {
        $this->addColumn(
            'payment_method',
            [
                'label' => __('Payment Method'),
                'renderer' => $this->getPaymentMethodRenderer(),
            ]
        );

        $this->addColumn(
            'order_status',
            [
                'label' => __('Order Status'),
                'renderer' => $this->getOrderStatusRenderer(),
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    protected function _prepareArrayRow(\Magento\Framework\DataObject $row): void
    {
        $options = [];
        $paymentMethod = $row->getData('payment_method');
        if ($paymentMethod) {
            $options['option_' . $this->getPaymentMethodRenderer()->calcOptionHash($paymentMethod)] = 'selected="selected"';
        }

        $orderStatus = $row->getData('order_status');
        if ($orderStatus) {
            $options['option_' . $this->getOrderStatusRenderer()->calcOptionHash($orderStatus)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    {
            \Packetery\Checkout\Block\System\Config\Form\AutoSubmitMapping\PaymentMethodSelect::class,
            '',
            ['data' => ['is_render_to_js_template' => true]]
        );

        return $this->paymentMethodRenderer;
    }

    {
            \Packetery\Checkout\Block\System\Config\Form\AutoSubmitMapping\OrderStatusSelect::class,
            '',
            ['data' => ['is_render_to_js_template' => true]]
        );

        return $this->orderStatusRenderer;
    }
}
