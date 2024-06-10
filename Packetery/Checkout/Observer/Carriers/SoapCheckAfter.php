<?php
declare(strict_types=1);

namespace Packetery\Checkout\Observer\Carriers;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

class SoapCheckAfter implements ObserverInterface
{
    /**
     * @var ManagerInterface
     */
    protected ManagerInterface $messageManager;

    /**
     * @param ManagerInterface $messageManager
     */
    public function __construct(ManagerInterface $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    /**
     * Observer triggers after saving store configuration.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        if (!extension_loaded('soap')) {
            $this->messageManager->addErrorMessage(__(
                'This plugin requires an active SOAP library for proper operation. Contact your server administrator.'
            ));
        }
    }
}
