<?php

declare(strict_types=1);

namespace Tests\Unit\DataMapper\Template;

use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\DataMapper\Template\ExpressionParser;

describe('Expression Parser Cache', function(): void {
    beforeEach(function(): void {
        DataHelpersConfig::reset();
        ExpressionParser::clearCache();
    });

    afterEach(function(): void {
        DataHelpersConfig::reset();
        ExpressionParser::clearCache();
    });

    it('caches parsed expressions', function(): void {
        $expression = '{{ user.name | trim }}';

        // First parse
        $result1 = ExpressionParser::parse($expression);

        // Second parse (should be from cache)
        $result2 = ExpressionParser::parse($expression);

        expect($result1)->toBe($result2);
        expect($result1)->toBe([
            'type' => 'expression',
            'path' => 'user.name',
            'default' => null,
            'filters' => ['trim'],
        ]);
    });

    it('tracks cache statistics', function(): void {
        ExpressionParser::parse('{{ user.name }}');
        ExpressionParser::parse('{{ user.email }}');
        ExpressionParser::parse('{{ user.age }}');

        $stats = ExpressionParser::getCacheStats();

        expect($stats['size'])->toBe(3);
        // max_size can vary depending on environment config (1000 default, 250-500 in E2E environments)
        expect($stats['max_size'])->toBeGreaterThanOrEqual(250);
        expect($stats)->toHaveKey('hits');
        expect($stats)->toHaveKey('misses');
    });

    it('clears cache', function(): void {
        ExpressionParser::parse('{{ user.name }}');
        ExpressionParser::parse('{{ user.email }}');

        $stats = ExpressionParser::getCacheStats();
        expect($stats['size'])->toBe(2);

        ExpressionParser::clearCache();

        $stats = ExpressionParser::getCacheStats();
        expect($stats['size'])->toBe(0);
    });

    it('respects cache max entries configuration', function(): void {
        // Note: Cache is initialized on first use, so we need to set config before first parse
        DataHelpersConfig::set('cache.max_entries', 3);

        // Clear cache to force reinitialization with new config
        ExpressionParser::clearCache();

        // Force cache reinitialization by parsing
        ExpressionParser::parse('{{ a }}');

        // Check max size is correct
        $stats = ExpressionParser::getCacheStats();
        expect($stats['max_size'])->toBe(3);

        // Add more entries
        ExpressionParser::parse('{{ b }}');
        ExpressionParser::parse('{{ c }}');

        $stats = ExpressionParser::getCacheStats();
        expect($stats['size'])->toBe(3);

        // Adding 4th entry should trigger LRU eviction
        ExpressionParser::parse('{{ d }}');

        $stats = ExpressionParser::getCacheStats();
        expect($stats['size'])->toBe(3); // Still 3 (LRU removed oldest)
    });

    it('caches null results for invalid expressions', function(): void {
        $invalid = 'not an expression';

        $result1 = ExpressionParser::parse($invalid);
        $result2 = ExpressionParser::parse($invalid);

        expect($result1)->toBeNull();
        expect($result2)->toBeNull();

        $stats = ExpressionParser::getCacheStats();
        expect($stats['size'])->toBe(1); // Null result is cached
    });

    it('caches different expression types', function(): void {
        // Clear cache to ensure clean state
        ExpressionParser::clearCache();

        ExpressionParser::parse('{{ user.name }}'); // Simple
        ExpressionParser::parse('{{ user.name | trim }}'); // With filter
        ExpressionParser::parse('{{ user.name ?? "Unknown" }}'); // With default
        ExpressionParser::parse('{{ @fullname }}'); // Alias
        ExpressionParser::parse('{{ user.tags | join:", " | upper }}'); // Multiple filters

        $stats = ExpressionParser::getCacheStats();
        expect($stats['size'])->toBe(5);
    });

    it('improves performance with caching', function(): void {
        $expression = '{{ user.name | trim | upper | substr:0:10 }}';
        $iterations = 1000;

        // Warm up cache
        ExpressionParser::parse($expression);

        // Measure cached performance
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            ExpressionParser::parse($expression);
        }
        $cachedTime = microtime(true) - $start;

        // Clear cache and measure uncached performance
        ExpressionParser::clearCache();

        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            ExpressionParser::parse($expression);
        }
        $uncachedTime = microtime(true) - $start;

        // Cached should be significantly faster
        expect($cachedTime)->toBeLessThan($uncachedTime * 0.5); // At least 2x faster
    })->skip('Performance test - enable manually');
});

