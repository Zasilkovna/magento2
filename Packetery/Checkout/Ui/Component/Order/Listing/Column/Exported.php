<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Packetery\Checkout\Ui\Component\Order\Listing\ByFieldColumnTrait;
use Packetery\Checkout\Model\Packet\TrackingUrlFactory;

class Exported extends Column
{
    use ByFieldColumnTrait;

    /** @var \Magento\Framework\Escaper */
    private $escaper;

    /** @var \Packetery\Checkout\Model\Packet\TrackingUrlFactory */
    private $trackingUrlFactory;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Escaper $escaper,
        TrackingUrlFactory $trackingUrlFactory,
        array $components = [],
        array $data = []
    ) {
        $this->escaper = $escaper;
        $this->trackingUrlFactory = $trackingUrlFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $exportedField = $this->getByField();
        $name = $this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            $packetNumber = isset($item['packet_number']) ? trim((string) $item['packet_number']) : '';
            $exported = $item[$exportedField] ?? 0;

            if ($packetNumber !== '') {
                $trackingNumber = 'Z' . $packetNumber;
                $trackingUrl = $this->trackingUrlFactory->create($packetNumber);
                $item[$name] = '<a href="' . $this->escaper->escapeHtmlAttr($trackingUrl) . '" target="_blank" rel="noopener noreferrer">'
                    . $this->escaper->escapeHtml($trackingNumber) . '</a>';
            } else {
                $item[$name] = !empty($exported) ? __('Yes') : __('No');
            }
        }

        return $dataSource;
    }
}
