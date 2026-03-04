<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\Client;

/**
 * Immutable configuration for ARES client.
 */
final readonly class AresClientOptions
{
    /**
     * @param list<string> $defaultDatasetCodes
     */
    public function __construct(
        public string $baseUri,
        public float $timeoutSeconds,
        public array $defaultDatasetCodes,
    ) {
    }
}
