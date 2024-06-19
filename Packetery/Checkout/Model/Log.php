<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

class Log extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    public const CACHE_TAG = 'packetery_checkout_log';

    protected $_cacheTag = 'packetery_checkout_log';

    protected $_eventPrefix = 'packetery_checkout_log';

    protected function _construct(): void
    {
        $this->_init('Packetery\Checkout\Model\ResourceModel\Log');
    }

    /**
     * @return string[]
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
