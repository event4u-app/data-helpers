<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Data Helpers Configuration (Plain PHP).
|--------------------------------------------------------------------------
|
| This file is used when Laravel or Symfony is not available.
| It reads configuration from environment variables or uses defaults.
*/

/*
|--------------------------------------------------------------------------
| Template Expression Cache
|--------------------------------------------------------------------------
|
| The template expression parser can cache parsed expressions to improve
| performance. This setting controls the maximum number of cache entries.
| When the limit is reached, the oldest entries will be discarded (LRU).
|
| Set to 0 to disable caching.
| Recommended: 1000 for most applications.
|
*/
return [
    'cache' => [
        'driver' => $_ENV['DATA_HELPERS_CACHE_DRIVER'] ?? 'memory',
        'max_entries' => (int)($_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES'] ?? 1000),
        'prefix' => $_ENV['DATA_HELPERS_CACHE_PREFIX'] ?? 'data_helpers:',
        'default_ttl' => (int)($_ENV['DATA_HELPERS_CACHE_DEFAULT_TTL'] ?? 3600),
    ],
    'performance_mode' => $_ENV['DATA_HELPERS_PERFORMANCE_MODE'] ?? 'fast',
];

