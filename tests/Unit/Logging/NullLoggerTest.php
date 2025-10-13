<?php

declare(strict_types=1);

use event4u\DataHelpers\Logging\LogEvent;
use event4u\DataHelpers\Logging\Loggers\NullLogger;
use event4u\DataHelpers\Logging\LogLevel;

describe('NullLogger', function(): void {
    beforeEach(function(): void {
        $this->logger = new NullLogger();
    });

    it('accepts log calls without errors', function(): void {
        $this->logger->log(LogLevel::ERROR, 'Test message', ['key' => 'value']);
        expect(true)->toBeTrue(); // No exception thrown
    });

    it('accepts event calls without errors', function(): void {
        $this->logger->event(LogEvent::MAPPING_ERROR, ['error' => 'test']);
        expect(true)->toBeTrue(); // No exception thrown
    });

    it('accepts performance calls without errors', function(): void {
        $this->logger->performance('operation', 123.45, ['key' => 'value']);
        expect(true)->toBeTrue(); // No exception thrown
    });

    it('accepts exception calls without errors', function(): void {
        $exception = new RuntimeException('Test');
        $this->logger->exception($exception);
        expect(true)->toBeTrue(); // No exception thrown
    });

    it('accepts metric calls without errors', function(): void {
        $this->logger->metric('test_metric', 42.0, ['tag' => 'value']);
        expect(true)->toBeTrue(); // No exception thrown
    });
});

