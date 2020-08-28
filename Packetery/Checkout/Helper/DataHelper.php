<?php
declare(strict_types=1);

namespace Packetery\Checkout\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Packetery\Checkout\Helper\api\ApiRest;
use Packetery\Checkout\Helper\api\Branch;
use Packetery\Checkout\Helper\api\Label;
use Packetery\Checkout\Helper\api\Model\BranchStorageFile;
use Packetery\Checkout\Helper\api\Model\PacketAttributes;

class DataHelper extends AbstractHelper {

    protected $api = null;
    protected $branch = null;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isEnabled() {
        return true;
    }

    public function test(){
        $this->setUp();

        //var_dump($this->api->packetStatus('1970667409'));
        //var_dump($this->branch->getBranchList());

        $transporterPackage = new PacketAttributes(
            '000001',
            'Adrian Alin',
            'Munteanu',
            100,
            79, //address id Praha 4, Pražského povstání, Na Pankráci 969/97
            'Z 105 0045 766',
            'Royalsoft',
            'adrianm@royalsoft.eu',
            '0761633677',
            null,
            null,
            null,
            'sansha.royalsoft.dev',
            false,
            'Street',
            'StreetNumber',
            null,
            null
        );

        //$this->api->createPacket($transporterPackage); 

        $label = new Label($this->api, $this->branch);
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->AddPage();
        $label->generateLabelFull($pdf, $transporterPackage);

    }

    public function setUp()
    {
        $configPath = __DIR__ . '/config.json';
        if (file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath));


            $this->api = new ApiRest(
                $config->apiPassword,
                $config->apiKey
            );

            $this->branch = new Branch(
                $config->apiKey, 
                new BranchStorageFile()
            );
           
        }
        else
        {
            throw new \Exception('config.json not found');
        }
    }

}

