<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\FeatureFlag;

class SplitFlag extends \Magento\Framework\Flag
{
    /**
     * Flag code
     *
     * @var string
     */
    protected $_flagCode = 'packetery_feature_split';

    public function setActive(string $value = '1'): void {
        if (!in_array($value, ['0', '1'])) {
            throw new \InvalidArgumentException();
        }

        $this->setFlagData(
            [
                'active' => $value,
            ] +
            $this->getFlagDataAsArray()
        );
    }

    public function setLastFetchTime(\DateTimeImmutable $time): void {
        $this->setFlagData(
            [
                'lastFetchTime' => $time
                    ->setTimezone(new \DateTimeZone('UTC'))
                    ->format(\DateTimeImmutable::ATOM),
            ] +
            $this->getFlagDataAsArray()
        );
    }

    public function isActive(): bool {
        $data = $this->getFlagDataAsArray();
        if (!isset($data['active'])) {
            return false;
        }

        return $data['active'] === '1';
    }

    public function getLastFetchTime(): ?\DateTimeImmutable {
        $data = $this->getFlagDataAsArray();
        if (!isset($data['lastFetchTime'])) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat(\DateTimeImmutable::ATOM, $data['lastFetchTime'], new \DateTimeZone('UTC'));
    }

    private function getFlagDataAsArray(): array {
        $flagData = $this->getFlagData();
        if (!is_array($flagData)) {
            return [];
        }

        return $flagData;
    }
}
