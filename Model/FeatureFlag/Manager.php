<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\FeatureFlag;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Flag;
use Magento\Store\Model\ScopeInterface;

class Manager
{
    /** @var \GuzzleHttp\Client */
    private $client;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /** @var \Packetery\Checkout\Model\FeatureFlag\Factory */
    private $flagFactory;

    /** @var \Magento\Framework\Flag\FlagResource */
    private $flagResource;

    /** @var \Magento\AdminNotification\Model\Inbox */
    private $adminNotificationInbox;

    /** @var array<class-string, Flag> */
    private $cachedFlags;

    /**
     * @param \GuzzleHttp\Client $client
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Packetery\Checkout\Model\FeatureFlag\Factory $flagFactory
     * @param \Magento\Framework\Flag\FlagResource $flagResource
     * @param \Magento\AdminNotification\Model\Inbox $adminNotificationInbox
     */
    public function __construct(
        \GuzzleHttp\Client $client,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Packetery\Checkout\Model\FeatureFlag\Factory $flagFactory,
        \Magento\Framework\Flag\FlagResource $flagResource,
        \Magento\AdminNotification\Model\Inbox $adminNotificationInbox
    ) {
        $this->client = $client;
        $this->scopeConfig = $scopeConfig;
        $this->flagFactory = $flagFactory;
        $this->flagResource = $flagResource;
        $this->adminNotificationInbox = $adminNotificationInbox;
    }

    private function getFlag(string $className): Flag {
        if (!isset($this->cachedFlags[$className])) {
            $this->cachedFlags[$className] = $this->flagFactory->createLoaded(SplitFlag::class);
        }

        return $this->cachedFlags[$className];
    }

    public function isSplitActive(): bool
    {
        $flag = $this->getFlag(SplitFlag::class);
        if ($flag->isActive()) {
            return true;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if (
            $flag->getLastFetchTime() !== null &&
            $flag->getLastFetchTime() > $now->modify('- 1 day')
        ) {
            return false;
        }

        $apiKey = $this->scopeConfig->getValue(
            'carriers/packetery/api_key',
            ScopeInterface::SCOPE_STORE,
        );

        if (empty($apiKey)) {
            return false;
        }

        try {
            $response = $this->client->get(
                sprintf('https://pes-features-test.packeta-com.codenow.com/v1/magento?api_key=%s', $apiKey),
                [
                    'timeout' => 5, // seconds
                ]
            );
        } catch (GuzzleException $clientException) {
            return false;
        } finally {
            $flag->setLastFetchTime($now);
            $this->flagResource->save($flag);
        }

        if ($response->getStatusCode() > 200) {
            return false;
        }

        $data = json_decode((string)$response->getBody(), true);

        if (empty($data) || $data['status'] !== 'OK') {
            return false;
        }

        $isEnabled = (bool)$data['features']['split'];

        if ($isEnabled) {
            $flag->setActive();
            $this->flagResource->save($flag);
            $this->adminNotificationInbox->addNotice(
                __('New feature was enabled'),
                __("We've just turned on new options for setting up your Packeta pickup points. You can now choose a different price for a Z-Box or a pickup point in the pricing rules settings. More information can be found in the plugin documentation.",),
                'https://github.com/Zasilkovna/magento2'
            );
        }

        return $flag->isActive();
    }
}
