<?php

declare(strict_types=1);

use event4u\DataHelpers\Logging\LogEvent;
use event4u\DataHelpers\Logging\LogLevel;

describe('LogEvent', function(): void {
    it('has correct default sampling rates', function(): void {
        expect(LogEvent::MAPPING_ERROR->defaultSamplingRate())->toBe(1.0);
        expect(LogEvent::EXCEPTION->defaultSamplingRate())->toBe(1.0);
        expect(LogEvent::MAPPING_SUCCESS->defaultSamplingRate())->toBe(0.01);
        expect(LogEvent::MAPPING_PERFORMANCE->defaultSamplingRate())->toBe(0.1);
    });

    it('has correct default log levels', function(): void {
        expect(LogEvent::MAPPING_ERROR->defaultLogLevel())->toBe(LogLevel::ERROR);
        expect(LogEvent::EXCEPTION->defaultLogLevel())->toBe(LogLevel::ERROR);
        expect(LogEvent::MAPPING_SUCCESS->defaultLogLevel())->toBe(LogLevel::INFO);
        expect(LogEvent::MAPPING_PERFORMANCE->defaultLogLevel())->toBe(LogLevel::DEBUG);
    });

    it('groups events correctly', function(): void {
        expect(LogEvent::MAPPING_ERROR->samplingGroup())->toBe('errors');
        expect(LogEvent::EXCEPTION->samplingGroup())->toBe('errors');
        expect(LogEvent::MAPPING_SUCCESS->samplingGroup())->toBe('success');
        expect(LogEvent::MAPPING_PERFORMANCE->samplingGroup())->toBe('performance');
        expect(LogEvent::MISSING_FIELD->samplingGroup())->toBe('data_quality');
        expect(LogEvent::PROCESSED_RECORDS->samplingGroup())->toBe('metrics');
    });

    it('has correct enum values', function(): void {
        expect(LogEvent::MAPPING_ERROR->value)->toBe('mapping.error');
        expect(LogEvent::EXCEPTION->value)->toBe('exception');
        expect(LogEvent::MAPPING_SUCCESS->value)->toBe('mapping.success');
        expect(LogEvent::MAPPING_PERFORMANCE->value)->toBe('performance.mapping');
    });
});

