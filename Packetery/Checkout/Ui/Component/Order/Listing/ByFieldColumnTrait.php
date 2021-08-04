<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing;

trait ByFieldColumnTrait
{
    /**
     * Specifies what field value is used as input in field transformation. It is used in SQL sorting as well.
     *
     * @return string
     */
    private function getByField(): string {
        return $this->getData('packetery/byField') ?? $this->getData('name');
    }

    /**
     * Apply sorting
     *
     * @return void
     */
    protected function applyByFieldSorting(): void
    {
        $sorting = $this->getContext()->getRequestParam('sorting');
        $isSortable = $this->getData('config/sortable');
        if ($isSortable !== false
            && !empty($sorting['field'])
            && !empty($sorting['direction'])
            && $sorting['field'] === $this->getName()
        ) {
            $this->getContext()->getDataProvider()->addOrder(
                $this->getByField(),
                strtoupper($sorting['direction'])
            );
        }
    }
}
