<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\DTO;

final readonly class Address {
    public function __construct(
        public ?string $text = NULL,
        public ?string $street = NULL,
        public ?string $houseNumber = NULL,
        public ?string $orientationNumber = NULL,
        public ?string $orientationNumberLetter = NULL,
        public ?string $cityPart = NULL,
        public ?string $city = NULL,
        public ?string $postalCode = NULL,
        public ?string $countryCode = NULL,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array {
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
