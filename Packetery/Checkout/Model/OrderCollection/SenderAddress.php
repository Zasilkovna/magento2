<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\OrderCollection;

class SenderAddress
{
    private string $name;

    private string $street;

    private string $zip;

    private string $city;

    private string $countryCode;

    public function __construct(string $name, string $street, string $zip, string $city, string $countryCode)
    {
        $this->name = $name;
        $this->street = $street;
        $this->zip = $zip;
        $this->city = $city;
        $this->countryCode = $countryCode;
    }

    public function getName(): string
    {
        return $this->name;
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
