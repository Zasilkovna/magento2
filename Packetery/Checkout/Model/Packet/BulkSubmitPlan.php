<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

class BulkSubmitPlan
{
    /** @var int[] */
    private $queueableOrderIds;

    /** @var int */
    private $alreadySubmittedCount;

    /** @var int */
    private $missingConfigCount;

    /** @var string[] */
    private $missingConfigStoreNames;

    /**
     * @param int[] $queueableOrderIds
     * @param string[] $missingConfigStoreNames
     */
    public function __construct(
        array $queueableOrderIds,
        int $alreadySubmittedCount,
        int $missingConfigCount,
        array $missingConfigStoreNames
    ) {
        $this->queueableOrderIds = $queueableOrderIds;
        $this->alreadySubmittedCount = $alreadySubmittedCount;
        $this->missingConfigCount = $missingConfigCount;
        $this->missingConfigStoreNames = $missingConfigStoreNames;
    }

    /** @return int[] */
    public function getQueueableOrderIds(): array
    {
        return $this->queueableOrderIds;
    }

    public function getAlreadySubmittedCount(): int
    {
        return $this->alreadySubmittedCount;
    }

    public function getMissingConfigCount(): int
    {
        return $this->missingConfigCount;
    }

    /** @return string[] */
    public function getMissingConfigStoreNames(): array
    {
        return $this->missingConfigStoreNames;
    }
}
