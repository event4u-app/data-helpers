<?php

declare(strict_types=1);

use event4u\DataHelpers\Logging\Support\LogContext;

describe('LogContext', function(): void {
    it('creates empty context', function(): void {
        $context = LogContext::create()->toArray();

        expect($context)->toBeArray();
        expect($context)->toBeEmpty();
    });

    it('adds simple values', function(): void {
        $context = LogContext::create()
            ->with('key1', 'value1')
            ->with('key2', 123)
            ->toArray();

        expect($context)->toBe([
            'key1' => 'value1',
            'key2' => 123,
        ]);
    });

    it('adds exception details', function(): void {
        $exception = new RuntimeException('Test error', 500);
        $context = LogContext::create()
            ->withException($exception)
            ->toArray();

        expect($context)->toHaveKey('exception');
        expect($context['exception'])->toHaveKey('message');
        expect($context['exception'])->toHaveKey('code');
        expect($context['exception'])->toHaveKey('file');
        expect($context['exception'])->toHaveKey('line');
        expect($context['exception']['message'])->toBe('Test error');
        expect($context['exception']['code'])->toBe(500);
    });

    it('adds performance metrics', function(): void {
        $context = LogContext::create()
            ->withPerformance(123.45, 100)
            ->toArray();

        expect($context)->toHaveKey('performance');
        expect($context['performance'])->toHaveKey('duration_ms');
        expect($context['performance'])->toHaveKey('record_count');
        expect($context['performance']['duration_ms'])->toBe(123.45);
        expect($context['performance']['record_count'])->toBe(100);
    });

    it('adds timestamp', function(): void {
        $context = LogContext::create()
            ->withTimestamp()
            ->toArray();

        expect($context)->toHaveKey('timestamp');
        expect($context['timestamp'])->toBeString();
        expect($context['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
    });

    it('adds memory usage', function(): void {
        $context = LogContext::create()
            ->withMemoryUsage()
            ->toArray();

        expect($context)->toHaveKey('memory');
        expect($context['memory'])->toHaveKey('current_mb');
        expect($context['memory'])->toHaveKey('peak_mb');
        expect($context['memory']['current_mb'])->toBeGreaterThan(0);
        expect($context['memory']['peak_mb'])->toBeGreaterThan(0);
    });

    it('chains multiple methods', function(): void {
        $exception = new RuntimeException('Test');
        $context = LogContext::create()
            ->with('operation', 'mapping')
            ->withException($exception)
            ->withPerformance(50.0, 10)
            ->withTimestamp()
            ->withMemoryUsage()
            ->toArray();

        expect($context)->toHaveKey('operation');
        expect($context)->toHaveKey('exception');
        expect($context)->toHaveKey('performance');
        expect($context)->toHaveKey('timestamp');
        expect($context)->toHaveKey('memory');
    });
});

