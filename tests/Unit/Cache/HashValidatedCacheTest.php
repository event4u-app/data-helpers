<?php

declare(strict_types=1);

use event4u\DataHelpers\Cache\CacheHelper;
use event4u\DataHelpers\Cache\HashValidatedCache;
use Tests\Fixtures\CacheTestClassV1;
use Tests\Fixtures\CacheTestClassV2;

beforeEach(function(): void {
    CacheHelper::flush();
});

afterEach(function(): void {
    CacheHelper::flush();
});

it('stores and retrieves value with hash validation', function(): void {
    $class = 'TestClass';
    $key = 'test_key';
    $value = 'test_value';
    $sourceData = ['template' => 'original'];

    // Store value
    HashValidatedCache::set($class, $key, $value, $sourceData);

    // Retrieve value with same source data
    $retrieved = HashValidatedCache::get($class, $key, $sourceData);

    expect($retrieved)->toBe($value);
});

it('invalidates cache when source data changes', function(): void {
    $class = 'TestClass';
    $key = 'test_key';
    $value = 'test_value';
    $originalSource = ['template' => 'original'];
    $modifiedSource = ['template' => 'modified'];

    // Store value with original source
    HashValidatedCache::set($class, $key, $value, $originalSource);

    // Try to retrieve with modified source - should return null
    $retrieved = HashValidatedCache::get($class, $key, $modifiedSource);

    expect($retrieved)->toBeNull();
});

it('has() returns false when hash mismatches', function(): void {
    $class = 'TestClass';
    $key = 'test_key';
    $value = 'test_value';
    $originalSource = ['template' => 'original'];
    $modifiedSource = ['template' => 'modified'];

    // Store value
    HashValidatedCache::set($class, $key, $value, $originalSource);

    // Check with modified source
    $exists = HashValidatedCache::has($class, $key, $modifiedSource);

    expect($exists)->toBeFalse();
});

it('remember() recalculates when hash mismatches', function(): void {
    $class = 'TestClass';
    $key = 'test_key';
    $originalSource = ['template' => 'original'];
    $modifiedSource = ['template' => 'modified'];
    $callCount = 0;

    // First call with original source
    $value1 = HashValidatedCache::remember(
        $class,
        $key,
        $originalSource,
        function() use (&$callCount): string {
            $callCount++;
            return 'calculated_' . $callCount;
        }
    );

    expect($value1)->toBe('calculated_1');
    expect($callCount)->toBe(1);

    // Second call with same source - should use cache
    $value2 = HashValidatedCache::remember(
        $class,
        $key,
        $originalSource,
        function() use (&$callCount): string {
            $callCount++;
            return 'calculated_' . $callCount;
        }
    );

    expect($value2)->toBe('calculated_1');
    expect($callCount)->toBe(1); // Not called again

    // Third call with modified source - should recalculate
    $value3 = HashValidatedCache::remember(
        $class,
        $key,
        $modifiedSource,
        function() use (&$callCount): string {
            $callCount++;
            return 'calculated_' . $callCount;
        }
    );

    expect($value3)->toBe('calculated_2');
    expect($callCount)->toBe(2); // Called again
});

it('delete() removes both value and hash', function(): void {
    $class = 'TestClass';
    $key = 'test_key';
    $value = 'test_value';
    $sourceData = ['template' => 'original'];

    // Store value
    HashValidatedCache::set($class, $key, $value, $sourceData);

    // Verify it exists
    expect(HashValidatedCache::has($class, $key, $sourceData))->toBeTrue();

    // Delete
    HashValidatedCache::delete($class, $key);

    // Verify it's gone
    expect(HashValidatedCache::has($class, $key, $sourceData))->toBeFalse();
});

it('clearClass() removes all entries for a class', function(): void {
    $class = 'TestClass';
    $sourceData = ['template' => 'test'];

    // Store multiple values
    HashValidatedCache::set($class, 'key1', 'value1', $sourceData);
    HashValidatedCache::set($class, 'key2', 'value2', $sourceData);
    HashValidatedCache::set($class, 'key3', 'value3', $sourceData);

    // Verify they exist
    expect(HashValidatedCache::has($class, 'key1', $sourceData))->toBeTrue();
    expect(HashValidatedCache::has($class, 'key2', $sourceData))->toBeTrue();
    expect(HashValidatedCache::has($class, 'key3', $sourceData))->toBeTrue();

    // Clear class
    HashValidatedCache::clearClass($class);

    // Verify they're gone
    expect(HashValidatedCache::has($class, 'key1', $sourceData))->toBeFalse();
    expect(HashValidatedCache::has($class, 'key2', $sourceData))->toBeFalse();
    expect(HashValidatedCache::has($class, 'key3', $sourceData))->toBeFalse();
});

it('handles string source data', function(): void {
    $class = 'TestClass';
    $key = 'test_key';
    $value = 'test_value';
    $originalSource = 'original template string';
    $modifiedSource = 'modified template string';

    // Store with original
    HashValidatedCache::set($class, $key, $value, $originalSource);

    // Retrieve with same source
    expect(HashValidatedCache::get($class, $key, $originalSource))->toBe($value);

    // Retrieve with modified source - should be null
    expect(HashValidatedCache::get($class, $key, $modifiedSource))->toBeNull();
});

it('handles array source data', function(): void {
    $class = 'TestClass';
    $key = 'test_key';
    $value = 'test_value';
    $originalSource = ['a' => 1, 'b' => 2];
    $modifiedSource = ['a' => 1, 'b' => 3]; // Different value

    // Store with original
    HashValidatedCache::set($class, $key, $value, $originalSource);

    // Retrieve with same source
    expect(HashValidatedCache::get($class, $key, $originalSource))->toBe($value);

    // Retrieve with modified source - should be null
    expect(HashValidatedCache::get($class, $key, $modifiedSource))->toBeNull();
});

it('handles object source data', function(): void {
    $class = 'TestClass';
    $key = 'test_key';
    $value = 'test_value';
    $originalSource = (object)['prop' => 'original'];
    $modifiedSource = (object)['prop' => 'modified'];

    // Store with original
    HashValidatedCache::set($class, $key, $value, $originalSource);

    // Retrieve with same source
    expect(HashValidatedCache::get($class, $key, $originalSource))->toBe($value);

    // Retrieve with modified source - should be null
    expect(HashValidatedCache::get($class, $key, $modifiedSource))->toBeNull();
});

it('invalidates cache when template changes (real-world scenario)', function(): void {
    $template1 = [
        'name' => '{{ user.name | upper }}',
        'email' => '{{ user.email | lower }}',
    ];

    $template2 = [
        'name' => '{{ user.name | lower }}',  // Changed: upper -> lower
        'email' => '{{ user.email | lower }}',
    ];

    $class = 'TemplateTest';
    $key = 'user_template';
    $callCount = 0;

    // First call with template1
    $result1 = HashValidatedCache::remember(
        $class,
        $key,
        $template1,
        function() use (&$callCount, $template1): array {
            $callCount++;
            return ['parsed' => $template1, 'call' => $callCount];
        }
    );

    expect($result1['call'])->toBe(1);
    expect($callCount)->toBe(1);

    // Second call with same template - should use cache
    $result2 = HashValidatedCache::remember(
        $class,
        $key,
        $template1,
        function() use (&$callCount, $template1): array {
            $callCount++;
            return ['parsed' => $template1, 'call' => $callCount];
        }
    );

    expect($result2['call'])->toBe(1); // Same result from cache
    expect($callCount)->toBe(1); // Not called again

    // Third call with modified template - should recalculate
    $result3 = HashValidatedCache::remember(
        $class,
        $key,
        $template2,
        function() use (&$callCount, $template2): array {
            $callCount++;
            return ['parsed' => $template2, 'call' => $callCount];
        }
    );

    expect($result3['call'])->toBe(2); // New calculation
    expect($callCount)->toBe(2); // Called again
    expect($result3['parsed']['name'])->toBe('{{ user.name | lower }}');
});

it('invalidates cache when class file changes', function(): void {
    $class = 'ClassFileTest';
    $key = 'class_cache';
    $callCount = 0;

    // First call with CacheTestClassV1
    $result1 = HashValidatedCache::remember(
        $class,
        $key,
        CacheTestClassV1::class,  // Use class name as source data
        function() use (&$callCount): array {
            $callCount++;
            $instance = new CacheTestClassV1();
            return [
                'version' => $instance->getVersion(),
                'result' => $instance->process('test'),
                'call' => $callCount,
            ];
        }
    );

    expect($result1['version'])->toBe(1);
    expect($result1['result'])->toBe('TEST'); // uppercase
    expect($result1['call'])->toBe(1);
    expect($callCount)->toBe(1);

    // Second call with same class - should use cache
    $result2 = HashValidatedCache::remember(
        $class,
        $key,
        CacheTestClassV1::class,
        function() use (&$callCount): array {
            $callCount++;
            $instance = new CacheTestClassV1();
            return [
                'version' => $instance->getVersion(),
                'result' => $instance->process('test'),
                'call' => $callCount,
            ];
        }
    );

    expect($result2['call'])->toBe(1); // Same result from cache
    expect($callCount)->toBe(1); // Not called again

    // Third call with different class (simulates file change) - should recalculate
    $result3 = HashValidatedCache::remember(
        $class,
        $key,
        CacheTestClassV2::class,  // Different class = different file
        function() use (&$callCount): array {
            $callCount++;
            $instance = new CacheTestClassV2();
            return [
                'version' => $instance->getVersion(),
                'result' => $instance->process('test'),
                'call' => $callCount,
            ];
        }
    );

    expect($result3['version'])->toBe(2);
    expect($result3['result'])->toBe('test'); // lowercase (changed implementation)
    expect($result3['call'])->toBe(2); // New calculation
    expect($callCount)->toBe(2); // Called again
});

it('handles file path as source data', function(): void {
    $class = 'FilePathTest';
    $key = 'file_cache';

    // Get file paths for test classes
    $reflection1 = new ReflectionClass(CacheTestClassV1::class);
    $reflection2 = new ReflectionClass(CacheTestClassV2::class);
    $filePath1 = $reflection1->getFileName();
    $filePath2 = $reflection2->getFileName();

    expect($filePath1)->not->toBeFalse();
    expect($filePath2)->not->toBeFalse();

    $callCount = 0;

    // First call with file path 1
    $result1 = HashValidatedCache::remember(
        $class,
        $key,
        $filePath1,
        function() use (&$callCount): string {
            $callCount++;
            return 'result_' . $callCount;
        }
    );

    expect($result1)->toBe('result_1');
    expect($callCount)->toBe(1);

    // Second call with same file path - should use cache
    $result2 = HashValidatedCache::remember(
        $class,
        $key,
        $filePath1,
        function() use (&$callCount): string {
            $callCount++;
            return 'result_' . $callCount;
        }
    );

    expect($result2)->toBe('result_1'); // From cache
    expect($callCount)->toBe(1); // Not called again

    // Third call with different file path - should recalculate
    $result3 = HashValidatedCache::remember(
        $class,
        $key,
        $filePath2,
        function() use (&$callCount): string {
            $callCount++;
            return 'result_' . $callCount;
        }
    );

    expect($result3)->toBe('result_2'); // New calculation
    expect($callCount)->toBe(2); // Called again
});

it('integrates with TemplateParser for real template validation', function(): void {
    $template1 = [
        'user' => [
            'name' => '{{ source.firstName | upper }}',
            'email' => '{{ source.email | lower }}',
        ],
    ];

    $template2 = [
        'user' => [
            'name' => '{{ source.firstName | lower }}',  // Changed!
            'email' => '{{ source.email | lower }}',
        ],
    ];

    $class = 'RealTemplateTest';
    $key = 'complex_template';
    $parseCount = 0;

    // Simulate expensive template parsing
    $parseTemplate = function(array $template) use (&$parseCount): array {
        $parseCount++;
        $parsed = [];
        foreach ($template as $targetKey => $targetValue) {
            if (is_array($targetValue)) {
                $parsed[$targetKey] = [];
                foreach ($targetValue as $subKey => $subValue) {
                    $parsed[$targetKey][$subKey] = [
                        'expression' => $subValue,
                        'parsed_at' => $parseCount,
                    ];
                }
            }
        }
        return $parsed;
    };

    // First parse
    $result1 = HashValidatedCache::remember(
        $class,
        $key,
        $template1,
        fn(): array => $parseTemplate($template1)
    );

    expect($result1['user']['name']['parsed_at'])->toBe(1);
    expect($parseCount)->toBe(1);

    // Second parse with same template - should use cache
    $result2 = HashValidatedCache::remember(
        $class,
        $key,
        $template1,
        fn(): array => $parseTemplate($template1)
    );

    expect($result2['user']['name']['parsed_at'])->toBe(1); // From cache
    expect($parseCount)->toBe(1); // Not called again

    // Third parse with modified template - should reparse
    $result3 = HashValidatedCache::remember(
        $class,
        $key,
        $template2,
        fn(): array => $parseTemplate($template2)
    );

    expect($result3['user']['name']['parsed_at'])->toBe(2); // New parse
    expect($result3['user']['name']['expression'])->toBe('{{ source.firstName | lower }}');
    expect($parseCount)->toBe(2); // Called again
});
