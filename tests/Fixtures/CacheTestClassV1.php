<?php

declare(strict_types=1);

namespace Tests\Fixtures;

/**
 * Test class for cache validation - Version 1.
 */
class CacheTestClassV1
{
    public function process(string $input): string
    {
        return strtoupper($input);
    }

    public function getVersion(): int
    {
        return 1;
    }
}

