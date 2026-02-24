<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\RateLimit;

final class AresRateLimitExceededException extends \RuntimeException
{
    public function __construct(
        public readonly ?\DateTimeImmutable $retryAt = null,
        string $message = 'ARES outgoing rate limit exceeded.',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
