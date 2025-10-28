<?php

declare(strict_types=1);

use event4u\DataHelpers\Support\Cache\CacheInvalidator;
use event4u\DataHelpers\Support\Cache\CacheManager;

beforeEach(function(): void {
    CacheManager::reset();
    CacheManager::clear();
});

afterEach(function(): void {
    CacheManager::clear();
    CacheManager::reset();
});

it('can wrap and unwrap values', function(): void {
    $value = ['foo' => 'bar', 'nested' => ['data' => 123]];
    $sourceFile = __FILE__;

    $wrapped = CacheInvalidator::wrap($value, $sourceFile);

    expect($wrapped)->toBeArray();
    expect($wrapped)->toHaveKeys(['data', 'mtime', 'hash', 'version']);
    expect($wrapped['data'])->toBe($value);
    expect($wrapped['mtime'])->toBeInt();

    $unwrapped = CacheInvalidator::unwrap($wrapped);
    expect($unwrapped)->toBe($value);
});

it('validates cache based on file modification time', function(): void {
    $value = 'test_value';
    $sourceFile = __FILE__;

    $wrapped = CacheInvalidator::wrap($value, $sourceFile);

    // Should be valid immediately after wrapping
    expect(CacheInvalidator::isValid($wrapped, $sourceFile))->toBeTrue();
});

it('invalidates cache when file does not exist', function(): void {
    $value = 'test_value';
    $sourceFile = '/non/existent/file.php';

    $wrapped = CacheInvalidator::wrap($value, $sourceFile);

    // Should be invalid for non-existent file
    expect(CacheInvalidator::isValid($wrapped, $sourceFile))->toBeFalse();
});

it('can use remember helper for automatic caching', function(): void {
    $key = 'remember_test';
    $sourceFile = __FILE__;
    $callCount = 0;

    $generator = function() use (&$callCount): string {
        $callCount++;

        return 'generated_value_' . $callCount;
    };

    // First call should generate
    $value1 = CacheInvalidator::remember($key, $sourceFile, $generator);
    expect($value1)->toBe('generated_value_1');
    expect($callCount)->toBe(1);

    // Second call should use cache
    $value2 = CacheInvalidator::remember($key, $sourceFile, $generator);
    expect($value2)->toBe('generated_value_1'); // Same value
    expect($callCount)->toBe(1); // Generator not called again
});

it('handles missing mtime in cached data', function(): void {
    $sourceFile = __FILE__;

    $invalidData = [
        'data' => 'test',
        'version' => '1.0.0',
        'mtime' => false,
        'hash' => null,
    ];

    expect(CacheInvalidator::isValid($invalidData, $sourceFile))->toBeFalse();
});

it('handles missing version in cached data', function(): void {
    $sourceFile = __FILE__;

    $invalidData = [
        'data' => 'test',
        'mtime' => filemtime($sourceFile),
        'hash' => null,
        'version' => '',
    ];

    expect(CacheInvalidator::isValid($invalidData, $sourceFile))->toBeFalse();
});

it('wraps values with null hash when using mtime strategy', function(): void {
    $value = 'test_value';
    $sourceFile = __FILE__;

    $wrapped = CacheInvalidator::wrap($value, $sourceFile);

    // Hash should be null for mtime strategy (default)
    expect($wrapped['hash'])->toBeNull();
});
