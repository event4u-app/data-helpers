<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap Symfony Application for E2E Tests
|--------------------------------------------------------------------------
|
| This file creates a minimal Symfony kernel for testing
| the Data Helpers package integration with Symfony.
|
*/

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Minimal Symfony Kernel for E2E Tests.
 */
class TestKernel extends Kernel
{
    public function registerBundles(): array
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new event4u\DataHelpers\Symfony\DataHelpersBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function(ContainerBuilder $container): void {
            // Framework configuration
            $container->loadFromExtension('framework', [
                'secret' => 'test-secret',
                'test' => true,
                'cache' => [
                    'app' => 'cache.adapter.array',
                ],
            ]);

            // Data Helpers configuration
            $container->loadFromExtension('data_helpers', [
                'cache' => [
                    'driver' => 'framework',
                    'max_entries' => 1000,
                    'default_ttl' => 3600,
                    'symfony' => [
                        'pool' => '@cache.app',
                    ],
                ],
                'performance_mode' => 'fast',
            ]);
        });
    }

    public function getCacheDir(): string
    {
        return __DIR__ . '/var/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return __DIR__ . '/var/log';
    }
}

return new TestKernel('test', true);
