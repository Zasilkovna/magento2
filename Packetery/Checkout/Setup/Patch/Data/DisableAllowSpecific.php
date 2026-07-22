<?php

declare(strict_types=1);

namespace Packetery\Checkout\Setup\Patch\Data;

use Magento\Config\Model\Config\Factory as ConfigFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/** getVersion() only guards re-run on existing installs; it is not the module version */
class DisableAllowSpecific implements DataPatchInterface, PatchVersionInterface
{
    /** @var ConfigFactory */
    private $configFactory;

    public function __construct(
        ConfigFactory $configFactory
    ) {
        $this->configFactory = $configFactory;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getVersion(): string
    {
        return '2.1.0';
    }

    public function apply(): void
    {
        $configModel = $this->configFactory->create();
        $configModel->setDataByPath('carriers/packetery/sallowspecific', 0); // config option UI was removed
        $configModel->save();
    }
}
