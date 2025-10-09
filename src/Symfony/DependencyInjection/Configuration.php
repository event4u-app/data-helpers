<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('data_helpers');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('driver')
                            ->values(['memory', 'symfony', 'null'])
                            ->defaultValue('symfony')
                            ->info('Cache driver: memory, symfony, null')
                        ->end()
                        ->integerNode('max_entries')
                            ->defaultValue(1000)
                            ->info('Maximum number of cache entries. Set to 0 to disable caching.')
                        ->end()
                        ->scalarNode('default_ttl')
                            ->defaultNull()
                            ->info('Default TTL in seconds. Set to null for no expiration.')
                        ->end()
                        ->arrayNode('symfony')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('pool')->defaultValue('@cache.app')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->enumNode('performance_mode')
                    ->values(['fast', 'safe'])
                    ->defaultValue('fast')
                    ->info('Performance mode: fast (no escape handling) or safe (full escape handling)')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
