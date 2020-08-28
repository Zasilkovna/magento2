<?php
declare(strict_types=1);

namespace Packetery\Checkout\Block\Debug;

class View extends \Magento\Framework\View\Element\Template {

    public $dataHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Packetery\Checkout\Helper\DataHelper $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    public function test(){
        echo 'asd';
        //$this->dataHelper->test();
        return;
    }
}
