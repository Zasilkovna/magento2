<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit;

use Packetery\Checkout\Model\Packet\TrackingUrlFactory;
use PHPUnit\Framework\TestCase;

class TrackingUrlFactoryTest extends TestCase
{
    public function testFormatNumberWithBarcodePrefix(): void
    {
        $this->assertSame(
            'Z1234567890',
            (new TrackingUrlFactory())->formatNumber('1234567890')
        );
    }

    public function testCreateUrlFromBarcode(): void
    {
        $this->assertSame(
            'https://tracking.packeta.com/Z1234567890',
            (new TrackingUrlFactory())->create('1234567890')
        );
    }
}
