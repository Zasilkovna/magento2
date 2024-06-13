<?php

namespace Packetery\Checkout\Block\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class View
{
    public function beforeSetLayout(OrderView $subject): void
    {
        $url = $subject->getUrl('packetery/order/packetsubmission', ['order_id' => $subject->getOrderId()]);

        $subject->addButton(
            'packeta_submit',
            [
                'label' => __('Submit to Packeta'),
                'class' => 'submit',
                'id' => 'order-view-packeta-submit',
                'sort_order' => 80,
                'on_click' => 'setLocation(\'' . $url . '\')',
            ]
        );
    }
}
