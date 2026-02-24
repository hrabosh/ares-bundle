<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\RateLimit;

use Symfony\Component\RateLimiter\RateLimiterFactory;

final class AresRateLimiter
{
    public function __construct(
        private readonly bool $enabled,
        private readonly bool $wait,
        private readonly string $key,
        private readonly ?RateLimiterFactory $factory,
    ) {
        if ($this->enabled && null === $this->factory) {
            throw new \InvalidArgumentException('Rate limiter is enabled but RateLimiterFactory is not configured.');
        }
    }

    public function throttle(): void
    {
        if (!$this->enabled) {
            return;
        }

        $limiter = $this->factory->create($this->key);

        if ($this->wait) {
            $limiter->reserve(1)->wait();

            return;
        }

        $rateLimit = $limiter->consume(1);
        if ($rateLimit->isAccepted()) {
            return;
        }

        $retryAt = null;
        if (method_exists($rateLimit, 'getRetryAfter')) {
            /** @var mixed $v */
            $v = $rateLimit->getRetryAfter();
            if ($v instanceof \DateTimeInterface) {
                $retryAt = \DateTimeImmutable::createFromInterface($v);
            }
        }

        throw new AresRateLimitExceededException($retryAt);
    }
}
