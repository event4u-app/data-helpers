<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Symfony;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use function dirname;

/**
 * Symfony Bundle for Data Helpers.
 *
 * This bundle provides automatic configuration and service registration
 * for the Data Helpers package in Symfony applications.
 */
final class DataHelpersBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__, 3);
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new DataHelpersExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Load command services
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/Resources/config')
        );
        $loader->load('services.yaml');
    }
}

