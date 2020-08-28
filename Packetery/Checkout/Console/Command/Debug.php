<?php

namespace Packetery\Checkout\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Packetery\Checkout\Helper\DataHelper;
use Magento\Framework\App\State as AppState;

class Debug extends Command {

    protected $_helper;
    protected $_appState;

    public function __construct(
        AppState $appState,
        DataHelper $helper
    ){
        $this->_helper = $helper;
        $this->_appState = $appState;
        parent::__construct('packetery:debug');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->_appState->setAreaCode('adminhtml');
        
        $output->writeln("<info>Packetery Debuger</info>");
    
        $output->writeln("Function enable: " . $this->_helper->isEnabled() );
        $output->writeln("test(): " . $this->_helper->test() );
    }

    protected function configure() {
        $this->setName("packetery:debug");
        $this->setDescription("ssh comand for debuging cod");
        parent::configure();
    }
}

