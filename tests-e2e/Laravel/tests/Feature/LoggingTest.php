<?php

declare(strict_types=1);

use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\Logging\LogEvent;
use event4u\DataHelpers\Logging\LogLevel;
use event4u\DataHelpers\Logging\LoggerFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

describe('Logging (Laravel)', function (): void {
    beforeEach(function (): void {
        // Configure logging using DataHelpersConfig
        DataHelpersConfig::reset();
        DataHelpersConfig::setMany([
            'logging.enabled' => true,
            'logging.driver' => 'framework',
            'logging.level' => 'debug',
            'logging.events' => [
                'mapping.error' => true,
                'mapping.success' => true,
                'performance.mapping' => true,
            ],
            'logging.sampling' => [
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

    afterEach(function (): void {
        // Reset config after each test
        DataHelpersConfig::reset();
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

        // Configure Slack integration using DataHelpersConfig
        DataHelpersConfig::reset();
        DataHelpersConfig::setMany([
            'logging.enabled' => true,
            'logging.driver' => 'framework',
            'logging.slack.enabled' => true,
            'logging.slack.webhook_url' => 'https://hooks.slack.com/test',
            'logging.slack.channel' => '#test',
            'logging.slack.level' => 'error',
            'logging.slack.queue' => 'default',
            'logging.slack.events' => [
                'mapping.error',
                'exception',
            ],
        ]);
    });

    afterEach(function (): void {
        // Reset config after each test
        DataHelpersConfig::reset();
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

        // Configure filesystem logger using setters
        // Note: filename_pattern uses date() format, so literal text must be escaped with backslashes
        DataHelpersConfig::reset();
        DataHelpersConfig::setMany([
            'logging.enabled' => true,
            'logging.driver' => 'filesystem',
            'logging.path' => $this->logPath,
            'logging.filename_pattern' => 'Y-m-d-\l\o\g.\l\o\g',  // All literal chars escaped
            'logging.level' => 'debug',
            'logging.grafana.enabled' => true,
            'logging.grafana.format' => 'json',
        ]);
    });

    afterEach(function (): void {
        DataHelpersConfig::reset();

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

        $files = glob($this->logPath . '/*.log');
        expect($files)->toBeArray();
        expect(count($files))->toBeGreaterThan(0);
    });

    it('writes JSON formatted logs for Loki', function (): void {
        $logger = LoggerFactory::create();

        $logger->log(LogLevel::INFO, 'Test JSON log', ['key' => 'value']);

        $files = glob($this->logPath . '/*.log');
        expect($files)->toBeArray();
        expect(count($files))->toBeGreaterThan(0);

        $content = file_get_contents($files[0]);
        expect($content)->toContain('"message":"Test JSON log"');
        expect($content)->toContain('"level":"info"');
        expect($content)->toContain('"key":"value"');
    });
})->group('laravel');

