<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\DTO;

use Hrabo\AresBundle\Enum\Dataset;

final readonly class NormalizedCompany
{
    public function __construct(
        public string $icoCanonical,
        public Dataset $dataset,
        public ?string $name = null,
        public ?string $vatId = null,
        public ?string $legalFormCode = null,
        public ?Address $address = null,
        public ?\DateTimeImmutable $establishedAt = null,
        public ?\DateTimeImmutable $terminatedAt = null,
        /** @var array<string, mixed> */
        public array $extra = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array {
        return [
            'ico' => $this->icoCanonical,
            'dataset' => $this->dataset->value,
            'name' => $this->name,
            'vatId' => $this->vatId,
            'legalFormCode' => $this->legalFormCode,
            'address' => $this->address?->toArray(),
            'establishedAt' => $this->establishedAt?->format('Y-m-d'),
            'terminatedAt' => $this->terminatedAt?->format('Y-m-d'),
            'extra' => $this->extra,
        ];
    }
}
