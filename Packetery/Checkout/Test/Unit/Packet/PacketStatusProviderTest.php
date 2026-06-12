<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Packet;

class PacketStatusProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return array<string, array{string, bool}>
     */
    public static function isFinalProvider(): array
    {
        return [
            'delivered is final' => [\Packetery\Checkout\Model\Packet\PacketStatus::DELIVERED, true],
            'returned is final' => [\Packetery\Checkout\Model\Packet\PacketStatus::RETURNED, true],
            'cancelled is final' => [\Packetery\Checkout\Model\Packet\PacketStatus::CANCELLED, true],
            'unknown is final' => [\Packetery\Checkout\Model\Packet\PacketStatus::UNKNOWN, true],
            'received data is not final' => [\Packetery\Checkout\Model\Packet\PacketStatus::RECEIVED_DATA, false],
            'delivery attempt is not final' => [\Packetery\Checkout\Model\Packet\PacketStatus::DELIVERY_ATTEMPT, false],
            'unlisted code is not final' => ['some future code', false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('isFinalProvider')]
    public function testIsFinal(string $codeText, bool $expected): void
    {
        $this->assertSame($expected, (new \Packetery\Checkout\Model\Packet\PacketStatusProvider())->isFinal($codeText));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function translatedNameProvider(): array
    {
        return [
            'known delivered' => [\Packetery\Checkout\Model\Packet\PacketStatus::DELIVERED, 'Delivered'],
            'known received data' => [\Packetery\Checkout\Model\Packet\PacketStatus::RECEIVED_DATA, 'Awaiting consignment'],
            'unlisted falls back to code text' => ['some future code', 'some future code'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('translatedNameProvider')]
    public function testGetTranslatedName(string $codeText, string $expected): void
    {
        $this->assertSame($expected, (string) (new \Packetery\Checkout\Model\Packet\PacketStatusProvider())->getTranslatedName($codeText));
    }

    public function testGetFinalCodeTextsReturnsExactlyFinalStatuses(): void
    {
        $finalCodeTexts = (new \Packetery\Checkout\Model\Packet\PacketStatusProvider())->getFinalCodeTexts();
        sort($finalCodeTexts);

        $expected = [
            \Packetery\Checkout\Model\Packet\PacketStatus::DELIVERED,
            \Packetery\Checkout\Model\Packet\PacketStatus::RETURNED,
            \Packetery\Checkout\Model\Packet\PacketStatus::CANCELLED,
            \Packetery\Checkout\Model\Packet\PacketStatus::UNKNOWN,
        ];
        sort($expected);

        $this->assertSame($expected, $finalCodeTexts);
    }
}
