<?php

declare(strict_types=1);

namespace Packetery\Checkout\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Packetery\Checkout\Model\Carrier\Imp\Packetery\Config;

class MigrateSenderConfig implements DataPatchInterface, PatchVersionInterface
{
    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var WriterInterface */
    private $configWriter;

    /** @var StoreManagerInterface */
    private $storeManager;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $configWriter,
        StoreManagerInterface $storeManager
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter = $configWriter;
        $this->storeManager = $storeManager;
    }

    public static function getDependencies(): array
    {
        return [BackfillRecipientCountryAndVendorGroups::class];
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getVersion(): string
    {
        return '2.5.0';
    }

    public function apply(): void
    {
        $defaultSender = null;
        foreach ($this->storeManager->getStores() as $store) {
            $group = $store->getGroup();
            if ($group === null) {
                continue;
            }
            $groupCode = $group->getCode();
            if ($groupCode === null || $groupCode === '') {
                continue;
            }
            if ($defaultSender === null) {
                $defaultSender = $groupCode;
            }
            $this->configWriter->save(
                Config::CONFIG_PATH_SENDER,
                $groupCode,
                ScopeInterface::SCOPE_STORE,
                (string) $store->getId()
            );
        }

        foreach ($this->storeManager->getWebSites() as $website) {
            $defaultStore = $website->getDefaultStore();
            if ($defaultStore === null) {
                continue;
            }
            $group = $defaultStore->getGroup();
            $groupCode = $group !== null ? $group->getCode() : null;
            if ($groupCode === null || $groupCode === '') {
                continue;
            }
            $this->configWriter->save(
                Config::CONFIG_PATH_SENDER,
                $groupCode,
                ScopeInterface::SCOPE_WEBSITE,
                (string) $website->getId()
            );
        }

        if ($defaultSender !== null) {
            $this->configWriter->save(
                Config::CONFIG_PATH_SENDER,
                $defaultSender,
                'default',
                '0'
            );
        }
    }
}
