<?php

declare(strict_types=1);

use event4u\DataHelpers\Enums\CacheDriver;
use event4u\DataHelpers\Helpers\EnvHelper;

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
        | Supported: CacheDriver::MEMORY, CacheDriver::FRAMEWORK, CacheDriver::NONE
        |
        | - MEMORY: In-memory LRU cache (fast, no persistence)
        | - FRAMEWORK: Automatically uses Laravel or Symfony cache if available,
        |              falls back to memory if no framework is detected
        | - NONE: No caching (for testing/debugging)
        |
        */
        'driver' => EnvHelper::string('DATA_HELPERS_CACHE_DRIVER', CacheDriver::default()->value),

        /*
        |--------------------------------------------------------------------------
        | Maximum Cache Entries (Memory Driver)
        |--------------------------------------------------------------------------
        |
        | Maximum number of cache entries for the memory driver.
        | When the limit is reached, the oldest entries will be discarded (LRU).
        |
        */
        'max_entries' => EnvHelper::integer('DATA_HELPERS_CACHE_MAX_ENTRIES', 1000),

        /*
        |--------------------------------------------------------------------------
        | Cache Key Prefix
        |--------------------------------------------------------------------------
        |
        | Prefix for cache keys (used by laravel/symfony drivers).
        |
        */
        'prefix' => EnvHelper::string('DATA_HELPERS_CACHE_PREFIX', 'data_helpers:'),

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
        'default_ttl' => EnvHelper::integer('DATA_HELPERS_CACHE_DEFAULT_TTL', 3600),
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
    'performance_mode' => EnvHelper::string('DATA_HELPERS_PERFORMANCE_MODE', 'fast'),

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
        'driver' => EnvHelper::string('DATA_HELPERS_LOG_DRIVER', 'filesystem'),

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
        'level' => EnvHelper::string('DATA_HELPERS_LOG_LEVEL', 'info'),

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
            'mapping.error' => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_MAPPING_ERROR', true),
            'exception' => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_EXCEPTION', true),
            'pipeline.filter_error' => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_FILTER_ERROR', true),
            'template.expression_error' => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_EXPRESSION_ERROR', true),
            'data.validation_failure' => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_VALIDATION_FAILURE', true),

            // Performance (can be noisy, use sampling)
            'performance.mapping' => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_PERFORMANCE_MAPPING', false),
            'performance.query' => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_PERFORMANCE_QUERY', false),
            'performance.pipeline' => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_PERFORMANCE_PIPELINE', false),

            // Success (very noisy, use low sampling rate)
            'mapping.success' => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_MAPPING_SUCCESS', false),
            'query.success' => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_QUERY_SUCCESS', false),

            // Cache
            'cache.hit' => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_CACHE_HIT', false),
            'cache.miss' => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_CACHE_MISS', false),

            // Data Quality
            'data.missing_field' => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_MISSING_FIELD', true),
            'data.null_skipped' => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_NULL_SKIPPED', false),
            'query.empty_result' => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_EMPTY_RESULT', true),

            // Metrics
            'metrics.processed_records' => EnvHelper::boolean('DATA_HELPERS_LOG_EVENT_PROCESSED_RECORDS', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Sampling Rates
        |--------------------------------------------------------------------------
        |
        | Sampling rates per event group (0.0 - 1.0).
        | Reduces log volume while maintaining visibility.
        |
        | Groups: errors, success, performance, cache, data_quality, metrics
        |
        */
        'sampling' => [
            'errors' => (float) EnvHelper::string('DATA_HELPERS_LOG_SAMPLING_ERRORS', '1.0'),
            'success' => (float) EnvHelper::string('DATA_HELPERS_LOG_SAMPLING_SUCCESS', '0.01'),
            'performance' => (float) EnvHelper::string('DATA_HELPERS_LOG_SAMPLING_PERFORMANCE', '0.1'),
            'cache' => (float) EnvHelper::string('DATA_HELPERS_LOG_SAMPLING_CACHE', '0.05'),
            'data_quality' => (float) EnvHelper::string('DATA_HELPERS_LOG_SAMPLING_DATA_QUALITY', '1.0'),
            'metrics' => (float) EnvHelper::string('DATA_HELPERS_LOG_SAMPLING_METRICS', '0.1'),
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
            'level' => EnvHelper::string('DATA_HELPERS_SLACK_LEVEL', 'error'),

            // Which events to send to Slack
            'events' => [
                'mapping.error',
                'exception',
                'data.validation_failure',
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
