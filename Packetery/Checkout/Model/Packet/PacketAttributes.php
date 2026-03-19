<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

class PacketAttributes
{
    /** @var array<string, string|int|float> */
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function withNumber(string $number): self
    {
        $clone = clone $this;
        $clone->data['number'] = $number;
        return $clone;
    }

    public function withName(string $name): self
    {
        $clone = clone $this;
        $clone->data['name'] = $name;
        return $clone;
    }

    public function withSurname(string $surname): self
    {
        $clone = clone $this;
        $clone->data['surname'] = $surname;
        return $clone;
    }

    public function withCompany(string $company): self
    {
        $clone = clone $this;
        $clone->data['company'] = $company;
        return $clone;
    }

    public function withEmail(string $email): self
    {
        $clone = clone $this;
        $clone->data['email'] = $email;
        return $clone;
    }

    public function withPhone(string $phone): self
    {
        $clone = clone $this;
        $clone->data['phone'] = $phone;
        return $clone;
    }

    public function withAddressId(int $addressId): self
    {
        $clone = clone $this;
        $clone->data['addressId'] = $addressId;
        return $clone;
    }

    public function withValue(float $value): self
    {
        $clone = clone $this;
        $clone->data['value'] = $value;
        return $clone;
    }

    public function withCod(float $cod): self
    {
        $clone = clone $this;
        $clone->data['cod'] = $cod;
        return $clone;
    }

    public function withCurrency(string $currency): self
    {
        $clone = clone $this;
        $clone->data['currency'] = $currency;
        return $clone;
    }

    public function withWeight(float $weight): self
    {
        $clone = clone $this;
        $clone->data['weight'] = $weight;
        return $clone;
    }

    public function withEshop(string $eshop): self
    {
        $clone = clone $this;
        $clone->data['eshop'] = $eshop;
        return $clone;
    }

    public function withCarrierPickupPoint(string $carrierPickupPoint): self
    {
        $clone = clone $this;
        $clone->data['carrierPickupPoint'] = $carrierPickupPoint;
        return $clone;
    }

    public function withStreet(string $street): self
    {
        $clone = clone $this;
        $clone->data['street'] = $street;
        return $clone;
    }

    public function withHouseNumber(string $houseNumber): self
    {
        $clone = clone $this;
        $clone->data['houseNumber'] = $houseNumber;
        return $clone;
    }

    public function withCity(string $city): self
    {
        $clone = clone $this;
        $clone->data['city'] = $city;
        return $clone;
    }

    public function withZip(string $zip): self
    {
        $clone = clone $this;
        $clone->data['zip'] = $zip;
        return $clone;
    }

    /**
     * @return array<string, string|int|float>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
