<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

use Exception;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
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

    /**
     * The first box created silently becomes the default.
     */
    public function save(Box $box): Box
    {
        try {
            if (!$box->getId() && !$this->hasDefaultBox()) {
                $box->setIsDefault(true);
            }

            $this->boxResource->save($box);
            if ($box->getId()) {
                $this->instances[$box->getId()] = $box;
            }
        } catch (Exception $e) {
            throw new CouldNotSaveException(__('Could not save box: %1', $e->getMessage()), $e);
        }

        return $box;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getDefaultBox(): ?Box
    {
        $id = $this->findDefaultBoxId();

        return $id !== null ? $this->getById($id) : null;
    }

    private function hasDefaultBox(): bool
    {
        return $this->findDefaultBoxId() !== null;
    }

    private function findDefaultBoxId(): ?int
    {
        $connection = $this->boxResource->getConnection();

        $id = $connection->fetchOne(
            $connection->select()
                ->from($this->boxResource->getTable(Box::TABLE_NAME), Box::ID)
                ->where(Box::IS_DEFAULT . ' = ?', 1)
                ->where(Box::DELETED . ' = ?', 0)
                ->limit(1)
        );

        return $id ? (int) $id : null;
    }

    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }

    private function delete(Box $box): bool
    {
        try {
            $box->setDeleted(true);
            $this->boxResource->save($box);
            if ($box->getId()) {
                $this->instances[$box->getId()] = $box;
            }
        } catch (Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete box: %1', $e->getMessage()), $e);
        }

        return true;
    }

    /**
     * The bulk reset changes is_default in the DB,
     * so the instance cache is dropped and only the new default is kept.
     *
     * @throws LocalizedException
     */
    public function setAsDefault(int $id): Box
    {
        $box = $this->getById($id);
        if ($box->getDeleted()) {
            throw new NoSuchEntityException();
        }

        $connection = $this->boxResource->getConnection();
        $connection->beginTransaction();

        try {
            $this->boxResource->unsetDefaultFlag();

            $box->setIsDefault(true);
            $this->boxResource->save($box);
            $connection->commit();

            $this->instances = [$box->getId() => $box];
        } catch (Exception $e) {
            $connection->rollBack();
            throw new CouldNotSaveException(__('Could not set box as default: %1', $e->getMessage()), $e);
        }

        return $box;
    }
}
