<?php

declare(strict_types=1);

namespace Packetery\Checkout\Block\Adminhtml\PacketSettings;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    protected function _construct()
    {
        parent::_construct();

        $this->setId('packetery_packet_settings_tabs');
        $this->setDestElementId('packetery_packet_settings_content');
        $this->setTitle('');
    }
}
