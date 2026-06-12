<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Column;

class PacketStatus extends \Magento\Ui\Component\Listing\Columns\Column
{
    use \Packetery\Checkout\Ui\Component\Order\Listing\ByFieldColumnTrait;

    /** @var \Packetery\Checkout\Model\Packet\PacketStatusProvider */
    private $packetStatusProvider;

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        \Packetery\Checkout\Model\Packet\PacketStatusProvider $packetStatusProvider,
        array $components = [],
        array $data = []
    ) {
        $this->packetStatusProvider = $packetStatusProvider;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $field = $this->getByField();
        $name = $this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            $value = isset($item[$field]) ? (string) $item[$field] : '';
            if ($value === '') {
                continue;
            }

            if ($value === \Packetery\Checkout\Model\Packet\PacketStatus::DOES_NOT_EXIST) {
                $item[$name] = __('Packet does not exist');
                continue;
            }

            $item[$name] = $this->packetStatusProvider->getTranslatedName($value);
        }

        return $dataSource;
    }
}
