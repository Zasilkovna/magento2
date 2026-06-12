<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

class PacketRepository
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Packet\CollectionFactory */
    private $packetCollectionFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Packet */
    private $packetResource;

    public function __construct(
        \Packetery\Checkout\Model\ResourceModel\Packet\CollectionFactory $packetCollectionFactory,
        \Packetery\Checkout\Model\ResourceModel\Packet $packetResource
    ) {
        $this->packetCollectionFactory = $packetCollectionFactory;
        $this->packetResource = $packetResource;
    }

    public function findLatestByOrderNumber(string $orderNumber): ?Packet
    {
        $collection = $this->packetCollectionFactory->create();
        $collection->addFieldToFilter('order_number', $orderNumber);
        $collection->setOrder('id', 'DESC');
        $collection->setPageSize(1);
        $items = $collection->getItems();
        if ($items === []) {
            return null;
        }

        $first = reset($items);
        if (!$first instanceof Packet) {
            return null;
        }

        return $first;
    }

    public function findById(int $packetId): ?Packet
    {
        $collection = $this->packetCollectionFactory->create();
        $collection->addFieldToFilter('id', $packetId);
        $collection->setPageSize(1);
        $items = $collection->getItems();
        if ($items === []) {
            return null;
        }

        $first = reset($items);
        if (!$first instanceof Packet) {
            return null;
        }

        return $first;
    }

    /**
     * Open packets have a non-final last known status, are not flagged as non-existent and still have a matching Magento order.
     * They are ordered from the longest unsynchronized first (NULL sync time wins); a positive limit caps the result.
     *
     * @param string[] $finalCodeTexts
     * @return int[]
     */
    public function getOpenPacketIds(array $finalCodeTexts, int $limit): array
    {
        $collection = $this->packetCollectionFactory->create();
        $collection->addFieldToFilter('packet_id_fault', 0);
        if ($finalCodeTexts !== []) {
            $collection->addFieldToFilter(
                'packet_status',
                [
                    ['nin' => $finalCodeTexts],
                    ['null' => true],
                ]
            );
        }
        $select = $collection->getSelect();
        $select->reset(\Magento\Framework\DB\Select::COLUMNS);
        $select->columns('id', 'main_table');
        $select->joinInner(
            ['sales_order' => $this->packetResource->getTable('sales_order')],
            'sales_order.increment_id = main_table.order_number',
            []
        );
        $select->order('status_synced_at ASC');
        if ($limit > 0) {
            $select->limit($limit);
        }

        return array_map('intval', $collection->getConnection()->fetchCol($select));
    }

    /**
     * @return string[] Distinct packet status codeTexts currently present among packets.
     */
    public function getPresentStatusCodeTexts(): array
    {
        $collection = $this->packetCollectionFactory->create();
        $connection = $collection->getConnection();
        $select = $connection->select()
            ->from($collection->getMainTable(), ['packet_status'])
            ->where('packet_status IS NOT NULL')
            ->distinct(true);

        return array_map('strval', $connection->fetchCol($select));
    }

    public function hasAnyPacketIdFault(): bool
    {
        $collection = $this->packetCollectionFactory->create();
        $connection = $collection->getConnection();
        $select = $connection->select()
            ->from($collection->getMainTable(), ['packet_id_fault'])
            ->where('packet_id_fault = ?', 1)
            ->limit(1);

        return $connection->fetchOne($select) !== false;
    }

    public function save(Packet $packet): void
    {
        $this->packetResource->save($packet);
    }

    public function delete(Packet $packet): void
    {
        $this->packetResource->delete($packet);
    }
}
