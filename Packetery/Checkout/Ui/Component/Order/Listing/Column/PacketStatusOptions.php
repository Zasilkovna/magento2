<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Column;

class PacketStatusOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    /** @var \Packetery\Checkout\Model\PacketRepository */
    private $packetRepository;

    /** @var \Packetery\Checkout\Model\Packet\PacketStatusProvider */
    private $packetStatusProvider;

    public function __construct(
        \Packetery\Checkout\Model\PacketRepository $packetRepository,
        \Packetery\Checkout\Model\Packet\PacketStatusProvider $packetStatusProvider
    ) {
        $this->packetRepository = $packetRepository;
        $this->packetStatusProvider = $packetStatusProvider;
    }

    /**
     * Builds the filter options from the statuses that are actually present among packets.
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        $options = [];

        foreach ($this->packetRepository->getPresentStatusCodeTexts() as $codeText) {
            $options[] = [
                'value' => $codeText,
                'label' => $this->packetStatusProvider->getTranslatedName($codeText),
            ];
        }

        if ($this->packetRepository->hasAnyPacketIdFault()) {
            $options[] = [
                'value' => \Packetery\Checkout\Model\Packet\PacketStatus::DOES_NOT_EXIST,
                'label' => __('Packet does not exist'),
            ];
        }

        usort(
            $options,
            static function (array $first, array $second): int {
                return strcasecmp((string) $first['label'], (string) $second['label']);
            }
        );

        return $options;
    }
}
