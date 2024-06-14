<?php

declare(strict_types=1);

namespace Packetery\Checkout\Block\Adminhtml\Packet;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class View
{
    public function beforeSetLayout(OrderView $subject): void
    {
        $url = $subject->getUrl('packetery/packet/add');

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
