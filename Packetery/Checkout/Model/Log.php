<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

class Log extends \Magento\Framework\Model\AbstractModel
{
    public const TABLE_NAME = 'packetery_log';

    public const ID = 'id';
    public const ACTION = 'action';
    public const STATUS = 'status';
    public const ORDER_NUMBER = 'order_number';
    public const NOTE = 'note';
    public const PARAMS = 'params';
    public const RESPONSE = 'response';
    public const CREATED_AT = 'created_at';

    public const ACTION_SUBMIT = 'submit';
    public const ACTION_CANCEL = 'cancel';
    public const ACTION_PRINT_LABEL = 'print_label';
    public const ACTION_PRINT_LIST = 'print_list';

    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';

    protected function _construct(): void
    {
        $this->_init(\Packetery\Checkout\Model\ResourceModel\Log::class);
    }

    public function getAction(): ?string
    {
        return $this->getData(self::ACTION);
    }

    public function setAction(string $action): self
    {
        return $this->setData(self::ACTION, $action);
    }

    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS);
    }

    public function setStatus(string $status): self
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getOrderNumber(): ?string
    {
        return $this->getData(self::ORDER_NUMBER);
    }

    public function setOrderNumber(?string $orderNumber): self
    {
        return $this->setData(self::ORDER_NUMBER, $orderNumber);
    }

    public function getNote(): ?string
    {
        return $this->getData(self::NOTE);
    }

    public function setNote(?string $note): self
    {
        return $this->setData(self::NOTE, $note);
    }

    public function getParams(): ?string
    {
        return $this->getData(self::PARAMS);
    }

    public function setParams(?string $params): self
    {
        return $this->setData(self::PARAMS, $params);
    }

    public function getResponse(): ?string
    {
        return $this->getData(self::RESPONSE);
    }

    public function setResponse(?string $response): self
    {
        return $this->setData(self::RESPONSE, $response);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
