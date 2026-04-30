<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Api;

class SoapApiClient
{
    private const WSDL_URL = 'https://soap.api.packeta.com/api/soap-php-bugfix.wsdl';

    /**
     * @throws \Packetery\Checkout\Model\Api\PacketSubmissionException
     */
    public function createPacket(
        string $apiPassword,
        \Packetery\Checkout\Model\Packet\PacketAttributes $packetAttributes
    ): \Packetery\Checkout\Model\Api\Result\CreatePacketResult {
        try {
            $client = $this->createSoapClient();
            $result = $client->createPacket($apiPassword, $packetAttributes->toArray());
        } catch (\SoapFault $e) {
            $errors = $this->getValidationErrors($e);
            throw new PacketSubmissionException($e->getMessage(), $errors, $e);
        }

        if (!isset($result->id)) {
            throw new PacketSubmissionException('API did not return packet id.');
        }

        return new \Packetery\Checkout\Model\Api\Result\CreatePacketResult((string) $result->id);
    }

    /**
     * @param \Packetery\Checkout\Model\Api\Request\PacketsLabelsPdfRequest $request
     */
    public function packetsLabelsPdf(
        \Packetery\Checkout\Model\Api\Request\PacketsLabelsPdfRequest $request
    ): \Packetery\Checkout\Model\Api\Result\PacketsLabelsPdfResult {
        $apiPassword = $request->getApiPassword();
        $packetIds = $request->getPacketIds();
        $format = $request->getFormat();
        $offset = $request->getOffset();

        $response = new \Packetery\Checkout\Model\Api\Result\PacketsLabelsPdfResult();
        try {
            $client = $this->createSoapClient();
            $pdfContents = $client->packetsLabelsPdf($apiPassword, $packetIds, $format, $offset);
            $response->setPdfContents(is_string($pdfContents) ? $pdfContents : null);
        } catch (\SoapFault $e) {
            $response->setFault($this->getFaultIdentifier($e));
            $response->setFaultString($e->getMessage());
            if ($response->getFault() === 'PacketIdsFault') {
                $response->setInvalidPacketIds($this->getInvalidPacketIdsSoapDetailErrors($e));
            }
        }

        return $response;
    }

    /**
     * @param \Packetery\Checkout\Model\Api\Request\PacketsCourierLabelsPdfRequest $request
     */
    public function packetsCourierLabelsPdf(
        \Packetery\Checkout\Model\Api\Request\PacketsCourierLabelsPdfRequest $request
    ): \Packetery\Checkout\Model\Api\Result\PacketsCourierLabelsPdfResult {
        $apiPassword = $request->getApiPassword();
        $packetIdsWithCourierNumbers = $request->getPacketIdsWithCourierNumbers();
        $offset = $request->getOffset();
        $format = $request->getFormat();

        $response = new \Packetery\Checkout\Model\Api\Result\PacketsCourierLabelsPdfResult();
        $soapPairs = [];
        foreach ($packetIdsWithCourierNumbers as $pair) {
            $item = new \stdClass();
            $item->packetId = $pair['packetId'];
            $item->courierNumber = $pair['courierNumber'];
            $soapPairs[] = $item;
        }

        try {
            $client = $this->createSoapClient();
            $pdfContents = $client->packetsCourierLabelsPdf($apiPassword, $soapPairs, $offset, $format);
            $response->setPdfContents(is_string($pdfContents) ? $pdfContents : null);
        } catch (\SoapFault $e) {
            $response->setFault($this->getFaultIdentifier($e));
            $response->setFaultString($e->getMessage());
            if ($response->getFault() === 'InvalidCourierNumberFault' && count($packetIdsWithCourierNumbers) === 1) {
                $response->setInvalidCourierNumbers(array_column($packetIdsWithCourierNumbers, 'courierNumber'));
            }
            if ($response->getFault() === 'PacketIdFault' && count($packetIdsWithCourierNumbers) === 1) {
                $response->setInvalidPacketIds(array_column($packetIdsWithCourierNumbers, 'packetId'));
            }
        }

        return $response;
    }

    /**
     * @param \Packetery\Checkout\Model\Api\Request\PacketCourierNumberRequest $request
     */
    public function packetCourierNumber(
        \Packetery\Checkout\Model\Api\Request\PacketCourierNumberRequest $request
    ): \Packetery\Checkout\Model\Api\Result\PacketCourierNumberResult {
        $apiPassword = $request->getApiPassword();
        $packetId = $request->getPacketId();

        $response = new \Packetery\Checkout\Model\Api\Result\PacketCourierNumberResult();
        try {
            $client = $this->createSoapClient();
            $number = $client->packetCourierNumber($apiPassword, $packetId);
            $response->setCourierNumber(
                (is_string($number) || is_numeric($number)) ? (string) $number : null
            );
        } catch (\SoapFault $e) {
            $response->setFault($this->getFaultIdentifier($e));
            $response->setFaultString($e->getMessage());
        }

        return $response;
    }

    /**
     * @throws \SoapFault
     */
    private function createSoapClient(): \SoapClient
    {
        return new \SoapClient(
            self::WSDL_URL,
            [
                'exceptions' => true,
                'connection_timeout' => 10,
                'stream_context' => stream_context_create(
                    [
                        'http' => [
                            'timeout' => 10,
                        ],
                    ]
                ),
            ]
        );
    }

    /**
     * @return string[]
     */
    private function getValidationErrors(\SoapFault $e): array
    {
        $errors = [];
        if (!isset($e->detail->PacketAttributesFault->attributes->fault)) {
            return $errors;
        }
        $faults = $e->detail->PacketAttributesFault->attributes->fault;
        if (!is_array($faults)) {
            $faults = [$faults];
        }
        foreach ($faults as $fault) {
            if (isset($fault->name, $fault->fault)) {
                $errors[] = $fault->fault;
            }
        }
        return $errors;
    }

    /**
     * @return string[]
     */
    private function getInvalidPacketIdsSoapDetailErrors(\SoapFault $e): array
    {
        $errors = [];
        if (isset($e->detail->PacketIdsFault->ids->packetId)) {
            $ids = $e->detail->PacketIdsFault->ids->packetId;
            if (!is_array($ids)) {
                $ids = [$ids];
            }
            foreach ($ids as $id) {
                $errors[] = (string) $id;
            }
        }

        return $errors;
    }

    private function getFaultIdentifier(\SoapFault $e): string
    {
        if (!isset($e->detail)) {
            return '';
        }

        $fields = array_keys(get_object_vars($e->detail));
        if ($fields === []) {
            return '';
        }

        return (string) $fields[0];
    }
}
