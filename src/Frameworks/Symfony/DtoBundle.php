<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Symfony;

use event4u\DataHelpers\Frameworks\Symfony\Serializer\DtoNormalizer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * Symfony Bundle for Dto integration.
 *
 * Registers:
 * - DtoValueResolver for automatic controller injection
 * - DtoNormalizer for Symfony Serializer (automatic DTO serialization with DateTimeFormat support)
 *
 * Usage:
 * Add to config/bundles.php:
 * ```php
 * return [
 *     // ...
 *     event4u\DataHelpers\Symfony\DtoBundle::class => ['all' => true],
 * ];
 * ```
 *
 * Note: When using Symfony Flex, the DtoNormalizer is also registered via the recipe
 * in config/services/data_helpers.yaml for better visibility and customization.
 */
class DtoBundle extends AbstractBundle
{
    /** @param array<string, mixed> $config */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Register DtoValueResolver
        /** @phpstan-ignore-next-line */
        $container->services()
            ->set('event4u.data_helpers.dto_value_resolver', DtoValueResolver::class)
            ->args([
                service('validator')->nullOnInvalid(),
            ])
            ->tag('controller.argument_value_resolver', ['priority' => 100]);

        // Register DtoNormalizer for Symfony Serializer
        /** @phpstan-ignore-next-line */
        $container->services()
            ->set('event4u.data_helpers.dto_normalizer', DtoNormalizer::class)
            ->tag('serializer.normalizer', ['priority' => 64]);
    }
}
