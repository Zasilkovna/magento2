<?php


namespace Packetery\Checkout\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Packetery\Checkout\Console\Command\MigratePriceRules;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class UpgradeData implements UpgradeDataInterface
{
    /** @var MigratePriceRules */
    private $migratePriceRulesCommand;

    /** @var \Magento\Config\Model\Config\Factory */
    private $configFactory;

    /**
     * UpgradeData constructor.
     *
     * @param \Packetery\Checkout\Console\Command\MigratePriceRules $migratePriceRulesCommand
     * @param \Magento\Config\Model\Config\Factory $configFactory
     */
    public function __construct(MigratePriceRules $migratePriceRulesCommand, \Magento\Config\Model\Config\Factory $configFactory)
    {
        $this->migratePriceRulesCommand = $migratePriceRulesCommand;
        $this->configFactory = $configFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ): void {
        if (version_compare($context->getVersion(), "2.0.1", ">=") && version_compare($context->getVersion(), "2.0.3", "<")) {
            $this->migratePriceRulesCommand->run(new ArrayInput([]), new NullOutput());
        }

        if (version_compare($context->getVersion(), "2.0.6", "<")) {
            $configModel = $this->configFactory->create();
            $configModel->setDataByPath('carriers/packetery/sallowspecific', 0); // config option UI was removed
            $configModel->save();
        }
    }
}
