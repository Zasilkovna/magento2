<?php

declare(strict_types=1);

namespace Packetery\Checkout\Block\Adminhtml\Log;

class Detail extends \Magento\Backend\Block\Template
{
    public function getCallParameters(): string
    {
        $params = $this->findLog()?->getParams() ?? '';
        if ($params === '') {
            return '';
        }

        $decoded = json_decode($params, true);
        if (!is_array($decoded)) {
            return $params;
        }

        return (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function getApiResponse(): string
    {
        return $this->findLog()?->getResponse() ?? '';
    }

    private function findLog(): ?\Packetery\Checkout\Model\Log
    {
        $log = $this->getData('log');

        return $log instanceof \Packetery\Checkout\Model\Log ? $log : null;
    }
}
