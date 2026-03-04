<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\Tests;

use Hrabo\AresBundle\RateLimit\AresRateLimitExceededException;
use Hrabo\AresBundle\RateLimit\AresRateLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class AresRateLimiterTest extends TestCase
{
    public function testThrottleNoopWhenDisabled(): void {
        $limiter = new AresRateLimiter(
            enabled: false,
            wait: false,
            key: 'test',
            factory: null,
        );

        $limiter->throttle();

        self::assertTrue(true);
    }

    public function testThrottleThrowsWhenRejectedAndWaitIsFalse(): void {
        $factory = new RateLimiterFactory(
            [
                'id' => 'test',
                'policy' => 'token_bucket',
                'limit' => 1,
                'rate' => [
                    // long interval prevents refill during test
                    'interval' => '1 hour',
                    'amount' => 1,
                ],
            ],
            new InMemoryStorage(),
        );

        $aresLimiter = new AresRateLimiter(
            enabled: true,
            wait: false,
            key: 'ares-outgoing',
            factory: $factory,
        );

        // 1 token in bucket is accepted
        $aresLimiter->throttle();

        // next one should be rejected
        $this->expectException(AresRateLimitExceededException::class);
        $aresLimiter->throttle();
    }

    public function testThrottleDoesNotThrowWhenWaitIsTrueAndTokensAvailable(): void {
        $factory = new RateLimiterFactory(
            [
                'id' => 'test',
                'policy' => 'token_bucket',
                'limit' => 10,
                'rate' => [
                    'interval' => '1 second',
                    'amount' => 10,
                ],
            ],
            new InMemoryStorage(),
        );

        $aresLimiter = new AresRateLimiter(
            enabled: true,
            wait: true,
            key: 'ares-outgoing',
            factory: $factory,
        );

        $aresLimiter->throttle();

        self::assertTrue(true);
    }
}
