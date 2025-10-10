<?php

declare(strict_types=1);

namespace Tests\Fixtures;

/**
 * Test class for cache validation - Version 2 (modified).
 */
class CacheTestClassV2
{
    public function process(string $input): string
    {
        // Changed implementation
        return strtolower($input);
    }

    public function getVersion(): int
    {
        return 2;
    }

    // Added new method
    public function newMethod(): string
    {
        return 'new';
    }
}

