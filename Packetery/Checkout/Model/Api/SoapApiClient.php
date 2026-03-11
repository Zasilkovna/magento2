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
            $client = new \SoapClient(
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
}
