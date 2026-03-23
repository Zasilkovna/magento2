<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

use Magento\Framework\Model\AbstractModel;

class Box extends AbstractModel
{
    public const TABLE_NAME = 'packetery_box';

    public const ID = 'id';
    public const NAME = 'name';
    public const DEPTH = 'depth';
    public const WIDTH = 'width';
    public const HEIGHT = 'height';

    private const ROUND_PRECISION = 1;

    protected function _construct(): void
    {
        $this->_init(ResourceModel\Box::class);
    }

    public function getName(): ?string
    {
        return $this->getData(self::NAME);
    }

    public function setName(string $name): self
    {
        return $this->setData(self::NAME, $name);
    }

    public function getDepth(): ?float
    {
        return $this->getData(self::DEPTH) !== null
            ? (float) $this->getData(self::DEPTH)
            : null;
    }

    public function setDepth(float $depth): self
    {
        $depth = round($depth, self::ROUND_PRECISION);
        return $this->setData(self::DEPTH, $depth);
    }

    public function getWidth(): ?float
    {
        return $this->getData(self::WIDTH) !== null
            ? (float) $this->getData(self::WIDTH)
            : null;
    }

    public function setWidth(float $width): self
    {
        $width = round($width, self::ROUND_PRECISION);
        return $this->setData(self::WIDTH, $width);
    }

    public function getHeight(): ?float
    {
        return $this->getData(self::HEIGHT) !== null
            ? (float) $this->getData(self::HEIGHT)
            : null;
    }

    public function setHeight(float $height): self
    {
        $height = round($height, self::ROUND_PRECISION);
        return $this->setData(self::HEIGHT, $height);
    }
}
