<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\DataMapper\Pipeline\CallbackParameters;
use event4u\DataHelpers\DataMapper\Pipeline\CallbackRegistry;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CallbackFilter;

describe('CallbackFilter', function(): void {
    beforeEach(function(): void {
        MapperExceptions::reset();
        CallbackRegistry::clear();
    });

    it('transforms values using callback in pipeline', function(): void {
        $source = [
            'user' => [
                'name' => 'alice',
                'email' => 'ALICE@EXAMPLE.COM',
            ],
        ];

        $mapping = [
            'profile.name' => '{{ user.name }}',
            'profile.email' => '{{ user.email }}',
        ];

        $result = DataMapper::source($source)->target([])->template($mapping)->pipe(
            [new CallbackFilter(fn(CallbackParameters $params): mixed => is_string($params->value) ? strtoupper(
                $params->value
            ) : $params->value),]
        )->map()->getTarget();

        expect($result)->toBe([
            'profile' => [
                'name' => 'ALICE',
                'email' => 'ALICE@EXAMPLE.COM',
            ],
        ]);
    });

    it('provides complete context in CallbackParameters', function(): void {
        $source = [
            'user' => [
                'name' => 'Bob',
                'age' => 25,
            ],
        ];

        $mapping = [
            'profile.displayName' => '{{ user.name }}',
        ];

        $capturedParams = null;

        DataMapper::source($source)->target([])->template($mapping)->pipe(
            [new CallbackFilter(function(CallbackParameters $params) use (&$capturedParams) {
                $capturedParams = $params;
                return $params->value;
            }),]
        )->map()->getTarget();

        expect($capturedParams)->not->toBeNull();
        assert($capturedParams instanceof CallbackParameters);
        expect($capturedParams->value)->toBe('Bob');
        expect($capturedParams->key)->toBe('displayName');
        expect($capturedParams->keyPath)->toBe('profile.displayName');
        expect($capturedParams->source)->toBe($source);
    });

    it('can return __skip__ to skip values', function(): void {
        $source = [
            'user' => [
                'name' => 'Alice',
                'email' => '',
                'phone' => '123-456',
            ],
        ];

        $mapping = [
            'profile.name' => '{{ user.name }}',
            'profile.email' => '{{ user.email }}',
            'profile.phone' => '{{ user.phone }}',
        ];

        $result = DataMapper::source($source)->target([])->template($mapping)->pipe([new CallbackFilter(function(
            CallbackParameters $params
        ) {
                // Skip empty values
                if ('' === $params->value) {
                    return '__skip__';
                }
                return $params->value;
            }),])->map()->getTarget();

        expect($result)->toBe([
            'profile' => [
                'name' => 'Alice',
                'phone' => '123-456',
                // email is skipped
            ],
        ]);
    });

    it('handles exceptions in callback and collects them', function(): void {
        MapperExceptions::setCollectExceptionsEnabled(true);

        $source = [
            'user' => [
                'name' => 'Alice',
            ],
        ];

        $mapping = [
            'profile.name' => '{{ user.name }}',
        ];

        // Exception should be collected, NOT thrown
        $result = DataMapper::source($source)->target([])->template($mapping)->pipe(
            [new CallbackFilter(function(CallbackParameters $params): void {
                throw new RuntimeException('Callback failed!');
            }),]
        )->map();

        // Exception should be collected
        expect(MapperExceptions::hasExceptions())->toBeTrue();
        $exception = MapperExceptions::getLastException();
        expect($exception)->toBeInstanceOf(RuntimeException::class);
        expect($exception->getMessage())->toContain('Callback filter failed');
        expect($exception->getMessage())->toContain('Callback failed!');
    });

    it('can transform based on key path', function(): void {
        $source = [
            'user' => [
                'email' => 'ALICE@EXAMPLE.COM',
                'name' => 'alice',
            ],
        ];

        $mapping = [
            'profile.email' => '{{ user.email }}',
            'profile.name' => '{{ user.name }}',
        ];

        $result = DataMapper::source($source)->target([])->template($mapping)->pipe([new CallbackFilter(function(
            CallbackParameters $params
        ) {
                // Only lowercase emails
                if ('email' === $params->key && is_string($params->value)) {
                    return strtolower($params->value);
                }
                return $params->value;
            }),])->map()->getTarget();

        expect($result)->toBe([
            'profile' => [
                'email' => 'alice@example.com',
                'name' => 'alice',
            ],
        ]);
    });

    it('works with multiple callbacks in pipeline', function(): void {
        $source = [
            'user' => [
                'name' => '  alice  ',
            ],
        ];

        $mapping = [
            'profile.name' => '{{ user.name }}',
        ];

        $result = DataMapper::source($source)->target([])->template($mapping)->pipe(
            [new CallbackFilter(fn(CallbackParameters $p): mixed => is_string($p->value) ? trim($p->value) : $p->value),
            new CallbackFilter(fn(CallbackParameters $p): mixed => is_string($p->value) ? strtoupper(
                $p->value
            ) : $p->value),]
        )->map()->getTarget();

        expect($result)->toBe([
            'profile' => [
                'name' => 'ALICE',
            ],
        ]);
    });

    it('handles nested data structures', function(): void {
        $source = [
            'company' => [
                'departments' => [
                    ['name' => 'sales', 'employees' => 10],
                    ['name' => 'engineering', 'employees' => 25],
                ],
            ],
        ];

        $mapping = [
            'org.teams' => '{{ company.departments }}',
        ];

        $result = DataMapper::source($source)->target([])->template($mapping)->pipe([new CallbackFilter(function(
            CallbackParameters $params
        ) {
                // Uppercase department names in nested arrays
                if (is_array($params->value)) {
                    return array_map(function($dept) {
                        if (is_array($dept) && isset($dept['name']) && is_string($dept['name'])) {
                            $dept['name'] = strtoupper($dept['name']);
                        }
                        return $dept;
                    }, $params->value);
                }
                return $params->value;
            }),])->map()->getTarget();

        expect($result)->toBe([
            'org' => [
                'teams' => [
                    ['name' => 'SALES', 'employees' => 10],
                    ['name' => 'ENGINEERING', 'employees' => 25],
                ],
            ],
        ]);
    });

    it('handles null and empty values correctly', function(): void {
        $source = [
            'data' => [
                'name' => '',
                'age' => null,
                'active' => false,
                'count' => 0,
            ],
        ];

        $mapping = [
            'result.name' => '{{ data.name }}',
            'result.age' => '{{ data.age }}',
            'result.active' => '{{ data.active }}',
            'result.count' => '{{ data.count }}',
        ];

        $result = DataMapper::source($source)->target([])->template($mapping)->pipe([new CallbackFilter(function(
            CallbackParameters $params
        ) {
                // Only skip null, not empty string or false or 0
                if (null === $params->value) {
                    return '__skip__';
                }
                return $params->value;
            }),])->map()->getTarget();

        expect($result)->toBe([
            'result' => [
                'name' => '',
                'active' => false,
                'count' => 0,
                // age is skipped (null)
            ],
        ]);
    });

    it('works with array values', function(): void {
        $source = [
            'user' => [
                'tags' => ['php', 'javascript', 'python'],
            ],
        ];

        $mapping = [
            'profile.tags' => '{{ user.tags }}',
        ];

        $result = DataMapper::source($source)->target([])->template($mapping)->pipe([new CallbackFilter(function(
            CallbackParameters $params
        ) {
                // Uppercase all array elements
                if (is_array($params->value)) {
                    return array_map(fn($v): mixed => is_string($v) ? strtoupper($v) : $v, $params->value);
                }
                return $params->value;
            }),])->map()->getTarget();

        expect($result)->toBe([
            'profile' => [
                'tags' => ['PHP', 'JAVASCRIPT', 'PYTHON'],
            ],
        ]);
    });

    it('handles numeric keys', function(): void {
        $source = [
            'items' => [
                ['id' => 1, 'name' => 'apple'],
                ['id' => 2, 'name' => 'banana'],
            ],
        ];

        $mapping = [
            'products.*' => '{{ items.* }}',
        ];

        $capturedKeys = [];

        $result = DataMapper::source($source)->target([])->template($mapping)->pipe(
            [new CallbackFilter(function(CallbackParameters $params) use (&$capturedKeys) {
                $capturedKeys[] = $params->key;
                return $params->value;
            }),]
        )->map()->getTarget();

        // Should capture keys (might be 'products' for the array itself)
        expect($capturedKeys)->not->toBeEmpty();
        expect($result['products'])->toHaveCount(2);
    });

    it('can return null without skipping', function(): void {
        $source = [
            'user' => [
                'name' => 'Alice',
            ],
        ];

        $mapping = [
            'profile.name' => '{{ user.name }}',
        ];

        $result = DataMapper::source($source)->target([])->template(
            $mapping
        )->pipe([new CallbackFilter(fn(CallbackParameters $params): null =>
                // Explicitly return null (not __skip__)
                null),])->map()->getTarget();

        expect($result)->toBe([
            'profile' => [
                'name' => null,
            ],
        ]);
    });

    it('handles multiple __skip__ in sequence', function(): void {
        $source = [
            'user' => [
                'name' => '',
                'email' => 'test@example.com',
            ],
        ];

        $mapping = [
            'profile.name' => '{{ user.name }}',
            'profile.email' => '{{ user.email }}',
        ];

        $result = DataMapper::source($source)->target([])->template($mapping)->pipe([new CallbackFilter(function(
            CallbackParameters $params
        ) {
                if ('' === $params->value) {
                    return '__skip__';
                }
                return $params->value;
            }),
            new CallbackFilter(fn(CallbackParameters $params): mixed =>
                // This should NOT be called for skipped values
                // because __skip__ is handled before the next filter
                is_string($params->value) ? strtoupper($params->value) : $params->value),])->map()->getTarget();

        expect($result)->toBe([
            'profile' => [
                'email' => 'TEST@EXAMPLE.COM',
                // name is skipped
            ],
        ]);
    });
});

