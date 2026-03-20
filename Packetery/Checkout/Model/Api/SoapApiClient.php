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
     * @throws \Packetery\Checkout\Model\Api\PacketLabelException
     */
    public function packetsLabelsPdf(
        string $apiPassword,
        array $packetIds,
        string $format,
        int $offset
    ): string {
        try {
            $client = $this->createSoapClient();
            $pdfContents = $client->packetsLabelsPdf($apiPassword, $packetIds, $format, $offset);
        } catch (\SoapFault $e) {
            throw new PacketLabelException($e->getMessage(), $this->getLabelSoapDetailErrors($e), $e);
        }

        if (!is_string($pdfContents)) {
            throw new PacketLabelException('API did not return PDF data.', []);
        }

        return $pdfContents;
    }

    /**
     * @param array<int, array{packetId: string, courierNumber: string}> $packetIdsWithCourierNumbers
     */
    /**
     * @param array<int, array{packetId: string, courierNumber: string}> $packetIdsWithCourierNumbers
     * @throws \Packetery\Checkout\Model\Api\PacketLabelException
     */
    public function packetsCourierLabelsPdf(
        string $apiPassword,
        array $packetIdsWithCourierNumbers,
        int $offset,
        string $format
    ): string {
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
        } catch (\SoapFault $e) {
            throw new PacketLabelException($e->getMessage(), $this->getLabelSoapDetailErrors($e), $e);
        }

        if (!is_string($pdfContents)) {
            throw new PacketLabelException('API did not return PDF data.', []);
        }

        return $pdfContents;
    }

    /**
     * @throws \Packetery\Checkout\Model\Api\PacketLabelException
     */
    public function packetCourierNumber(string $apiPassword, string $packetId): string
    {
        try {
            $client = $this->createSoapClient();
            $number = $client->packetCourierNumber($apiPassword, $packetId);
        } catch (\SoapFault $e) {
            throw new PacketLabelException($e->getMessage(), $this->getLabelSoapDetailErrors($e), $e);
        }

        if (!is_string($number) && !is_numeric($number)) {
            throw new PacketLabelException('API did not return courier number.', []);
        }

        return (string) $number;
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
    private function getLabelSoapDetailErrors(\SoapFault $e): array
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
}
