<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\OrderCollection;

class ReceiverAddress
{
    public const FALLBACK_COUNTRY = 'CZ';

    private const ADDRESS_MAP = [
        'CZ' => [
            'company' => 'Zásilkovna s.r.o.',
            'street' => 'Českomoravská 2408/1a',
            'zip' => '190 00',
            'city' => 'Praha 9',
        ],
        'SK' => [
            'company' => 'Packeta Slovakia s. r. o.',
            'street' => 'Sliačska 1E',
            'zip' => '831 02',
            'city' => 'Bratislava',
        ],
        'HU' => [
            'company' => 'Packeta Hungary Kft.',
            'street' => 'Ezred utca 1-3. B2/11',
            'zip' => '1044',
            'city' => 'Budapest',
        ],
        'RO' => [
            'company' => 'Packeta Romania s.r.l.',
            'street' => 'Strada Călușei 21A, parter',
            'zip' => '021351',
            'city' => 'București, Sector 2',
        ],
        'PL' => [
            'company' => 'Packeta Poland Sp. z o.o.',
            'street' => 'ul. Postępu 14',
            'zip' => '02-676',
            'city' => 'Warszawa',
        ],
    ];

    private string $company;

    private string $street;

    private string $zip;

    private string $city;

    private string $countryCode;

    public function __construct(string $company, string $street, string $zip, string $city, string $countryCode)
    {
        $this->company = $company;
        $this->street = $street;
        $this->zip = $zip;
        $this->city = $city;
        $this->countryCode = $countryCode;
    }

    public static function forCountry(string $countryCode): self
    {
        $code = strtoupper($countryCode);
        $address = self::ADDRESS_MAP[$code] ?? self::ADDRESS_MAP[self::FALLBACK_COUNTRY];
        $resolvedCode = isset(self::ADDRESS_MAP[$code]) ? $code : self::FALLBACK_COUNTRY;

        return new self(
            $address['company'],
            $address['street'],
            $address['zip'],
            $address['city'],
            $resolvedCode
        );
    }

    public function getCompany(): string
    {
        return $this->company;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }
}
