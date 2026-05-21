<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\OrderCollection;

use Packetery\Checkout\Model\OrderCollection\ReceiverAddress;

class ReceiverAddressTest extends \Packetery\Checkout\Test\BaseTest
{
    /**
     * @dataProvider forCountryProvider
     */
    public function testForCountry(
        string $input,
        string $expectedCountryCode,
        string $expectedCompany,
        string $expectedStreet,
        string $expectedZip,
        string $expectedCity
    ): void {
        $address = ReceiverAddress::forCountry($input);

        $this->assertSame($expectedCountryCode, $address->getCountryCode());
        $this->assertSame($expectedCompany, $address->getCompany());
        $this->assertSame($expectedStreet, $address->getStreet());
        $this->assertSame($expectedZip, $address->getZip());
        $this->assertSame($expectedCity, $address->getCity());
    }

    public static function forCountryProvider(): array
    {
        return [
            'CZ exact' => ['CZ', 'CZ', 'Zásilkovna s.r.o.', 'Českomoravská 2408/1a', '190 00', 'Praha 9'],
            'SK exact' => ['SK', 'SK', 'Packeta Slovakia s. r. o.', 'Sliačska 1E', '831 02', 'Bratislava'],
            'HU exact' => ['HU', 'HU', 'Packeta Hungary Kft.', 'Ezred utca 1-3. B2/11', '1044', 'Budapest'],
            'RO exact' => ['RO', 'RO', 'Packeta Romania s.r.l.', 'Strada Călușei 21A, parter', '021351', 'București, Sector 2'],
            'PL exact' => ['PL', 'PL', 'Packeta Poland Sp. z o.o.', 'ul. Postępu 14', '02-676', 'Warszawa'],
            'lowercase normalized' => ['cz', 'CZ', 'Zásilkovna s.r.o.', 'Českomoravská 2408/1a', '190 00', 'Praha 9'],
            'unknown country falls back to CZ' => ['DE', 'CZ', 'Zásilkovna s.r.o.', 'Českomoravská 2408/1a', '190 00', 'Praha 9'],
            'empty falls back to CZ' => ['', 'CZ', 'Zásilkovna s.r.o.', 'Českomoravská 2408/1a', '190 00', 'Praha 9'],
        ];
    }
}
