<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit;

use Packetery\Checkout\Model\Packet\PacketAttributes;
use PHPUnit\Framework\TestCase;

class PacketAttributesTest extends TestCase
{
    public function testWithSizeStoresDimensionsAsArray(): void
    {
        $size = [
            'length' => 150,
            'width' => 200,
            'height' => 50,
        ];

        $attributes = (new PacketAttributes())
            ->withSize(
                $size['length'],
                $size['width'],
                $size['height']
            );

        $this->assertSame($size, $attributes->toArray()['size']);
    }

    public function testWithSizeKeepsOriginalUntouched(): void
    {
        $original = new PacketAttributes();
        $modified = $original
            ->withSize(10, 20, 30);

        $this->assertArrayNotHasKey('size', $original->toArray());
        $this->assertNotSame($original, $modified);
    }

    public function testWithAdultContentStoresBool(): void
    {
        $attributes = (new PacketAttributes())
            ->withAdultContent(true);

        $this->assertTrue($attributes->toArray()['adultContent']);
    }

    public function testWithAdultContentKeepsOriginalUntouched(): void
    {
        $original = new PacketAttributes();
        $modified = $original
            ->withAdultContent(true);

        $this->assertArrayNotHasKey('adultContent', $original->toArray());
        $this->assertNotSame($original, $modified);
    }

    public function testMultipleKeepAllValues(): void
    {
        $weight = 1.5;
        $size = [
            'length' => 10,
            'width' => 20,
            'height' => 30,
        ];

        $data = (new PacketAttributes())
            ->withWeight($weight)
            ->withSize(
                $size['length'],
                $size['width'],
                $size['height']
            )
            ->withAdultContent(true)
            ->toArray();

        $this->assertSame($weight, $data['weight']);
        $this->assertSame($size, $data['size']);
        $this->assertTrue($data['adultContent']);
    }
}
