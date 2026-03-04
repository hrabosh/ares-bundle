<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\DTO;

final readonly class AresError
{
    public function __construct(
        public ?string $code,
        public ?string $subCode,
        public ?string $description,
        /** @var array<string, mixed>|null */
        public ?array $raw = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array {
        return [
            'code' => $this->code,
            'subCode' => $this->subCode,
            'description' => $this->description,
            'raw' => $this->raw,
        ];
    }
}
