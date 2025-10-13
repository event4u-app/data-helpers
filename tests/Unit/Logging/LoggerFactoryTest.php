<?php

declare(strict_types=1);

use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\Logging\LogDriver;
use event4u\DataHelpers\Logging\LoggerFactory;
use event4u\DataHelpers\Logging\Loggers\NullLogger;
use event4u\DataHelpers\Logging\LogLevel;

describe('LoggerFactory', function(): void {
    afterEach(function(): void {
        // Reset config after each test
        DataHelpersConfig::reset();
    });

    it('returns NullLogger when logging is disabled', function(): void {
        DataHelpersConfig::initialize([
            'logging' => [
                'enabled' => false,
                'driver' => LogDriver::FILESYSTEM,
                'path' => sys_get_temp_dir(),
                'filename_pattern' => 'test.log',
                'level' => LogLevel::INFO,
            ],
        ]);

        $logger = LoggerFactory::create();

        expect($logger)->toBeInstanceOf(NullLogger::class);
    });

    it('creates logger when logging is enabled', function(): void {
        DataHelpersConfig::initialize([
            'logging' => [
                'enabled' => true,
                'driver' => LogDriver::FILESYSTEM,
                'path' => sys_get_temp_dir(),
                'filename_pattern' => 'test.log',
                'level' => LogLevel::INFO,
            ],
        ]);

        $logger = LoggerFactory::create();

        expect($logger)->not->toBeInstanceOf(NullLogger::class);
    });

    it('accepts LogDriver enum', function(): void {
        DataHelpersConfig::initialize([
            'logging' => [
                'enabled' => true,
                'driver' => LogDriver::NONE,
            ],
        ]);

        $logger = LoggerFactory::create();

        expect($logger)->toBeInstanceOf(NullLogger::class);
    });

    it('accepts LogDriver string', function(): void {
        DataHelpersConfig::initialize([
            'logging' => [
                'enabled' => true,
                'driver' => 'none',
            ],
        ]);

        $logger = LoggerFactory::create();

        expect($logger)->toBeInstanceOf(NullLogger::class);
    });

    it('accepts LogLevel enum', function(): void {
        DataHelpersConfig::initialize([
            'logging' => [
                'enabled' => true,
                'driver' => LogDriver::FILESYSTEM,
                'path' => sys_get_temp_dir(),
                'filename_pattern' => 'test.log',
                'level' => LogLevel::ERROR,
            ],
        ]);

        $logger = LoggerFactory::create();

        expect($logger)->not->toBeInstanceOf(NullLogger::class);
    });

    it('accepts LogLevel string', function(): void {
        DataHelpersConfig::initialize([
            'logging' => [
                'enabled' => true,
                'driver' => LogDriver::FILESYSTEM,
                'path' => sys_get_temp_dir(),
                'filename_pattern' => 'test.log',
                'level' => 'error',
            ],
        ]);

        $logger = LoggerFactory::create();

        expect($logger)->not->toBeInstanceOf(NullLogger::class);
    });

    it('defaults to disabled when enabled is not set', function(): void {
        DataHelpersConfig::initialize([
            'logging' => [
                'driver' => LogDriver::FILESYSTEM,
            ],
        ]);

        $logger = LoggerFactory::create();

        expect($logger)->toBeInstanceOf(NullLogger::class);
    });
});

