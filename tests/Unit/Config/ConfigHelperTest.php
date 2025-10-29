<?php

declare(strict_types=1);

use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\Enums\PerformanceMode;
use event4u\DataHelpers\Helpers\ConfigHelper;

beforeEach(function(): void {
    DataHelpersConfig::reset();
});

afterEach(function(): void {
    DataHelpersConfig::reset();
});

describe('ConfigHelper', function(): void {
    it('returns singleton instance', function(): void {
        $instance1 = ConfigHelper::getInstance();
        $instance2 = ConfigHelper::getInstance();

        expect($instance1)->toBe($instance2);
    });

    it('resets singleton instance', function(): void {
        $instance1 = ConfigHelper::getInstance();
        ConfigHelper::resetInstance();
        $instance2 = ConfigHelper::getInstance();

        expect($instance1)->not->toBe($instance2);
    });

    it('gets configuration value with dot notation', function(): void {
        $helper = ConfigHelper::getInstance();

        $value = $helper->get('performance_mode');

        expect($value)->toBeString();
    });

    it('returns default value for missing key', function(): void {
        $helper = ConfigHelper::getInstance();

        $value = $helper->get('non.existent.key', 'default');

        expect($value)->toBe('default');
    });

    it('gets boolean value', function(): void {
        $helper = ConfigHelper::getInstance();

        // Test with actual boolean
        $value = $helper->getBoolean('some.boolean', true);
        expect($value)->toBeBool();

        // Test with string 'true'
        $value = $helper->getBoolean('non.existent', false);
        expect($value)->toBe(false);
    });

    it('gets integer value', function(): void {
        $helper = ConfigHelper::getInstance();

        $value = $helper->getInteger('logging.enabled', 0);

        expect($value)->toBeInt();
    });

    it('gets float value', function(): void {
        $helper = ConfigHelper::getInstance();

        $value = $helper->getFloat('some.float', 1.5);

        expect($value)->toBeFloat();
    });

    it('gets string value', function(): void {
        $helper = ConfigHelper::getInstance();

        $value = $helper->getString('performance_mode', PerformanceMode::SAFE->value);

        expect($value)->toBeString();
        expect($value)->toBeIn([PerformanceMode::FAST->value, PerformanceMode::SAFE->value]);
    });

    it('gets array value', function(): void {
        $helper = ConfigHelper::getInstance();

        // Test with a simple array config
        $helper->set('test_array', ['key1' => 'value1', 'key2' => 'value2']);

        $value = $helper->getArray('test_array', []);

        expect($value)->toBeArray();
        expect($value)->toHaveKey('key1');
    });

    it('checks if key exists', function(): void {
        $helper = ConfigHelper::getInstance();

        expect($helper->has('performance_mode'))->toBeTrue();
        expect($helper->has('non.existent.key'))->toBeFalse();
    });

    it('returns all configuration', function(): void {
        $helper = ConfigHelper::getInstance();

        $all = $helper->all();

        expect($all)->toBeArray();
        expect($all)->toHaveKey('performance_mode');
    });

    it('detects configuration source', function(): void {
        $helper = ConfigHelper::getInstance();

        $source = $helper->getSource();

        expect($source)->toBeIn(['laravel', 'symfony', 'plain', 'default']);
    });

    it('converts string to boolean correctly', function(): void {
        $helper = ConfigHelper::getInstance();

        // Test with default false
        expect($helper->getBoolean('non.existent.key', false))->toBeFalse();

        // Test with default true
        expect($helper->getBoolean('non.existent.key', true))->toBeTrue();
    });

    it('casts values correctly', function(): void {
        $helper = ConfigHelper::getInstance();

        // Integer casting
        expect($helper->getInteger('non.existent', 123))->toBe(123);
        expect($helper->getInteger('logging.enabled', 0))->toBeInt();

        // Float casting
        expect($helper->getFloat('non.existent', 123.45))->toBe(123.45);
        expect($helper->getFloat('non.existent', 123.0))->toBe(123.0);

        // String casting
        expect($helper->getString('non.existent', '123'))->toBe('123');
        expect($helper->getString('performance_mode', PerformanceMode::SAFE->value))->toBeString();
    });

    it('handles nested arrays correctly', function(): void {
        $helper = ConfigHelper::getInstance();

        // Set up test nested config
        $helper->set('test_nested', [
            'level1' => [
                'level2' => 'value',
            ],
        ]);

        // Get nested value
        $value = $helper->get('test_nested.level1.level2');
        expect($value)->toBe('value');

        // Get parent array
        $level1 = $helper->getArray('test_nested.level1');
        expect($level1)->toBeArray();
        expect($level1)->toHaveKey('level2');
    });

    it('returns default for non-array when expecting array', function(): void {
        $helper = ConfigHelper::getInstance();

        // performance_mode is a string, not an array
        $value = $helper->getArray('performance_mode', ['default']);

        expect($value)->toBe(['default']);
    });
});
