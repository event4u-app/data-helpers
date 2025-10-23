<?php

declare(strict_types=1);

namespace App;

use event4u\DataHelpers\Frameworks\Symfony\DataHelpersBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DataHelpersBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) use ($loader): void {
            // Load framework configuration
            $loader->load($this->getProjectDir() . '/config/packages/framework.yaml');

            // Load data_helpers configuration if it exists (created by recipe)
            $dataHelpersConfig = $this->getProjectDir() . '/config/packages/data_helpers.yaml';
            if (file_exists($dataHelpersConfig)) {
                $loader->load($dataHelpersConfig);
            }
        });
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__);
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir() . '/var/log';
    }
}
