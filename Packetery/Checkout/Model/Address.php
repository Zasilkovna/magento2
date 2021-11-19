<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

class Address
{
    /** @var string|null */
    private $street;

    /** @var string|null */
    private $houseNumber;

    /** @var string|null */
    private $city;

    /** @var string|null */
    private $zip;

    /** @var string|null */
    private $countryId;

    /** @var string|null */
    private $county;

    /** @var string|null */
    private $longitude;

    /** @var string|null */
    private $latitude;

    public static function fromValidatedAddress($validatedAddress): self {
        $address = new self();
        $address->setStreet(implode(' ', $validatedAddress->street));
        $address->setHouseNumber($validatedAddress->houseNumber);
        $address->setCity($validatedAddress->city);
        $address->setZip($validatedAddress->postcode);
        $address->setCountryId($validatedAddress->countryId);
        $address->setCounty($validatedAddress->county);
        $address->setLongitude($validatedAddress->longitude);
        $address->setLatitude($validatedAddress->latitude);
        return $address;
    }

    public static function fromShippingAddress(\Magento\Sales\Model\Order\Address $shippingAddress): self {
        $streetMatches = [];
        $match = preg_match('/^(.*[^0-9]+) (([1-9][0-9]*)\/)?([1-9][0-9]*[a-cA-C]?)$/', $shippingAddress->getStreet()[0], $streetMatches);

        if (!$match) {
            $houseNumber = null;
            $street = $shippingAddress->getStreet()[0];
        } elseif (!isset($streetMatches[4])) {
            $houseNumber = null;
            $street = $streetMatches[1];
        } else {
            $houseNumber = (!empty($streetMatches[3])) ? $streetMatches[3] . "/" . $streetMatches[4] : $streetMatches[4];
            $street = $streetMatches[1];
        }

        $address = new self();
        $address->setStreet($street);
        $address->setHouseNumber($houseNumber);
        $address->setCity($shippingAddress->getCity());
        $address->setZip($shippingAddress->getPostcode());
        $address->setCountryId($shippingAddress->getCountryId());
        return $address;
    }

    public function getStreet(): ?string {
        return $this->street;
    }

    public function setStreet(?string $street): void {
        $this->street = $street;
    }

    public function getHouseNumber(): ?string {
        return $this->houseNumber;
    }

    public function setHouseNumber(?string $houseNumber): void {
        $this->houseNumber = $houseNumber;
    }

    public function getCity(): ?string {
        return $this->city;
    }

    public function setCity(?string $city): void {
        $this->city = $city;
    }

    public function getZip(): ?string {
        return $this->zip;
    }

    public function setZip(?string $zip): void {
        $this->zip = $zip;
    }

    public function getCountryId(): ?string {
        return $this->countryId;
    }

    public function setCountryId(?string $countryId): void {
        $this->countryId = $countryId;
    }

    public function getCounty(): ?string {
        return $this->county;
    }

    public function setCounty(?string $county): void {
        $this->county = $county;
    }

    public function getLongitude(): ?string {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): void {
        $this->longitude = $longitude;
    }

    public function getLatitude(): ?string {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): void {
        $this->latitude = $latitude;
    }
}
