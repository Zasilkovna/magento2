<?php

declare(strict_types=1);

namespace Packetery\Checkout\Cron;

use Psr\Log\LoggerInterface;

class ImportFeed
{
    /** @var \Psr\Log\LoggerInterface  */
    protected $logger;

    /** @var \Packetery\Checkout\Model\Carrier\PacketeryConfig */
    private $packeteryConfig;

    /** @var \GuzzleHttp\Client */
    private $client;

    /** @var PacketeryOrderCollection */
    private $collection;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $this->logger->info('Feed import started');

        $apiKey = $this->packeteryConfig->getApiKey();
        $response = $this->client->get("https://www.zasilkovna.cz/api/v4/{$apiKey}/branch.json?address-delivery");

        $content = $response->getBody()->getContents();
        $data = json_decode($content);

        $collection = $this->collectionFactory->create();
        $collection->setData(
            [
                'deleted' => true,
            ]
        );
        $collection->save();

        foreach ($data->carriers->carrier as $carrier) {
            $data = [
                'id' => $carrier->id,
                'name' => $carrier->name,
                'is_pickup_points' => $carrier->pickupPoints,
                'has_carrier_direct_label' => $carrier->apiAllowed,
                'separate_house_number' => $carrier->separateHouseNumber,
                'customs_declarations' => $carrier->customsDeclarations,
                'requires_email' => $carrier->requiresEmail,
                'requires_phone' => $carrier->requiresPhone,
                'requires_size' => $carrier->requiresSize,
                'disallows_cod' => $carrier->disallowsCod,
                'country' => $carrier->country,
                'currency' => $carrier->currency,
                'max_weight' => (float)$carrier->currency,
                'deleted' => false,
            ];

            $collection = $this->collectionFactory->create();
            $record = $collection->getItemById($carrier->id);
            $record->setData($data);
            $record->save();
        }

        $this->logger->info('Feed import ended successfuly');
    }
}

