<?php

namespace Packetery\Checkout\Helper\api;

use Packetery\Checkout\Helper\api\Exception\PacketAttributesFault;
use Packetery\Checkout\Helper\api\Exception\RestFault;
use Packetery\Checkout\Helper\api\Model\ClaimAttributes;
use Packetery\Checkout\Helper\api\Model\IModel;
use Packetery\Checkout\Helper\api\Model\PacketAttributes;
use Packetery\Checkout\Helper\api\ArrayToXml;

class ApiRest implements IApi {

    private $restApiUrl = 'https://www.zasilkovna.cz/api/rest';
    private $apiPassword;
    private $apiKey;

    /**
     * ApiRest constructor.
     * @param $apiPassword
     * @param $apiKey
     */
    public function __construct($apiPassword, $apiKey) {
        $this->apiPassword = $apiPassword;
        $this->apiKey = $apiKey;
    }

    /**
     * @param $root
     * @param array $array
     * @return string
     */
    private function array2xml($root, array $array) {
        return ArrayToXml::convert($array, $root);
    }

    /**
     * @param $xml
     * @return mixed
     */
    private function xml2object($xml) {
        $simplexml = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($simplexml);
        return json_decode($json, false);
    }

    /**
     * @param $xml
     * @return mixed
     */
    private function post($xml) {
        $opts = ['http' =>
            [
                'method'  => 'POST',
                'header'  => 'Content-type: text/xml',
                'content' => $xml
            ]
        ];

        $context  = stream_context_create($opts);

        return file_get_contents($this->restApiUrl, false, $context);
    }

    /**
     * @param $method
     * @param IModel|array $object
     * @return mixed
     * @throws RestFault
     */
    private function callApi($method, $object) {
        $xmlArray = [
            'apiPassword' => $this->apiPassword
        ];

        if ($object instanceof IModel)
        {
            $path = explode('\\', get_class($object));
            $dataName =  lcfirst(array_pop($path));
            $data = $object->toArray();

            $xmlArray[$dataName] = $data;
        }
        elseif (is_array($object))
        {
            $xmlArray +=  $object;
        }

        $xml = $this->array2xml($method, $xmlArray);

        $resultXml = $this->post($xml);

        $result = $this->xml2object($resultXml);
        $this->proccessResult($result);

        return (isset($result->result) ? $result->result : null);
    }

    /**
     * @param array $result
     * @throws RestFault|PacketAttributesFault
     */
    private function proccessResult($result) {
        if ($result->status == 'fault')
        {
            if ($result->fault == 'PacketAttributesFault') {
                throw new PacketAttributesFault($result->detail->attributes->fault);
            }
            throw new RestFault($result->fault.': '.$result->string.json_encode($result->detail));
        }
    }

    /**
     * @param PacketAttributes $attributes
     * @return mixed
     */
    public function packetAttributesValid(PacketAttributes $attributes) {
        return $this->callApi(__FUNCTION__, $attributes);
    }

    /**
     * @param ClaimAttributes $attributes
     * @return mixed
     */
    public function packetClaimAttributesValid(ClaimAttributes $attributes) {
        return $this->callApi(__FUNCTION__, $attributes);
    }

    /**
     * @param PacketAttributes $attributes
     * @return mixed
     */
    public function createPacket(PacketAttributes $attributes) {
        return $this->callApi(__FUNCTION__, $attributes);
    }

    /**
     * @param ClaimAttributes $attributes
     * @return mixed
     */
    public function createPacketClaim(ClaimAttributes $attributes) {
        return $this->callApi(__FUNCTION__, $attributes);
    }

    /**
     * @param $packetId
     * @param $customBarcode
     * @return mixed
     */
    public function createShipment(/*int*/ $packetId, /*string*/ $customBarcode) {
        return $this->callApi(__FUNCTION__, ['packetId' => $packetId, 'customBarcode' => $customBarcode]);
    }

    /**
     * @param $packetId
     * @return mixed
     */
    public function packetStatus(/*int*/ $packetId) {
        return $this->callApi(__FUNCTION__, ['packetId' => $packetId]);
    }

    /**
     * @param $packetId
     * @return mixed
     */
    public function packetTracking(/*int*/ $packetId) {
        return $this->callApi(__FUNCTION__, ['packetId' => $packetId]);
    }

    /**
     * @param $packetId
     * @return mixed
     */
    public function packetGetStoredUntil(/*int*/ $packetId) {
        return $this->callApi(__FUNCTION__, ['packetId' => $packetId]);
    }

    /**
     * @param $packetId
     * @param \DateTimeInterface $date
     * @return mixed
     */
    public function packetSetStoredUntil(/*int*/ $packetId, \DateTimeInterface $date) {
        return $this->callApi(__FUNCTION__, ['packetId' => $packetId, 'date' => $date->format('Y-m-d H:i:s')]);
    }

    /**
     * @param $barcode
     * @return mixed
     */
    public function barcodePng(/*string*/ $barcode) {
        return $this->callApi(__FUNCTION__, ['barcode' => $barcode]);
    }

    /**
     * @param $packetId
     * @param $format
     * @param $offset
     * @return mixed
     */
    public function packetLabelPdf(/*int*/ $packetId, /*string*/ $format, /*int*/ $offset) {
        return $this->callApi(__FUNCTION__, ['packetId' => $packetId, 'format' => $format, 'offset' => $offset]);
    }

    /**
     * @param array $packetIds
     * @param $format
     * @param $offset
     * @return mixed
     */
    public function packetsLabelsPdf(array/*PacketIds*/ $packetIds, /*string*/ $format, /*int*/ $offset) {
        return $this->callApi(__FUNCTION__, ['packetIds' => $packetIds, 'format' => $format, 'offset' => $offset]);
    }

    /**
     * @param $packetId
     * @return mixed
     */
    public function packetCourierNumber(/*int*/ $packetId) {
        return $this->callApi(__FUNCTION__, ['packetId' => $packetId]);
    }

    /**
     * @param $senderLabel
     * @return mixed
     */
    public function senderGetReturnRouting(/*string*/ $senderLabel) {
        return $this->callApi(__FUNCTION__, ['senderLabel' => $senderLabel]);
    }
}
