<?php

declare(strict_types=1);

use event4u\DataHelpers\Cache\ClassScopedCache;
use event4u\DataHelpers\Cache\CacheHelper;
use event4u\DataHelpers\DataMapper\Support\TemplateParser;

beforeEach(function(): void {
    CacheHelper::flush();
});

afterEach(function(): void {
    CacheHelper::flush();
});

it('TemplateParser uses hash validation for template caching', function(): void {
    $template1 = [
        'name' => '{{ user.name | upper }}',
        'email' => '{{ user.email | lower }}',
    ];

    $template2 = [
        'name' => '{{ user.name | lower }}',  // Changed!
        'email' => '{{ user.email | lower }}',
    ];

    // First parse
    $result1 = TemplateParser::parseMapping($template1);

    expect($result1)->toHaveKey('name');
    expect($result1)->toHaveKey('email');

    // Get cache stats - should have 1 entry
    $stats1 = ClassScopedCache::getClassStats(TemplateParser::class);
    $initialCount = $stats1['count'];

    expect($initialCount)->toBeGreaterThan(0);

    // Second parse with same template - should use cache
    $result2 = TemplateParser::parseMapping($template1);

    expect($result2)->toBe($result1);

    // Cache count should be the same (no new entries)
    $stats2 = ClassScopedCache::getClassStats(TemplateParser::class);
    expect($stats2['count'])->toBe($initialCount);

    // Third parse with modified template - should invalidate and reparse
    $result3 = TemplateParser::parseMapping($template2);

    expect($result3)->toHaveKey('name');
    expect($result3)->toHaveKey('email');
    expect($result3)->not->toBe($result1); // Different result

    // Cache should have new entry (old one invalidated)
    $stats3 = ClassScopedCache::getClassStats(TemplateParser::class);
    expect($stats3['count'])->toBeGreaterThanOrEqual($initialCount);
});

it('TemplateParser invalidates cache when template structure changes', function(): void {
    $template1 = [
        'user.name' => '{{ source.name }}',
    ];

    $template2 = [
        'user.name' => '{{ source.name }}',
        'user.email' => '{{ source.email }}',  // Added new field!
    ];

    // Parse first template
    $result1 = TemplateParser::parseMapping($template1);

    expect($result1)->toHaveKey('user.name');
    expect($result1)->not->toHaveKey('user.email');

    // Parse second template with additional field
    $result2 = TemplateParser::parseMapping($template2);

    expect($result2)->toHaveKey('user.name');
    expect($result2)->toHaveKey('user.email'); // New field present
    expect($result2)->not->toBe($result1);
});

it('TemplateParser invalidates cache when filter changes', function(): void {
    $template1 = [
        'name' => '{{ user.name | upper }}',
    ];

    $template2 = [
        'name' => '{{ user.name | upper | trim }}',  // Added trim filter!
    ];

    $template3 = [
        'name' => '{{ user.name | lower }}',  // Changed filter!
    ];

    // Parse with single filter
    $result1 = TemplateParser::parseMapping($template1);
    expect($result1)->toHaveKey('name');

    // Parse with additional filter - should invalidate
    $result2 = TemplateParser::parseMapping($template2);
    expect($result2)->toHaveKey('name');
    expect($result2)->not->toBe($result1);

    // Parse with different filter - should invalidate
    $result3 = TemplateParser::parseMapping($template3);
    expect($result3)->toHaveKey('name');
    expect($result3)->not->toBe($result1);
    expect($result3)->not->toBe($result2);
});

it('TemplateParser invalidates cache when static marker changes', function(): void {
    $template = [
        'name' => 'John Doe',  // Static value
    ];

    // Parse with default static marker
    $result1 = TemplateParser::parseMapping($template);

    expect($result1)->toHaveKey('name');
    expect($result1['name'])->toBeArray();
    expect($result1['name'])->toHaveKey('__static__');

    // Parse with custom static marker
    $result2 = TemplateParser::parseMapping($template, '__custom__');

    expect($result2)->toHaveKey('name');
    expect($result2['name'])->toBeArray();
    expect($result2['name'])->toHaveKey('__custom__');
    expect($result2)->not->toBe($result1);
});

it('TemplateParser cache persists across multiple calls with same template', function(): void {
    $template = [
        'user.name' => '{{ source.firstName | upper }}',
        'user.email' => '{{ source.email | lower }}',
        'user.age' => '{{ source.age | default:18 }}',
        'metadata.created' => '{{ source.createdAt }}',
    ];

    // Parse multiple times
    $result1 = TemplateParser::parseMapping($template);
    $result2 = TemplateParser::parseMapping($template);
    $result3 = TemplateParser::parseMapping($template);
    $result4 = TemplateParser::parseMapping($template);
    $result5 = TemplateParser::parseMapping($template);

    // All results should be identical (from cache)
    expect($result2)->toBe($result1);
    expect($result3)->toBe($result1);
    expect($result4)->toBe($result1);
    expect($result5)->toBe($result1);

    // Verify structure
    expect($result1)->toHaveKey('user.name');
    expect($result1)->toHaveKey('user.email');
    expect($result1)->toHaveKey('user.age');
    expect($result1)->toHaveKey('metadata.created');
});

it('TemplateParser handles complex nested templates with hash validation', function(): void {
    $template1 = [
        'level1.level2.level3.value' => '{{ deep.nested.value | upper }}',
    ];

    $template2 = [
        'level1.level2.level3.value' => '{{ deep.nested.value | lower }}',  // Changed!
    ];

    // Parse first template
    $result1 = TemplateParser::parseMapping($template1);

    expect($result1)->toHaveKey('level1.level2.level3.value');

    // Parse second template - should invalidate due to filter change
    $result2 = TemplateParser::parseMapping($template2);

    expect($result2)->toHaveKey('level1.level2.level3.value');
    expect($result2)->not->toBe($result1);
});

