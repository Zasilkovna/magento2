<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

use Exception;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Packetery\Checkout\Model\ResourceModel\Box as BoxResource;

class BoxRepository
{
    private array $instances = [];

    public function __construct(
        private readonly BoxFactory $boxFactory,
        private readonly BoxResource $boxResource
    ) {
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $id): Box
    {
        if (!isset($this->instances[$id])) {
            $box = $this->boxFactory->create();
            $this->boxResource->load($box, $id);

            if (!$box->getId()) {
                throw new NoSuchEntityException(__('Box with id "%1" does not exist.', $id));
            }

            $this->instances[$id] = $box;
        }

        return $this->instances[$id];
    }

    public function save(Box $box): Box
    {
        try {
            $this->boxResource->save($box);
            if ($box->getId()) {
                $this->instances[$box->getId()] = $box;
            }
        } catch (Exception $e) {
            throw new CouldNotSaveException(__('Could not save box: %1', $e->getMessage()), $e);
        }

        return $box;
    }

    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }

    private function delete(Box $box): bool
    {
        try {
            $this->boxResource->delete($box);
            if ($box->getId()) {
                unset($this->instances[$box->getId()]);
            }
        } catch (Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete box: %1', $e->getMessage()), $e);
        }

        return true;
    }
}
