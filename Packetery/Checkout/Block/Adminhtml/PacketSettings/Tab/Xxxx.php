<?php

declare(strict_types=1);

namespace Packetery\Checkout\Block\Adminhtml\PacketSettings\Tab;

use Magento\Backend\Block\Widget\Tab\TabInterface;

class Xxxx extends \Magento\Backend\Block\Template implements TabInterface
{
    public function getTabLabel(): \Magento\Framework\Phrase
    {
        return __('XXXX');
    }

    public function getTabTitle(): \Magento\Framework\Phrase
    {
        return __('XXXX');
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
