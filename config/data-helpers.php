<?php

declare(strict_types=1);

use event4u\DataHelpers\Enums\CacheDriver;
use event4u\DataHelpers\Enums\CacheInvalidation;
use event4u\DataHelpers\Helpers\EnvHelper;
use event4u\DataHelpers\Logging\LogEvent;

return [
    /*
    |--------------------------------------------------------------------------
    | Data Helpers Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the event4u Data Helpers package.
    | Works with Laravel, Symfony, and Plain PHP.
    |
    */

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
    'performance_mode' => EnvHelper::string('DATA_HELPERS_PERFORMANCE_MODE', 'fast'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure persistent caching for metadata and generated code.
    | Improves performance by caching reflection analysis and generated code.
    |
    */
    'cache' => [
        /*
        |--------------------------------------------------------------------------
        | Cache Directory
        |--------------------------------------------------------------------------
        |
        | Directory where cache files will be stored.
        | Used for metadata cache and generated code.
        |
        | Default: ./.event4u/data-helpers/cache/
        |
        | Note: This directory is automatically added to .gitignore
        |
        */
        'path' => EnvHelper::string('DATA_HELPERS_CACHE_PATH', './.event4u/data-helpers/cache/'),

        /*
        |--------------------------------------------------------------------------
        | Cache Driver
        |--------------------------------------------------------------------------
        |
        | The cache driver to use for metadata caching.
        |
        | Supported: CacheDriver::NONE, CacheDriver::AUTO, CacheDriver::LARAVEL,
        |            CacheDriver::SYMFONY, CacheDriver::FILESYSTEM
        |
        | - NONE: Disable caching completely (not recommended for production)
        | - AUTO: Automatically detect Laravel/Symfony cache, fallback to filesystem
        | - LARAVEL: Use Laravel Cache (requires Laravel)
        | - SYMFONY: Use Symfony Cache (requires Symfony)
        | - FILESYSTEM: Use filesystem cache (always available)
        |
        | Default: CacheDriver::AUTO (recommended)
        |
        */
        'driver' => EnvHelper::string('DATA_HELPERS_CACHE_DRIVER')
            ? CacheDriver::from(EnvHelper::string('DATA_HELPERS_CACHE_DRIVER'))
            : CacheDriver::AUTO,

        /*
        |--------------------------------------------------------------------------
        | Cache TTL (Time To Live)
        |--------------------------------------------------------------------------
        |
        | How long to cache metadata in seconds.
        | Set to null for forever (recommended for production).
        |
        | Note: Cache is automatically invalidated when source files change,
        | so a long TTL is safe and recommended.
        |
        | Default: null (forever)
        |
        */
        'ttl' => (($ttlValue = EnvHelper::string('DATA_HELPERS_CACHE_TTL', '')) !== '')
            ? (int)$ttlValue
            : null,

        /*
        |--------------------------------------------------------------------------
        | Enable Code Generation
        |--------------------------------------------------------------------------
        |
        | Enable automatic code generation for optimized fromArray() methods.
        | Generated code is stored in cache/generated/ directory.
        |
        | When enabled, optimized code is generated on first use and
        | automatically regenerated when source files change.
        |
        | Default: true (recommended for production)
        |
        */
        'code_generation' => EnvHelper::boolean('DATA_HELPERS_CODE_GENERATION', true),

        /*
        |--------------------------------------------------------------------------
        | Cache Invalidation Strategy
        |--------------------------------------------------------------------------
        |
        | How to detect when cache should be invalidated.
        |
        | Supported: CacheInvalidation::MANUAL, CacheInvalidation::MTIME,
        |            CacheInvalidation::HASH, CacheInvalidation::BOTH
        |
        | - MANUAL: No automatic validation - cache only invalidated by clear-cache
        |           command (like Spatie Laravel Data). Best performance, but requires
        |           manual cache clearing after code changes.
        | - MTIME: Check file modification time on every cache hit (fast, automatic)
        | - HASH: Check file content hash on every cache hit (slower, more accurate)
        | - BOTH: Check both mtime and hash on every cache hit (most accurate)
        |
        | Default: CacheInvalidation::MANUAL (recommended for production with deployment cache clearing)
        |
        | Note: Use MANUAL in production with cache warming/clearing in deployment pipeline.
        |       Use MTIME/HASH/BOTH in development for automatic cache invalidation.
        |
        */
        'invalidation' => EnvHelper::string('DATA_HELPERS_CACHE_INVALIDATION')
            ? CacheInvalidation::from(EnvHelper::string('DATA_HELPERS_CACHE_INVALIDATION'))
            : CacheInvalidation::MTIME,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for data-helpers operations, performance metrics,
    | and data quality issues.
    |
    */
    'logging' => [
        /*
        |--------------------------------------------------------------------------
        | Enable Logging
        |--------------------------------------------------------------------------
        |
        | Master switch to enable or disable all logging.
        | When disabled, no logs will be written regardless of other settings.
        |
        | Default: false (disabled)
        |
        */
        'enabled' => EnvHelper::boolean('DATA_HELPERS_LOG_ENABLED', false),

        /*
        |--------------------------------------------------------------------------
        | Logger Driver
        |--------------------------------------------------------------------------
        |
        | The logger driver to use.
        |
        | Supported: 'filesystem', 'framework', 'none'
        |
        | - filesystem: Write logs to files (JSON format for Loki)
        | - framework: Use Laravel/Symfony logger
        | - none: Disable logging
        |
        */
        'driver' => \event4u\DataHelpers\Logging\LogDriver::from(
            EnvHelper::string('DATA_HELPERS_LOG_DRIVER', 'filesystem'),
        ),

        /*
        |--------------------------------------------------------------------------
        | Log Path (Filesystem Driver)
        |--------------------------------------------------------------------------
        |
        | Directory where log files will be stored.
        |
        */
        'path' => EnvHelper::string('DATA_HELPERS_LOG_PATH', './storage/logs/'),

        /*
        |--------------------------------------------------------------------------
        | Log Filename Pattern (Filesystem Driver)
        |--------------------------------------------------------------------------
        |
        | Filename pattern for log files. Supports date() format.
        |
        | Examples:
        | - 'data-helper-Y-m-d.log' = One file per day
        | - 'data-helper-Y-m-d-H.log' = One file per hour
        | - 'data-helper-Y-m-d-H-i-s.log' = One file per second
        |
        */
        'filename_pattern' => EnvHelper::string('DATA_HELPERS_LOG_FILENAME', 'data-helper-Y-m-d-H-i-s.log'),

        /*
        |--------------------------------------------------------------------------
        | Minimum Log Level
        |--------------------------------------------------------------------------
        |
        | Minimum log level to write.
        |
        | Levels: emergency, alert, critical, error, warning, notice, info, debug
        |
        */
        'level' => \event4u\DataHelpers\Logging\LogLevel::from(
            EnvHelper::string('DATA_HELPERS_LOG_LEVEL', 'info'),
        ),

        /*
        |--------------------------------------------------------------------------
        | Enabled Events
        |--------------------------------------------------------------------------
        |
        | Which events should be logged. Set to true to enable, false to disable.
        |
        */
        'events' => [
            // Errors (always recommended)
            LogEvent::MAPPING_ERROR->value => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_MAPPING_ERROR', true),
            LogEvent::EXCEPTION->value => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_EXCEPTION', true),
            LogEvent::FILTER_ERROR->value => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_FILTER_ERROR', true),
            LogEvent::EXPRESSION_ERROR->value => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_EXPRESSION_ERROR', true),
            LogEvent::VALIDATION_FAILURE->value => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_VALIDATION_FAILURE', true),

            // Performance (can be noisy, use sampling)
            LogEvent::MAPPING_PERFORMANCE->value => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_PERFORMANCE_MAPPING', false),
            LogEvent::QUERY_PERFORMANCE->value => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_PERFORMANCE_QUERY', false),
            LogEvent::PIPELINE_PERFORMANCE->value => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_PERFORMANCE_PIPELINE', false),

            // Success (very noisy, use low sampling rate)
            LogEvent::MAPPING_SUCCESS->value => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_MAPPING_SUCCESS', false),
            LogEvent::QUERY_SUCCESS->value => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_QUERY_SUCCESS', false),

            // Data Quality
            LogEvent::MISSING_FIELD->value => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_MISSING_FIELD', true),
            LogEvent::NULL_VALUES_SKIPPED->value => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_NULL_SKIPPED', false),
            LogEvent::QUERY_EMPTY_RESULT->value => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_EMPTY_RESULT', true),

            // Metrics
            LogEvent::PROCESSED_RECORDS->value => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_PROCESSED_RECORDS', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Sampling Rates
        |--------------------------------------------------------------------------
        |
        | Sampling rates per event group (0.0 - 1.0).
        | Reduces log volume while maintaining visibility.
        |
        | Groups: errors, success, performance, data_quality, metrics
        |
        */
        'sampling' => [
            'errors' => (float)EnvHelper::string('DATA_HELPERS_LOG_SAMPLING_ERRORS', '1.0'),
            'success' => (float)EnvHelper::string('DATA_HELPERS_LOG_SAMPLING_SUCCESS', '0.01'),
            'performance' => (float)EnvHelper::string('DATA_HELPERS_LOG_SAMPLING_PERFORMANCE', '0.1'),
            'data_quality' => (float)EnvHelper::string('DATA_HELPERS_LOG_SAMPLING_DATA_QUALITY', '1.0'),
            'metrics' => (float)EnvHelper::string('DATA_HELPERS_LOG_SAMPLING_METRICS', '0.1'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Slack Integration
        |--------------------------------------------------------------------------
        |
        | Send important logs to Slack for real-time notifications.
        |
        */
        'slack' => [
            'enabled' => EnvHelper::boolean('DATA_HELPERS_SLACK_ENABLED', false),
            'webhook_url' => EnvHelper::string('DATA_HELPERS_SLACK_WEBHOOK', ''),
            'channel' => EnvHelper::string('DATA_HELPERS_SLACK_CHANNEL', '#data-helpers'),
            'username' => EnvHelper::string('DATA_HELPERS_SLACK_USERNAME', 'Data Helpers Bot'),

            // Minimum log level for Slack (usually 'error')
            'level' => \event4u\DataHelpers\Logging\LogLevel::from(
                EnvHelper::string('DATA_HELPERS_SLACK_LEVEL', 'error'),
            ),

            // Which events to send to Slack
            'events' => [
                \event4u\DataHelpers\Logging\LogEvent::MAPPING_ERROR->value,
                \event4u\DataHelpers\Logging\LogEvent::EXCEPTION->value,
                \event4u\DataHelpers\Logging\LogEvent::VALIDATION_FAILURE->value,
            ],

            // Queue name for async sending (null = sync)
            'queue' => EnvHelper::string('DATA_HELPERS_SLACK_QUEUE', null),
        ],

        /*
        |--------------------------------------------------------------------------
        | Grafana Integration
        |--------------------------------------------------------------------------
        |
        | Configure Grafana integration for logs and metrics.
        |
        */
        'grafana' => [
            'enabled' => EnvHelper::boolean('DATA_HELPERS_GRAFANA_ENABLED', false),

            // Log format: 'json' (for Loki) or 'text'
            'format' => EnvHelper::string('DATA_HELPERS_GRAFANA_FORMAT', 'json'),

            // Labels for Loki
            'labels' => [
                'app' => 'data-helpers',
                'environment' => EnvHelper::string('APP_ENV', 'production'),
            ],

            // Prometheus metrics
            'prometheus' => [
                'enabled' => EnvHelper::boolean('DATA_HELPERS_PROMETHEUS_ENABLED', false),
                'metrics_file' => EnvHelper::string('DATA_HELPERS_PROMETHEUS_FILE', './storage/metrics/data-helpers.prom'),
            ],
        ],
    ],
];
