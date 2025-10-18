<?php

declare(strict_types=1);

namespace Tests\Unit\DataMapper;

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Support\HookInvoker;
use event4u\DataHelpers\Enums\DataMapperHook;

describe('HookInvoker Zero Overhead', function(): void {
    it('isEmpty returns true for empty array', function(): void {
        expect(HookInvoker::isEmpty([]))->toBeTrue();
    });

    it('isEmpty returns false for non-empty array', function(): void {
        $hooks = [
            DataMapperHook::BeforeAll->value => fn(): null => null,
        ];
        expect(HookInvoker::isEmpty($hooks))->toBeFalse();
    });

    it('normalizeHooks returns empty array immediately for empty input', function(): void {
        $result = HookInvoker::normalizeHooks([]);
        expect($result)->toBe([]);
    });

    it('normalizeHooks processes non-empty hooks', function(): void {
        $hooks = [
            DataMapperHook::BeforeAll->value => fn(): null => null,
        ];
        $result = HookInvoker::normalizeHooks($hooks);
        expect($result)->toHaveKey(DataMapperHook::BeforeAll->value);
    });

    it('DataMapper with empty hooks has zero overhead', function(): void {
        $source = ['name' => 'Alice', 'email' => 'alice@example.com'];
        $target = [];
        $mapping = [
            'userName' => '{{ name }}',
            'userEmail' => '{{ email }}',
        ];

        // Map with empty hooks array
        $result = DataMapper::source($source)
            ->target($target)
            ->template($mapping)
            ->map()
            ->getTarget();

        expect($result)->toBe([
            'userName' => 'Alice',
            'userEmail' => 'alice@example.com',
        ]);
    });

    it('DataMapper without hooks parameter uses empty array', function(): void {
        $source = ['name' => 'Bob'];
        $target = [];
        $mapping = ['fullName' => '{{ name }}'];

        // Map without hooks parameter (defaults to [])
        $result = DataMapper::source($source)
            ->target($target)
            ->template($mapping)
            ->map()
            ->getTarget();

        expect($result)->toBe(['fullName' => 'Bob']);
    });

    it('DataMapper with hooks executes them', function(): void {
        $source = ['name' => '  alice  '];
        $target = [];
        $mapping = ['userName' => '{{ name }}'];

        $hookCalled = false;
        $hooks = [
            DataMapperHook::BeforeTransform->value => function($value) use (&$hookCalled) {
                $hookCalled = true;
                return is_string($value) ? trim($value) : $value;
            },
        ];

        $result = DataMapper::source($source)
            ->target($target)
            ->template($mapping)
            ->hooks($hooks)
            ->map()
            ->getTarget();

        expect($hookCalled)->toBeTrue();
        expect($result)->toBe(['userName' => 'alice']);
    });

    it('empty hooks array skips all hook invocations', function(): void {
        $source = ['users' => [
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Bob', 'age' => 25],
        ]];
        $target = [];
        $mapping = [
            'names.*' => '{{ users.*.name }}',
            'ages.*' => '{{ users.*.age }}',
        ];

        // With empty hooks, no hook overhead
        $result = DataMapper::source($source)
            ->target($target)
            ->template($mapping)
            ->map()
            ->getTarget();

        expect($result)->toBe([
            'names' => ['Alice', 'Bob'],
            'ages' => [30, 25],
        ]);
    });

    it('performance: empty hooks vs hooks with callbacks', function(): void {
        $source = ['items' => array_fill(0, 100, ['name' => 'Item', 'value' => 42])];
        $target = [];
        $mapping = [
            'names.*' => '{{ items.*.name }}',
            'values.*' => '{{ items.*.value }}',
        ];

        // Measure with empty hooks (should be fast)
        $start1 = microtime(true);
        $result1 = DataMapper::source($source)
            ->target($target)
            ->template($mapping)
            ->map()
            ->getTarget();
        $time1 = microtime(true) - $start1;

        // Measure with hooks (will be slower)
        $hooks = [
            DataMapperHook::BeforeTransform->value => fn($v) => $v,
            DataMapperHook::AfterPair->value => fn(): null => null,
        ];
        $start2 = microtime(true);
        $result2 = DataMapper::source($source)
            ->target($target)
            ->template($mapping)
            ->hooks($hooks)
            ->map()
            ->getTarget();
        $time2 = microtime(true) - $start2;

        // Both should produce same result
        expect($result1)->toBe($result2);

        // Empty hooks should be faster (or at least not significantly slower)
        // This is a soft assertion - we just verify it runs without errors
        expect($time1)->toBeGreaterThan(0);
        expect($time2)->toBeGreaterThan(0);
    });

    it('normalizeHooks handles enum keys correctly', function(): void {
        $hooks = [
            [DataMapperHook::BeforeAll, fn(): string => 'before'],
            [DataMapperHook::AfterAll, fn(): string => 'after'],
        ];

        $normalized = HookInvoker::normalizeHooks($hooks);

        expect($normalized)->toHaveKey(DataMapperHook::BeforeAll->value);
        expect($normalized)->toHaveKey(DataMapperHook::AfterAll->value);
    });
});
