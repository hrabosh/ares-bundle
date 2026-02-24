<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Lustrace\AresBundle\Client\AresClient;
use Lustrace\AresBundle\Client\AresClientInterface;
use Lustrace\AresBundle\Client\AresClientOptions;
use Lustrace\AresBundle\Normalizer\AresCompanyNormalizer;
use Lustrace\AresBundle\RateLimit\AresRateLimiter;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    $services->set(AresClientOptions::class)
        ->args([
            param('lustrace_ares.base_uri'),
            param('lustrace_ares.timeout_seconds'),
            param('lustrace_ares.datasets'),
        ]);

    // Base scoped HTTP client for ARES
    $services->set('lustrace_ares.http_client_base', HttpClientInterface::class)
        ->factory([service('http_client'), 'withOptions'])
        ->args([[
            'base_uri' => param('lustrace_ares.base_uri'),
            'timeout' => param('lustrace_ares.timeout_seconds'),
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'LustraceAresBundle/0.1',
            ],
        ]]);

    // Retry strategy + retryable client
    $services->set('lustrace_ares.retry_strategy', GenericRetryStrategy::class)
        ->args([
            param('lustrace_ares.retry.status_codes'),
            param('lustrace_ares.retry.delay_ms'),
            param('lustrace_ares.retry.multiplier'),
            param('lustrace_ares.retry.max_delay_ms'),
            param('lustrace_ares.retry.jitter'),
        ]);

    $services->set('lustrace_ares.http_client', RetryableHttpClient::class)
        ->args([
            service('lustrace_ares.http_client_base'),
            service('lustrace_ares.retry_strategy'),
            param('lustrace_ares.retry.max_retries'),
            service('logger')->nullOnInvalid(),
        ]);

    // Rate limiting for outgoing requests (token bucket)
    $services->set('lustrace_ares.rate_limiter_storage', CacheStorage::class)
        ->args([service('cache.app')]);

    $services->set('lustrace_ares.rate_limiter_factory', RateLimiterFactory::class)
        ->args([
            [
                'id' => 'lustrace_ares_outgoing',
                'policy' => 'token_bucket',
                // bucket size (burst)
                'limit' => param('lustrace_ares.rate_limit.burst'),
                // refill rate
                'rate' => [
                    'interval' => param('lustrace_ares.rate_limit.interval'),
                    'amount' => param('lustrace_ares.rate_limit.limit'),
                ],
            ],
            service('lustrace_ares.rate_limiter_storage'),
        ]);

    $services->set(AresRateLimiter::class)
        ->args([
            param('lustrace_ares.rate_limit.enabled'),
            param('lustrace_ares.rate_limit.wait'),
            param('lustrace_ares.rate_limit.key'),
            service('lustrace_ares.rate_limiter_factory'),
        ]);

    $services->set(AresCompanyNormalizer::class);

    $services->set(AresClient::class)
        ->arg('$httpClient', service('lustrace_ares.http_client'));

    $services->alias(AresClientInterface::class, AresClient::class);
};
