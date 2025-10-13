<?php

declare(strict_types=1);

use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\Logging\LogEvent;
use event4u\DataHelpers\Logging\LogLevel;
use event4u\DataHelpers\Logging\LoggerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

describe('Logging (Symfony)', function (): void {
    beforeEach(function (): void {
        // Enable logging for tests
        DataHelpersConfig::reset();
        DataHelpersConfig::setMany([
            'logging' => [
                'enabled' => true,
                'driver' => 'framework',
                'level' => 'debug',
            ],
        ]);

        // Get logger from container
        $this->logger = self::getContainer()->get(LoggerInterface::class);

        // Get message bus if available (optional for E2E tests)
        $this->messageBus = self::getContainer()->has(MessageBusInterface::class)
            ? self::getContainer()->get(MessageBusInterface::class)
            : null;

        // Clear log file
        $logDir = self::getContainer()->getParameter('kernel.logs_dir');
        $logFile = $logDir . '/test.log';
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }
    });

    afterEach(function (): void {
        DataHelpersConfig::reset();
    });

    it('creates logger with Symfony logger', function (): void {
        $logger = LoggerFactory::create($this->logger);

        expect($logger)->toBeInstanceOf(\event4u\DataHelpers\Logging\DataHelpersLogger::class);
    });

    it('creates logger with Symfony logger and messenger', function (): void {
        $logger = LoggerFactory::create($this->logger, $this->messageBus);

        expect($logger)->toBeInstanceOf(\event4u\DataHelpers\Logging\DataHelpersLogger::class);
    });

    it('logs to Symfony logger', function (): void {
        $logger = LoggerFactory::create($this->logger);

        $logger->log(LogLevel::INFO, 'Test log message from data-helpers');

        // Symfony logger should have logged this
        expect(true)->toBeTrue(); // Logger doesn't throw
    });

    it('logs events', function (): void {
        $logger = LoggerFactory::create($this->logger);

        $logger->event(LogEvent::MAPPING_ERROR, [
            'error' => 'Test mapping error',
            'field' => 'test_field',
        ]);

        expect(true)->toBeTrue(); // No exception
    });

    it('logs performance metrics', function (): void {
        $logger = LoggerFactory::create($this->logger);

        $logger->performance('mapping', 123.45, [
            'operation' => 'test_mapping',
            'record_count' => 100,
        ]);

        expect(true)->toBeTrue(); // No exception
    });

    it('logs exceptions', function (): void {
        $logger = LoggerFactory::create($this->logger);

        $exception = new RuntimeException('Test exception from data-helpers', 500);
        $logger->exception($exception);

        expect(true)->toBeTrue(); // No exception
    });
})->group('symfony');

describe('Slack Integration (Symfony)', function (): void {
    beforeEach(function (): void {
        // Enable logging for tests
        DataHelpersConfig::reset();
        DataHelpersConfig::initialize([
            'logging' => [
                'enabled' => true,
                'driver' => 'framework',
                'level' => 'debug',
            ],
        ]);

        $this->logger = self::getContainer()->get(LoggerInterface::class);

        // Get message bus if available (optional for E2E tests)
        $this->messageBus = self::getContainer()->has(MessageBusInterface::class)
            ? self::getContainer()->get(MessageBusInterface::class)
            : null;
    });

    afterEach(function (): void {
        DataHelpersConfig::reset();
    });

    it('can create logger with messenger for async Slack', function (): void {
        $logger = LoggerFactory::create($this->logger, $this->messageBus);

        expect($logger)->toBeInstanceOf(\event4u\DataHelpers\Logging\DataHelpersLogger::class);
    });

    it('accepts error logs that would trigger Slack', function (): void {
        $logger = LoggerFactory::create($this->logger, $this->messageBus);

        $logger->log(LogLevel::ERROR, 'Critical error occurred');

        expect(true)->toBeTrue(); // No exception
    });

    it('accepts event logs that would trigger Slack', function (): void {
        $logger = LoggerFactory::create($this->logger, $this->messageBus);

        $logger->event(LogEvent::MAPPING_ERROR, ['error' => 'test']);

        expect(true)->toBeTrue(); // No exception
    });
})->group('symfony');

describe('Filesystem Logger (Symfony)', function (): void {
    beforeEach(function (): void {
        $this->logPath = sys_get_temp_dir() . '/data-helpers-symfony-test';
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }

        // Configure filesystem logger using setters
        DataHelpersConfig::reset();
        DataHelpersConfig::setMany([
            'logging.enabled' => true,
            'logging.driver' => 'filesystem',
            'logging.path' => $this->logPath,
            'logging.filename_pattern' => 'Y-m-d-\l\o\g.\l\o\g',
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
})->group('symfony');

describe('Prometheus Logger (Symfony)', function (): void {
    beforeEach(function (): void {
        $this->metricsPath = sys_get_temp_dir() . '/data-helpers-metrics-test';
        if (!is_dir($this->metricsPath)) {
            mkdir($this->metricsPath, 0777, true);
        }

        $this->metricsFile = $this->metricsPath . '/data-helpers.prom';

        // Configure Prometheus logger using setters
        DataHelpersConfig::reset();
        DataHelpersConfig::setMany([
            'logging.enabled' => true,
            'logging.driver' => 'filesystem',
            'logging.grafana.prometheus.enabled' => true,
            'logging.grafana.prometheus.metrics_file' => $this->metricsFile,
        ]);
    });

    afterEach(function (): void {
        DataHelpersConfig::reset();

        // Clean up
        if (file_exists($this->metricsFile)) {
            unlink($this->metricsFile);
        }
        if (is_dir($this->metricsPath)) {
            rmdir($this->metricsPath);
        }
    });

    it('writes metrics to Prometheus file', function (): void {
        $logger = LoggerFactory::create();

        $logger->metric('test_metric', 42.0, ['tag' => 'value']);

        expect(file_exists($this->metricsFile))->toBeTrue();

        $content = file_get_contents($this->metricsFile);
        expect($content)->toContain('test_metric');
        expect($content)->toContain('42');
    });

    it('writes performance metrics', function (): void {
        $logger = LoggerFactory::create();

        $logger->performance('mapping', 123.45, ['operation' => 'test']);

        expect(file_exists($this->metricsFile))->toBeTrue();

        $content = file_get_contents($this->metricsFile);
        expect($content)->toContain('mapping');
    });
})->group('symfony');

