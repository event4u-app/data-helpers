<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Logging;

/**
 * Log events for data-helpers operations.
 *
 * These events can be individually enabled/disabled in the configuration.
 */
enum LogEvent: string
{
    // Exceptions
    case MAPPING_ERROR = 'mapping.error';
    case EXCEPTION = 'exception';

    // Performance & Metrics
    case MAPPING_PERFORMANCE = 'performance.mapping';
    case QUERY_PERFORMANCE = 'performance.query';
    case PIPELINE_PERFORMANCE = 'performance.pipeline';
    case PROCESSED_RECORDS = 'metrics.processed_records';

    // Data Quality
    case MISSING_FIELD = 'data.missing_field';
    case NULL_VALUES_SKIPPED = 'data.null_skipped';
    case VALIDATION_FAILURE = 'data.validation_failure';

    // Query Builder
    case QUERY_EMPTY_RESULT = 'query.empty_result';

    // Pipeline
    case FILTER_ERROR = 'pipeline.filter_error';

    // Template Processing
    case EXPRESSION_ERROR = 'template.expression_error';

    // Success Events
    case MAPPING_SUCCESS = 'mapping.success';
    case QUERY_SUCCESS = 'query.success';

    /** Get the default sampling rate for this event. */
    public function defaultSamplingRate(): float
    {
        return match ($this) {
            // Errors: Always log (100%)
            self::MAPPING_ERROR,
            self::EXCEPTION,
            self::FILTER_ERROR,
            self::EXPRESSION_ERROR,
            self::VALIDATION_FAILURE => 1.0,

            // Performance: Sample 10%
            self::MAPPING_PERFORMANCE,
            self::QUERY_PERFORMANCE,
            self::PIPELINE_PERFORMANCE => 0.1,

            // Success: Sample 1%
            self::MAPPING_SUCCESS,
            self::QUERY_SUCCESS => 0.01,

            // Data Quality: Always log (100%)
            self::MISSING_FIELD,
            self::NULL_VALUES_SKIPPED,
            self::QUERY_EMPTY_RESULT => 1.0,

            // Metrics: Sample 10%
            self::PROCESSED_RECORDS => 0.1,
        };
    }

    /** Get the default log level for this event. */
    public function defaultLogLevel(): LogLevel
    {
        return match ($this) {
            self::MAPPING_ERROR,
            self::EXCEPTION,
            self::FILTER_ERROR,
            self::EXPRESSION_ERROR,
            self::VALIDATION_FAILURE => LogLevel::ERROR,

            self::MISSING_FIELD,
            self::NULL_VALUES_SKIPPED,
            self::QUERY_EMPTY_RESULT => LogLevel::WARNING,

            self::MAPPING_PERFORMANCE,
            self::QUERY_PERFORMANCE,
            self::PIPELINE_PERFORMANCE,
            self::PROCESSED_RECORDS => LogLevel::DEBUG,

            self::MAPPING_SUCCESS,
            self::QUERY_SUCCESS => LogLevel::INFO,
        };
    }

    /** Get the sampling group for this event. */
    public function samplingGroup(): string
    {
        return match ($this) {
            self::MAPPING_ERROR,
            self::EXCEPTION,
            self::FILTER_ERROR,
            self::EXPRESSION_ERROR,
            self::VALIDATION_FAILURE => 'errors',

            self::MAPPING_SUCCESS,
            self::QUERY_SUCCESS => 'success',

            self::MAPPING_PERFORMANCE,
            self::QUERY_PERFORMANCE,
            self::PIPELINE_PERFORMANCE => 'performance',

            self::MISSING_FIELD,
            self::NULL_VALUES_SKIPPED,
            self::QUERY_EMPTY_RESULT => 'data_quality',

            self::PROCESSED_RECORDS => 'metrics',
        };
    }
}

