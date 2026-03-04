<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\DTO;

use Hrabo\AresBundle\Enum\Dataset;

/**
 * Aggregate result of calling multiple ARES datasets for the same IČO.
 */
final readonly class CompanyLookupResult
{
    /**
     * @param array<string, DatasetResult> $datasets Map of dataset code => DatasetResult
     */
    public function __construct(
        public string $icoCanonical,
        public ?NormalizedCompany $company,
        public array $datasets,
        public \DateTimeImmutable $fetchedAt,
    ) {
    }

    public function getDataset(Dataset $dataset): ?DatasetResult {
        return $this->datasets[$dataset->value] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array {
        $datasets = [];
        foreach ($this->datasets as $code => $result) {
            $datasets[$code] = $result->toArray();
        }

        return [
            'ico' => $this->icoCanonical,
            'company' => $this->company?->toArray(),
            'datasets' => $datasets,
            'fetchedAt' => $this->fetchedAt->format(DATE_ATOM),
        ];
    }
}
