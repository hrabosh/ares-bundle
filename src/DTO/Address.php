<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\DTO;

final readonly class Address
{
    public function __construct(
        public ?string $text = null,
        public ?string $street = null,
        public ?string $houseNumber = null,
        public ?string $orientationNumber = null,
        public ?string $orientationNumberLetter = null,
        public ?string $cityPart = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $countryCode = null,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'street' => $this->street,
            'houseNumber' => $this->houseNumber,
            'orientationNumber' => $this->orientationNumber,
            'orientationNumberLetter' => $this->orientationNumberLetter,
            'cityPart' => $this->cityPart,
            'city' => $this->city,
            'postalCode' => $this->postalCode,
            'countryCode' => $this->countryCode,
        ];
    }
}
