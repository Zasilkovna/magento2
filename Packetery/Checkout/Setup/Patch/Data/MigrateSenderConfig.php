<?php

declare(strict_types=1);

namespace Packetery\Checkout\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Module\ModuleResource;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Packetery\Checkout\Model\Carrier\Imp\Packetery\Config;

/** getVersion() only guards re-run on existing installs; it is not the module version */
class MigrateSenderConfig implements DataPatchInterface, PatchVersionInterface
{
    /** @var WriterInterface */
    private $configWriter;

    /** @var ModuleResource */
    private $moduleResource;

    /** @var StoreManagerInterface */
    private $storeManager;

    public function __construct(
        WriterInterface $configWriter,
        ModuleResource $moduleResource,
        StoreManagerInterface $storeManager
    ) {
        $this->configWriter = $configWriter;
        $this->moduleResource = $moduleResource;
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
        $dataVersion = (string) $this->moduleResource->getDataVersion('Packetery_Checkout');

        // fresh install has no legacy store-group sender to migrate; merchant sets it
        if ($dataVersion === '') {
            return;
        }

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
