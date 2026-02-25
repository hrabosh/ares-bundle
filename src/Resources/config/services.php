<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Hrabo\AresBundle\Client\AresClient;
use Hrabo\AresBundle\Client\AresClientInterface;
use Hrabo\AresBundle\Client\AresClientOptions;
use Hrabo\AresBundle\Normalizer\AresCompanyNormalizer;
use Hrabo\AresBundle\RateLimit\AresRateLimiter;
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
            param('hrabo_ares.base_uri'),
            param('hrabo_ares.timeout_seconds'),
            param('hrabo_ares.datasets'),
        ]);

    $services->set('hrabo_ares.http_client_base', HttpClientInterface::class)
        ->factory([service('http_client'), 'withOptions'])
        ->args([[
            'base_uri' => param('hrabo_ares.base_uri'),
            'timeout' => param('hrabo_ares.timeout_seconds'),
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'LustraceAresBundle/0.1',
            ],
        ]]);

    $services->set('hrabo_ares.retry_strategy', GenericRetryStrategy::class)
        ->args([
            param('hrabo_ares.retry.status_codes'),
            param('hrabo_ares.retry.delay_ms'),
            param('hrabo_ares.retry.multiplier'),
            param('hrabo_ares.retry.max_delay_ms'),
            param('hrabo_ares.retry.jitter'),
        ]);

    $services->set('hrabo_ares.http_client', RetryableHttpClient::class)
        ->args([
            service('hrabo_ares.http_client_base'),
            service('hrabo_ares.retry_strategy'),
            param('hrabo_ares.retry.max_retries'),
            service('logger')->nullOnInvalid(),
        ]);

    $services->set('hrabo_ares.rate_limiter_storage', CacheStorage::class)
        ->args([service('cache.app')]);

    $services->set('hrabo_ares.rate_limiter_factory', RateLimiterFactory::class)
        ->args([
            [
                'id' => 'hrabo_ares_outgoing',
                'policy' => 'token_bucket',
                'limit' => param('hrabo_ares.rate_limit.burst'),
                'rate' => [
                    'interval' => param('hrabo_ares.rate_limit.interval'),
                    'amount' => param('hrabo_ares.rate_limit.limit'),
                ],
            ],
            service('hrabo_ares.rate_limiter_storage'),
        ]);

    $services->set(AresRateLimiter::class)
        ->args([
            param('hrabo_ares.rate_limit.enabled'),
            param('hrabo_ares.rate_limit.wait'),
            param('hrabo_ares.rate_limit.key'),
            service('hrabo_ares.rate_limiter_factory'),
        ]);

    $services->set(AresCompanyNormalizer::class);

    $services->set(AresClient::class)
        ->arg('$httpClient', service('hrabo_ares.http_client'));

    $services->alias(AresClientInterface::class, AresClient::class);
};
