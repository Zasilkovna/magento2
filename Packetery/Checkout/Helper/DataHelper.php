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
    public $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isEnabled() {
        return true;
    }

    public function sendTest(){
        $this->setUp();
        $transporterPackage = new PacketAttributes(
            '2000003107',
            'Adrian Alin',
            'Munteanu',
            2756.5,
            5797,
            null,
            'Royalsoft',
            'munteanuadrian89@gmail.com',
            '0761633677',
            'CZK',
            (int)round('2756.5'),
            1.268,
            'Brno, Husovice, Nováčkova 35',
            false,
            implode(' ',['Constanta','Sr. Constantin Brancusi','Nr. 30']),
            null,
            'Praha',
            '592 57'
        );
        $return = $this->api->packetAttributesValid($transporterPackage); 
        var_dump($return);die;
        return;
    }

    public function test(){ 
        $this->sendTest();
        die('test');
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
        $pdf->AddPage('L', 'A4');
        $pdf = $label->generateLabelFull($pdf, $transporterPackage);
        ob_end_clean();
        $pdf->Output('example_006.pdf', 'I');
        return 'ok';

    }

    public function generatePdf($data){
        $this->setUp();
        $label = new Label($this->api, $this->branch);
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        foreach($data as $transporterPackage){
            $pdf->AddPage('L', 'A4');
            $pdf = $label->generateLabelFull($pdf, $transporterPackage);
        }
        ob_end_clean();
        $pdf->Output('Labels.pdf', 'I');
        return;
    }

    public function sendData($data){
        $this->setUp();
        $senderName = $this->scopeConfig->getValue(
            'widget/options/sender_name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $transporterPackage = new PacketAttributes(
            $data['order_number'],
            $data['recipient_firstname'],
            $data['recipient_lastname'],
            $data['value'],
            $data['point_id'],
            null,
            $data['recipient_company'],
            $data['recipient_email'],
            $data['recipient_phone'],
            $data['currency'],
            (int)round($data['cod']),
            $data['weight'],
            'SANSHA Praha',//@todo add backend sender name
            $data['adult_content'],
            $data['recipient_street'],
            $data['recipient_house_number'],
            $data['recipient_city'],
            $data['recipient_zip']
        );
        $return = $this->api->createPacket($transporterPackage); 
        return $return;
    }

    public function setUp() {
        $apiKey = $this->scopeConfig->getValue(
            'widget/options/api_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $apiPassword = $this->scopeConfig->getValue(
            'widget/options/api_key_password',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        //@todo add variables in admin and get them from there base on store id
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
           
        } else {
            throw new \Exception('config.json not found');
        }
    }

}

