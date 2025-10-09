<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Data Helpers Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the event4u Data Helpers package.
    |
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
    'cache' => [
        /*
        |--------------------------------------------------------------------------
        | Cache Driver
        |--------------------------------------------------------------------------
        |
        | The cache driver to use for storing parsed expressions and other data.
        |
        | Supported: 'memory', 'laravel', 'null'
        |
        | - memory: In-memory LRU cache (default, fast, no persistence)
        | - laravel: Laravel cache system (uses your configured cache driver)
        | - null: No caching (for testing/debugging)
        |
        */
        'driver' => env('DATA_HELPERS_CACHE_DRIVER', 'laravel'),

        /*
        |--------------------------------------------------------------------------
        | Maximum Cache Entries (Memory Driver)
        |--------------------------------------------------------------------------
        |
        | Maximum number of cache entries for the memory driver.
        | When the limit is reached, the oldest entries will be discarded (LRU).
        |
        */
        'max_entries' => (int)env('DATA_HELPERS_CACHE_MAX_ENTRIES', 1000),

        /*
        |--------------------------------------------------------------------------
        | Cache Key Prefix
        |--------------------------------------------------------------------------
        |
        | Prefix for cache keys (used by laravel driver).
        |
        */
        'prefix' => env('DATA_HELPERS_CACHE_PREFIX', 'data_helpers:'),

        /*
        |--------------------------------------------------------------------------
        | Default TTL (Time To Live)
        |--------------------------------------------------------------------------
        |
        | Default time to live in seconds for cache entries.
        | Set to null for no expiration (cache forever).
        | Can be overridden per cache entry when calling set().
        |
        | Examples:
        | - 3600 = 1 hour
        | - 86400 = 24 hours
        | - null = forever
        |
        */
        'default_ttl' => (int)env('DATA_HELPERS_CACHE_DEFAULT_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Mode
    |--------------------------------------------------------------------------
    |
    | Fast mode uses simplified parsing without escape sequence handling.
    | Safe mode processes all escape sequences (\n, \t, \", \\, etc.).
    |
    | Options: 'fast', 'safe'
    | Default: 'fast' (recommended for most use cases)
    |
    */
    'performance_mode' => env('DATA_HELPERS_PERFORMANCE_MODE', 'fast'),
];
