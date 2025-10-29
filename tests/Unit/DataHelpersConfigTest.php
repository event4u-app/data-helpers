<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\Enums\PerformanceMode;

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

        expect($mode)->toBeIn([PerformanceMode::FAST->value, PerformanceMode::SAFE->value]);
        expect(DataHelpersConfig::isFastMode())->toBeBool();
    });

    it('sets custom configuration', function(): void {
        DataHelpersConfig::setMany([
            'performance_mode' => PerformanceMode::SAFE->value,
        ]);

        expect(DataHelpersConfig::getPerformanceMode())->toBe(PerformanceMode::SAFE->value);
        expect(DataHelpersConfig::isFastMode())->toBeFalse();
    });

    it('gets nested configuration values', function(): void {
        DataHelpersConfig::set('performance_mode', PerformanceMode::SAFE->value);

        expect(DataHelpersConfig::get('performance_mode'))->toBe(PerformanceMode::SAFE->value);
    });

    it('returns default value for missing keys', function(): void {
        expect(DataHelpersConfig::get('nonexistent', 'default'))->toBe('default');
        expect(DataHelpersConfig::get('some.nonexistent', 42))->toBe(42);
    });

    it('handles partial configuration', function(): void {
        DataHelpersConfig::reset(); // Ensure clean state
        DataHelpersConfig::set('performance_mode', PerformanceMode::SAFE->value);

        // performance_mode should be 'safe' as we just set it
        expect(DataHelpersConfig::getPerformanceMode())->toBe(PerformanceMode::SAFE->value);
    });

    it('resets configuration', function(): void {
        DataHelpersConfig::set('performance_mode', PerformanceMode::SAFE->value);

        expect(DataHelpersConfig::getPerformanceMode())->toBe(PerformanceMode::SAFE->value);

        DataHelpersConfig::reset();

        // After reset, ConfigHelper loads from config file or defaults
        $mode = DataHelpersConfig::getPerformanceMode();
        expect($mode)->toBeIn([PerformanceMode::FAST->value, PerformanceMode::SAFE->value]);
    });

    it('checks fast mode correctly', function(): void {
        DataHelpersConfig::set('performance_mode', PerformanceMode::FAST->value);
        expect(DataHelpersConfig::isFastMode())->toBeTrue();

        DataHelpersConfig::set('performance_mode', PerformanceMode::SAFE->value);
        expect(DataHelpersConfig::isFastMode())->toBeFalse();
    });
});
