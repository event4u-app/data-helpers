<?php

declare(strict_types=1);

use event4u\DataHelpers\Logging\LogEvent;
use event4u\DataHelpers\Logging\LogLevel;
use event4u\DataHelpers\Logging\LoggerFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

describe('Logging (Laravel)', function (): void {
    beforeEach(function (): void {
        // Set up test configuration
        config([
            'data-helpers.logging.driver' => 'framework',
            'data-helpers.logging.level' => 'debug',
            'data-helpers.logging.events' => [
                'mapping.error' => true,
                'mapping.success' => true,
                'performance.mapping' => true,
            ],
            'data-helpers.logging.sampling' => [
                'errors' => 1.0,
                'success' => 1.0,
                'performance' => 1.0,
            ],
        ]);

        // Clear log file
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }
    });

    it('creates logger with Laravel logger', function (): void {
        $logger = LoggerFactory::create(Log::getFacadeRoot());

        expect($logger)->toBeInstanceOf(\event4u\DataHelpers\Logging\DataHelpersLogger::class);
    });

    it('logs to Laravel logger', function (): void {
        $logger = LoggerFactory::create(Log::getFacadeRoot());

        $logger->log(LogLevel::INFO, 'Test log message from data-helpers');

        $logFile = storage_path('logs/laravel.log');
        $content = file_get_contents($logFile);

        expect($content)->toContain('Test log message from data-helpers');
    });

    it('logs events', function (): void {
        $logger = LoggerFactory::create(Log::getFacadeRoot());

        $logger->event(LogEvent::MAPPING_ERROR, [
            'error' => 'Test mapping error',
            'field' => 'test_field',
        ]);

        $logFile = storage_path('logs/laravel.log');
        $content = file_get_contents($logFile);

        expect($content)->toContain('mapping.error');
        expect($content)->toContain('Test mapping error');
    });

    it('logs performance metrics', function (): void {
        $logger = LoggerFactory::create(Log::getFacadeRoot());

        $logger->performance('mapping', 123.45, [
            'operation' => 'test_mapping',
            'record_count' => 100,
        ]);

        $logFile = storage_path('logs/laravel.log');
        $content = file_get_contents($logFile);

        expect($content)->toContain('mapping');
        expect($content)->toContain('123.45');
    });

    it('logs exceptions', function (): void {
        $logger = LoggerFactory::create(Log::getFacadeRoot());

        $exception = new RuntimeException('Test exception from data-helpers', 500);
        $logger->exception($exception);

        $logFile = storage_path('logs/laravel.log');
        $content = file_get_contents($logFile);

        expect($content)->toContain('Test exception from data-helpers');
        expect($content)->toContain('500');
    });
})->group('laravel');

describe('Slack Integration (Laravel)', function (): void {
    beforeEach(function (): void {
        Queue::fake();

        config([
            'data-helpers.logging.driver' => 'framework',
            'data-helpers.logging.slack.enabled' => true,
            'data-helpers.logging.slack.webhook_url' => 'https://hooks.slack.com/test',
            'data-helpers.logging.slack.channel' => '#test',
            'data-helpers.logging.slack.level' => 'error',
            'data-helpers.logging.slack.queue' => 'default',
            'data-helpers.logging.slack.events' => [
                'mapping.error',
                'exception',
            ],
        ]);
    });

    it('dispatches Slack job for errors', function (): void {
        $logger = LoggerFactory::create(Log::getFacadeRoot());

        $logger->log(LogLevel::ERROR, 'Critical error occurred');

        Queue::assertPushed(\event4u\DataHelpers\Logging\Jobs\SendLogToSlackJob::class);
    });

    it('does not dispatch Slack job for info logs', function (): void {
        $logger = LoggerFactory::create(Log::getFacadeRoot());

        $logger->log(LogLevel::INFO, 'Info message');

        Queue::assertNotPushed(\event4u\DataHelpers\Logging\Jobs\SendLogToSlackJob::class);
    });

    it('dispatches Slack job for enabled events', function (): void {
        $logger = LoggerFactory::create(Log::getFacadeRoot());

        $logger->event(LogEvent::MAPPING_ERROR, ['error' => 'test']);

        Queue::assertPushed(\event4u\DataHelpers\Logging\Jobs\SendLogToSlackJob::class);
    });

    it('uses configured queue name', function (): void {
        $logger = LoggerFactory::create(Log::getFacadeRoot());

        $logger->log(LogLevel::ERROR, 'Error message');

        Queue::assertPushed(
            \event4u\DataHelpers\Logging\Jobs\SendLogToSlackJob::class,
            function ($job) {
                return $job->queue === 'default';
            },
        );
    });
})->group('laravel');

describe('Filesystem Logger (Laravel)', function (): void {
    beforeEach(function (): void {
        $this->logPath = storage_path('logs/data-helpers-test');
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }

        config([
            'data-helpers.logging.driver' => 'filesystem',
            'data-helpers.logging.path' => $this->logPath,
            'data-helpers.logging.filename_pattern' => 'test-Y-m-d.log',
            'data-helpers.logging.level' => 'debug',
            'data-helpers.logging.grafana.enabled' => true,
            'data-helpers.logging.grafana.format' => 'json',
        ]);
    });

    afterEach(function (): void {
        // Clean up
        if (is_dir($this->logPath)) {
            $files = glob($this->logPath . '/*');
            if (false !== $files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            rmdir($this->logPath);
        }
    });

    it('creates log file in configured path', function (): void {
        $logger = LoggerFactory::create();

        $logger->log(LogLevel::INFO, 'Test filesystem log');

        $files = glob($this->logPath . '/test-*.log');
        expect($files)->toBeArray();
        expect(count($files))->toBeGreaterThan(0);
    });

    it('writes JSON formatted logs for Loki', function (): void {
        $logger = LoggerFactory::create();

        $logger->log(LogLevel::INFO, 'Test JSON log', ['key' => 'value']);

        $files = glob($this->logPath . '/test-*.log');
        $content = file_get_contents($files[0]);

        expect($content)->toContain('"message":"Test JSON log"');
        expect($content)->toContain('"level":"info"');
        expect($content)->toContain('"key":"value"');
    });
})->group('laravel');

