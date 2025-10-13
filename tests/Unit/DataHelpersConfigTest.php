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
        $maxEntries = DataHelpersConfig::getCacheMaxEntries();
        $mode = DataHelpersConfig::getPerformanceMode();

        expect($maxEntries)->toBeInt();
        expect($maxEntries)->toBeGreaterThan(0);
        expect($mode)->toBeIn(['fast', 'safe']);
        expect(DataHelpersConfig::isFastMode())->toBeBool();
    });

    it('sets custom configuration', function(): void {
        DataHelpersConfig::setMany([
            'cache.max_entries' => 5000,
            'performance_mode' => 'safe',
        ]);

        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(5000);
        expect(DataHelpersConfig::getPerformanceMode())->toBe('safe');
        expect(DataHelpersConfig::isFastMode())->toBeFalse();
    });

    it('gets nested configuration values', function(): void {
        DataHelpersConfig::set('cache.max_entries', 2000);

        expect(DataHelpersConfig::get('cache.max_entries'))->toBe(2000);
    });

    it('returns default value for missing keys', function(): void {
        expect(DataHelpersConfig::get('nonexistent', 'default'))->toBe('default');
        expect(DataHelpersConfig::get('cache.nonexistent', 42))->toBe(42);
    });

    it('handles partial configuration', function(): void {
        DataHelpersConfig::set('cache.max_entries', 3000);
        // performance_mode not set

        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(3000);
        expect(DataHelpersConfig::getPerformanceMode())->toBe('fast'); // default
    });

    it('resets configuration', function(): void {
        DataHelpersConfig::set('cache.max_entries', 5000);

        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(5000);

        DataHelpersConfig::reset();

        // After reset, ConfigHelper loads from config file or defaults
        $maxEntries = DataHelpersConfig::getCacheMaxEntries();
        expect($maxEntries)->toBeInt();
        expect($maxEntries)->toBeGreaterThan(0);
        // Should be different from the manually set value
        expect($maxEntries)->not->toBe(5000);
    });

    it('checks fast mode correctly', function(): void {
        DataHelpersConfig::set('performance_mode', 'fast');
        expect(DataHelpersConfig::isFastMode())->toBeTrue();

        DataHelpersConfig::set('performance_mode', 'safe');
        expect(DataHelpersConfig::isFastMode())->toBeFalse();
    });
});

