<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\DependencyInjection;

use Hrabo\AresBundle\Enum\Dataset;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder {
        $treeBuilder = new TreeBuilder('hrabo_ares');
        $root = $treeBuilder->getRootNode();

        /** @var string[] $defaultDatasets */
        $defaultDatasets = array_map(static fn (Dataset $d): string => $d->value, Dataset::companyDatasets());

        $root
            ->children()
                ->scalarNode('base_uri')
                    ->defaultValue('https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/')
                    ->cannotBeEmpty()
                ->end()
                ->floatNode('timeout_seconds')
                    ->defaultValue(10.0)
                    ->min(0.1)
                ->end()
                ->arrayNode('datasets')
                    ->prototype('scalar')->end()
                    ->defaultValue($defaultDatasets)
                ->end()

                ->arrayNode('retry')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_retries')->defaultValue(3)->min(0)->end()
                        ->integerNode('delay_ms')->defaultValue(250)->min(0)->end()
                        ->floatNode('multiplier')->defaultValue(2.0)->min(1.0)->end()
                        ->integerNode('max_delay_ms')->defaultValue(5000)->min(0)->end()
                        ->floatNode('jitter')->defaultValue(0.1)->min(0.0)->max(1.0)->end()
                        ->arrayNode('status_codes')
                            ->prototype('integer')->end()
                            ->defaultValue([0, 429, 500, 502, 503, 504])
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('rate_limit')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->integerNode('limit')->defaultValue(10)->min(1)->end()
                        ->scalarNode('interval')->defaultValue('1 second')->cannotBeEmpty()->end()
                        ->integerNode('burst')->defaultValue(20)->min(1)->end()
                        ->booleanNode('wait')->defaultTrue()->end()
                        ->scalarNode('key')->defaultValue('ares-outgoing')->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
