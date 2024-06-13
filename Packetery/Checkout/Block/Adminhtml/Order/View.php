<?php

declare(strict_types=1);

namespace Packetery\Checkout\Block\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class View
{
    /**
     * Prepares Magento Layout.
     *
     * @param OrderView $subject
     */
    public function beforeSetLayout(OrderView $subject): void
    {
        $url = $subject->getUrl('packetery/order/packetSubmission', ['order_number' => $subject->getOrder()->getIncrementId()]);

        $subject->addButton(
            'packetery_submit_button',
            [
                'label' => __('Submit to Packeta'),
                'class' => 'submit',
                'id' => 'order-view-packetery-submit-button',
                'sort_order' => 80,
                'on_click' => 'setLocation(\'' . $url . '\')',
            ]
        );
    }
}
