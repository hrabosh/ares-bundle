<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\DTO;

use Hrabo\AresBundle\Enum\Dataset;
use Hrabo\AresBundle\Enum\DatasetStatus;

final readonly class DatasetResult {
    /**
     * @param array<string, mixed>|null $raw
     */
    public function __construct(
        public Dataset $dataset,
        public DatasetStatus $status,
        public int $httpStatus,
        public float $latencyMs,
        public ?NormalizedCompany $company = NULL,
        public ?AresError $error = NULL,
        public ?array $raw = NULL,
    ) {
    }

    public function isOk(): bool {
        return $this->status === DatasetStatus::OK;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array {
        return [
            'dataset' => $this->dataset->value,
            'status' => $this->status->value,
            'httpStatus' => $this->httpStatus,
            'latencyMs' => $this->latencyMs,
            'company' => $this->company?->toArray(),
            'error' => $this->error?->toArray(),
            'raw' => $this->raw,
        ];
    }
}
