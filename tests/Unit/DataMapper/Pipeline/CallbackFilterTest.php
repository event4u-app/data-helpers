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

        $result = DataMapper::pipe([
            new CallbackFilter(fn(CallbackParameters $params): mixed => is_string($params->value) ? strtoupper(
                $params->value
            ) : $params->value),
        ])->map($source, [], $mapping);

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

        DataMapper::pipe([
            new CallbackFilter(function(CallbackParameters $params) use (&$capturedParams) {
                $capturedParams = $params;
                return $params->value;
            }),
        ])->map($source, [], $mapping);

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

        $result = DataMapper::pipe([
            new CallbackFilter(function(CallbackParameters $params) {
                // Skip empty values
                if ('' === $params->value) {
                    return '__skip__';
                }
                return $params->value;
            }),
        ])->map($source, [], $mapping);

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

        try {
            $result = DataMapper::pipe([
                new CallbackFilter(function(CallbackParameters $params): void {
                    throw new RuntimeException('Callback failed!');
                }),
            ])->map($source, [], $mapping);

            // Should not reach here
            expect(true)->toBeFalse();
        } catch (RuntimeException $runtimeException) {
            // Exception should be thrown at the end
            expect($runtimeException->getMessage())->toContain('Callback filter failed');
            expect($runtimeException->getMessage())->toContain('Callback failed!');
        }
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

        $result = DataMapper::pipe([
            new CallbackFilter(function(CallbackParameters $params) {
                // Only lowercase emails
                if ('email' === $params->key && is_string($params->value)) {
                    return strtolower($params->value);
                }
                return $params->value;
            }),
        ])->map($source, [], $mapping);

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

        $result = DataMapper::pipe([
            new CallbackFilter(fn(CallbackParameters $p): mixed => is_string($p->value) ? trim($p->value) : $p->value),
            new CallbackFilter(fn(CallbackParameters $p): mixed => is_string($p->value) ? strtoupper(
                $p->value
            ) : $p->value),
        ])->map($source, [], $mapping);

        expect($result)->toBe([
            'profile' => [
                'name' => 'ALICE',
            ],
        ]);
    });
});

