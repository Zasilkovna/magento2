<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Weight;

class Item extends \Magento\Framework\DataObject
{
    /**
     * @param array $items
     * @return array
     */
    public static function transformItems(array $items): array
    {
        $instances = [];

        foreach ($items as $key => $item) {
            if ($item instanceof \Magento\Quote\Model\Quote\Item) {
                $instances[$key] = self::fromQuoteItem($item);
            }

            if ($item instanceof \Magento\Sales\Model\Order\Item) {
                $instances[$key] = self::fromOrderItem($item);
            }
        }

        return $instances;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return static
     */
    public static function fromQuoteItem(\Magento\Quote\Model\Quote\Item $item): self
    {
        $instance = new self($item->getData());
        $instance->setData('qty', $item->getTotalQty()); // only configurable items have correct quantity
        $instance->setData('product', $item->getProduct());

        $children = [];
        foreach ($item->getChildren() as $child) {
            $children[] = self::fromQuoteItem($child);
        }

        $instance->setData('children', $children);

        return $instance;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return static
     */
    public static function fromOrderItem(\Magento\Sales\Model\Order\Item $item): self
    {
        $instance = new self($item->getData());
        $instance->setData('qty', $item->getQtyOrdered());
        $instance->setData('product', $item->getProduct());

        $children = [];
        foreach ($item->getChildrenItems() as $child) {
            $children[] = self::fromOrderItem($child);
        }

        $instance->setData('children', $children);

        return $instance;
    }

    /**
     * @return string
     */
    public function getProductType()
    {
        return parent::getProductType();
    }

    /**
     * @return self[]
     */
    public function getChildren()
    {
        return $this->getData('children');
    }

    /**
     * @return int
     */
    public function getQty()
    {
        return $this->getData('qty');
    }

    /**
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->getData('product');
    }
}
