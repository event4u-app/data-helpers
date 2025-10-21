<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Symfony;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * Symfony Bundle for DTO integration.
 *
 * Registers the DTOValueResolver for automatic controller injection.
 *
 * Usage:
 * Add to config/bundles.php:
 * ```php
 * return [
 *     // ...
 *     event4u\DataHelpers\Symfony\DTOBundle::class => ['all' => true],
 * ];
 * ```
 */
class DTOBundle extends AbstractBundle
{
    /** @param array<string, mixed> $config */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Register DTOValueResolver
        /** @phpstan-ignore-next-line */
        $container->services()
            ->set('event4u.data_helpers.dto_value_resolver', DTOValueResolver::class)
            ->args([
                service('validator')->nullOnInvalid(),
            ])
            ->tag('controller.argument_value_resolver', ['priority' => 100]);
    }
}

