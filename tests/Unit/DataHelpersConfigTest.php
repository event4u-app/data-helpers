<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\DataHelpersConfig;

describe('DataHelpers Config', function(): void {
    beforeEach(function(): void {
        DataHelpersConfig::reset();
    });

    afterEach(function(): void {
        DataHelpersConfig::reset();
    });

    it('loads default configuration', function(): void {
        // After reset, ConfigHelper loads from config file or defaults
        $mode = DataHelpersConfig::getPerformanceMode();

        expect($mode)->toBeIn(['fast', 'safe']);
        expect(DataHelpersConfig::isFastMode())->toBeBool();
    });

    it('sets custom configuration', function(): void {
        DataHelpersConfig::setMany([
            'performance_mode' => 'safe',
        ]);

        expect(DataHelpersConfig::getPerformanceMode())->toBe('safe');
        expect(DataHelpersConfig::isFastMode())->toBeFalse();
    });

    it('gets nested configuration values', function(): void {
        DataHelpersConfig::set('performance_mode', 'safe');

        expect(DataHelpersConfig::get('performance_mode'))->toBe('safe');
    });

    it('returns default value for missing keys', function(): void {
        expect(DataHelpersConfig::get('nonexistent', 'default'))->toBe('default');
        expect(DataHelpersConfig::get('some.nonexistent', 42))->toBe(42);
    });

    it('handles partial configuration', function(): void {
        DataHelpersConfig::reset(); // Ensure clean state
        DataHelpersConfig::set('performance_mode', 'safe');

        // performance_mode should be 'safe' as we just set it
        expect(DataHelpersConfig::getPerformanceMode())->toBe('safe');
    });

    it('resets configuration', function(): void {
        DataHelpersConfig::set('performance_mode', 'safe');

        expect(DataHelpersConfig::getPerformanceMode())->toBe('safe');

        DataHelpersConfig::reset();

        // After reset, ConfigHelper loads from config file or defaults
        $mode = DataHelpersConfig::getPerformanceMode();
        expect($mode)->toBeIn(['fast', 'safe']);
    });

    it('checks fast mode correctly', function(): void {
        DataHelpersConfig::set('performance_mode', 'fast');
        expect(DataHelpersConfig::isFastMode())->toBeTrue();

        DataHelpersConfig::set('performance_mode', 'safe');
        expect(DataHelpersConfig::isFastMode())->toBeFalse();
    });
});

