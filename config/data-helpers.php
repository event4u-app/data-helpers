<?php

declare(strict_types=1);

/**
 * Data Helpers Configuration
 *
 * This file only contains commonly changed settings.
 * All other settings use package defaults.
 *
 * For full configuration options, see:
 * vendor/event4u/data-helpers/config/data-helpers.php
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Mode
    |--------------------------------------------------------------------------
    |
    | Fast mode uses simplified parsing without escape sequence handling.
    | Safe mode processes all escape sequences (\n, \t, \", \\, etc.).
    |
    | Options: 'fast', 'safe'
    | Default: 'fast'
    |
    */
    'performance_mode' => env('DATA_HELPERS_PERFORMANCE_MODE', 'fast'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        // Cache directory path
        'path' => env('DATA_HELPERS_CACHE_PATH', './.event4u/data-helpers/cache/'),

        // Cache driver: 'auto', 'filesystem', 'laravel', 'symfony', 'none'
        'driver' => env('DATA_HELPERS_CACHE_DRIVER', 'auto'),

        // Cache TTL in seconds (null = forever)
        'ttl' => env('DATA_HELPERS_CACHE_TTL', null),

        // Enable code generation for optimized performance
        'code_generation' => env('DATA_HELPERS_CODE_GENERATION', true),

        // Cache invalidation: 'manual', 'mtime', 'hash', 'both'
        'invalidation' => env('DATA_HELPERS_CACHE_INVALIDATION', 'mtime'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        // Enable logging
        'enabled' => env('DATA_HELPERS_LOG_ENABLED', false),

        // Logger driver: 'filesystem', 'framework', 'none'
        'driver' => env('DATA_HELPERS_LOG_DRIVER', 'filesystem'),

        // Log path (for filesystem driver)
        'path' => env('DATA_HELPERS_LOG_PATH', './storage/logs/'),

        // Minimum log level: 'debug', 'info', 'warning', 'error'
        'level' => env('DATA_HELPERS_LOG_LEVEL', 'info'),
    ],
];
