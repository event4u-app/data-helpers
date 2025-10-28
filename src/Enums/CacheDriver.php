<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Enums;

/**
 * Available cache drivers for metadata and code generation caching.
 *
 * - none: Disable caching completely (not recommended for production)
 * - auto: Automatically detect Laravel/Symfony cache, fallback to filesystem
 * - laravel: Use Laravel Cache (requires Laravel)
 * - symfony: Use Symfony Cache (requires Symfony)
 * - filesystem: Use filesystem cache (always available)
 */
enum CacheDriver: string
{
    case NONE = 'none';
    case AUTO = 'auto';
    case LARAVEL = 'laravel';
    case SYMFONY = 'symfony';
    case FILESYSTEM = 'filesystem';
}
