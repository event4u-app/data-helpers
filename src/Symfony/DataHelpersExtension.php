<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Symfony;

use event4u\DataHelpers\DataHelpersConfig;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Symfony Extension for Data Helpers.
 *
 * This extension handles:
 * - Configuration loading and validation
 * - Service registration
 * - DataHelpersConfig initialization
 */
final class DataHelpersExtension extends Extension implements ConfigurationInterface
{
    /** @param array<int, mixed> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Load services (if file exists)
        $servicesPath = __DIR__ . '/../../config/symfony/services.yaml';
        if (file_exists($servicesPath)) {
            $loader = new YamlFileLoader(
                $container,
                new FileLocator(__DIR__ . '/../../config/symfony')
            );
            $loader->load('services.yaml');
        }

        // Process configuration
        $config = $this->processConfiguration($this, $configs);

        // Initialize DataHelpersConfig
        DataHelpersConfig::initialize($config);

        // Set parameters for use in services
        $container->setParameter('data_helpers.cache.max_entries', $config['cache']['max_entries']);
        $container->setParameter('data_helpers.performance_mode', $config['performance_mode']);

        // Register config file path for copying
        $container->setParameter('data_helpers.config_path', __DIR__ . '/../../config/symfony/data_helpers.yaml');
    }

    public function getAlias(): string
    {
        return 'data_helpers';
    }

    /** Get the path to the configuration file that should be copied. */
    public function getConfigPath(): string
    {
        return __DIR__ . '/../../config/symfony/data_helpers.yaml';
    }

    /** Configuration tree builder. */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('data_helpers');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('driver')
                            ->values(['memory', 'framework', 'none'])
                            ->defaultValue('framework')
                            ->info('Cache driver: memory, framework, none')
                        ->end()
                        ->integerNode('max_entries')
                            ->defaultValue(1000)
                            ->info('Maximum number of cache entries. Set to 0 to disable caching.')
                        ->end()
                        ->scalarNode('default_ttl')
                            ->defaultValue(3600)
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
