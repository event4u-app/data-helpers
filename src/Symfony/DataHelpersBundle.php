<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Symfony;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
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
        return dirname(__DIR__, 2);
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new DataHelpersExtension();
    }
}

