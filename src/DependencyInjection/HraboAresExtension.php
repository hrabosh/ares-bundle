<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class HraboAresExtension extends Extension {
    public function load(array $configs, ContainerBuilder $container): void {
        /** @var array<string, mixed> $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('hrabo_ares.base_uri', (string) $config['base_uri']);
        $container->setParameter('hrabo_ares.timeout_seconds', (float) $config['timeout_seconds']);
        $container->setParameter('hrabo_ares.datasets', (array) $config['datasets']);

        /** @var array<string, mixed> $retry */
        $retry = (array) $config['retry'];
        $container->setParameter('hrabo_ares.retry.max_retries', (int) $retry['max_retries']);
        $container->setParameter('hrabo_ares.retry.delay_ms', (int) $retry['delay_ms']);
        $container->setParameter('hrabo_ares.retry.multiplier', (float) $retry['multiplier']);
        $container->setParameter('hrabo_ares.retry.max_delay_ms', (int) $retry['max_delay_ms']);
        $container->setParameter('hrabo_ares.retry.jitter', (float) $retry['jitter']);
        $container->setParameter('hrabo_ares.retry.status_codes', (array) $retry['status_codes']);

        /** @var array<string, mixed> $rateLimit */
        $rateLimit = (array) $config['rate_limit'];
        $container->setParameter('hrabo_ares.rate_limit.enabled', (bool) $rateLimit['enabled']);
        $container->setParameter('hrabo_ares.rate_limit.limit', (int) $rateLimit['limit']);
        $container->setParameter('hrabo_ares.rate_limit.interval', (string) $rateLimit['interval']);
        $container->setParameter('hrabo_ares.rate_limit.burst', (int) $rateLimit['burst']);
        $container->setParameter('hrabo_ares.rate_limit.wait', (bool) $rateLimit['wait']);
        $container->setParameter('hrabo_ares.rate_limit.key', (string) $rateLimit['key']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');
    }
}
