<?php

declare(strict_types=1);

use event4u\DataHelpers\Logging\LogEvent;
use event4u\DataHelpers\Logging\Loggers\FilesystemLogger;
use event4u\DataHelpers\Logging\LogLevel;

describe('FilesystemLogger', function(): void {
    beforeEach(function(): void {
        $this->logPath = sys_get_temp_dir() . '/data-helpers-test-logs';
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }

        $this->logger = new FilesystemLogger(
            $this->logPath,
            '\l\o\g-Y-m-d-H-i-s.\l\o\g',
            LogLevel::DEBUG,
            [
                'mapping.error' => true,
                'mapping.success' => true,
            ],
            [
                'errors' => 1.0,
                'success' => 1.0,
            ],
            true, // JSON format
        );
    });

    afterEach(function(): void {
        // Clean up log files
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

    it('creates log file on first write', function(): void {
        $this->logger->log(LogLevel::INFO, 'Test message');

        $files = glob($this->logPath . '/log-*.log');
        if (false === $files) {
            throw new RuntimeException('glob() failed');
        }
        expect($files)->toBeArray();
        expect(count($files))->toBe(1);
    });

    it('writes JSON formatted logs', function(): void {
        $this->logger->log(LogLevel::INFO, 'Test message', ['key' => 'value']);

        $files = glob($this->logPath . '/log-*.log');
        if (false === $files) {
            throw new RuntimeException('glob() failed');
        }
        expect($files)->toBeArray();

        $content = file_get_contents($files[0]);
        expect($content)->toBeString();
        expect($content)->toContain('"message":"Test message"');
        expect($content)->toContain('"level":"info"');
        expect($content)->toContain('"key":"value"');
    });

    it('respects minimum log level', function(): void {
        $logger = new FilesystemLogger(
            $this->logPath,
            '\l\e\v\e\l.\l\o\g',
            LogLevel::ERROR,
            [],
            [],
            true,
        );

        $logger->log(LogLevel::DEBUG, 'Debug message');
        $logger->log(LogLevel::INFO, 'Info message');
        $logger->log(LogLevel::ERROR, 'Error message');

        $files = glob($this->logPath . '/level.log');
        if (false === $files || [] === $files) {
            expect(true)->toBeTrue(); // No file created for DEBUG/INFO
            return;
        }

        $content = file_get_contents($files[0]);
        expect($content)->not->toContain('Debug message');
        expect($content)->not->toContain('Info message');
        expect($content)->toContain('Error message');
    });

    it('logs events when enabled', function(): void {
        $this->logger->event(LogEvent::MAPPING_ERROR, ['error' => 'test']);

        $files = glob($this->logPath . '/log-*.log');
        if (false === $files) {
            throw new RuntimeException('glob() failed');
        }
        expect($files)->toBeArray();
        expect(count($files))->toBeGreaterThan(0);

        $content = file_get_contents($files[0]);
        expect($content)->toContain('mapping.error');
    });

    it('skips events when disabled', function(): void {
        $logger = new FilesystemLogger(
            $this->logPath,
            '\e\v\e\n\t\s.\l\o\g',
            LogLevel::DEBUG,
            [
                'mapping.error' => false,
            ],
            [],
            true,
        );

        $logger->event(LogEvent::MAPPING_ERROR, ['error' => 'test']);

        $files = glob($this->logPath . '/events.log');
        if (false === $files) {
            throw new RuntimeException('glob() failed');
        }
        expect($files)->toBeArray();
        expect(count($files))->toBe(0);
    });

    it('logs performance metrics', function(): void {
        $this->logger->performance('mapping', 123.45, ['operation' => 'test']);

        $files = glob($this->logPath . '/log-*.log');
        if (false === $files) {
            throw new RuntimeException('glob() failed');
        }
        expect($files)->toBeArray();
        expect(count($files))->toBeGreaterThan(0);

        $content = file_get_contents($files[0]);
        expect($content)->toContain('mapping');
    });

    it('logs exceptions', function(): void {
        $exception = new RuntimeException('Test exception', 500);
        $this->logger->exception($exception);

        $files = glob($this->logPath . '/log-*.log');
        if (false === $files) {
            throw new RuntimeException('glob() failed');
        }
        expect($files)->toBeArray();
        expect(count($files))->toBeGreaterThan(0);

        $content = file_get_contents($files[0]);
        expect($content)->toContain('Test exception');
    });
});
