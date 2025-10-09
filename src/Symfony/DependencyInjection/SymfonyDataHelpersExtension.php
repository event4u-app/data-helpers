<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Symfony\DependencyInjection;

use event4u\DataHelpers\DataHelpersConfig;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SymfonyDataHelpersExtension extends Extension
{
    /** @param array<int, mixed> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Load services (if file exists)
        $servicesPath = __DIR__ . '/../../../config/symfony/services.yaml';
        if (file_exists($servicesPath)) {
            $loader = new YamlFileLoader(
                $container,
                new FileLocator(__DIR__ . '/../../../config/symfony')
            );
            $loader->load('services.yaml');
        }

        // Process configuration
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Initialize DataHelpersConfig
        DataHelpersConfig::initialize($config);

        // Set parameters for use in services
        $container->setParameter('data_helpers.cache.max_entries', $config['cache']['max_entries']);
        $container->setParameter('data_helpers.performance_mode', $config['performance_mode']);

        // Register config file path for copying
        $container->setParameter('data_helpers.config_path', __DIR__ . '/../../../config/symfony/data_helpers.yaml');
    }

    public function getAlias(): string
    {
        return 'data_helpers';
    }

    /** Get the path to the configuration file that should be copied. */
    public function getConfigPath(): string
    {
        return __DIR__ . '/../../../config/symfony/data_helpers.yaml';
    }
}

