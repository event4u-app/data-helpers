<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\DataMapper\Pipeline\CallbackParameters;
use event4u\DataHelpers\Support\CallbackHelper;

describe('CallbackHelper (DataMapper Integration)', function(): void {
    beforeEach(function(): void {
        CallbackHelper::clear();
        MapperExceptions::reset();
    });

    it('registers and retrieves callbacks', function(): void {
        $callback = fn(CallbackParameters $p): mixed => is_string($p->value) ? strtoupper($p->value) : $p->value;

        CallbackHelper::register('upper', $callback);

        expect(CallbackHelper::has('upper'))->toBeTrue();
        expect(CallbackHelper::get('upper'))->toBe($callback);
    });

    it('throws exception when registering duplicate callback', function(): void {
        $callback = fn(CallbackParameters $p): mixed => $p->value;

        CallbackHelper::register('test', $callback);

        expect(fn() => CallbackHelper::register('test', $callback))
            ->toThrow(InvalidArgumentException::class, 'already registered');
    });

    it('allows overwriting with registerOrReplace', function(): void {
        $callback1 = fn(CallbackParameters $p): string => 'first';
        $callback2 = fn(CallbackParameters $p): string => 'second';

        CallbackHelper::register('test', $callback1);
        CallbackHelper::registerOrReplace('test', $callback2);

        expect(CallbackHelper::get('test'))->toBe($callback2);
    });

    it('unregisters callbacks', function(): void {
        $callback = fn(CallbackParameters $p): mixed => $p->value;

        CallbackHelper::register('test', $callback);
        expect(CallbackHelper::has('test'))->toBeTrue();

        CallbackHelper::unregister('test');
        expect(CallbackHelper::has('test'))->toBeFalse();
    });

    it('clears all callbacks', function(): void {
        CallbackHelper::register('test1', fn($p): mixed => $p->value);
        CallbackHelper::register('test2', fn($p): mixed => $p->value);

        expect(CallbackHelper::count())->toBe(2);

        CallbackHelper::clear();

        expect(CallbackHelper::count())->toBe(0);
        expect(CallbackHelper::has('test1'))->toBeFalse();
        expect(CallbackHelper::has('test2'))->toBeFalse();
    });

    it('returns registered callback names', function(): void {
        CallbackHelper::register(
            'upper',
            fn(CallbackParameters $p): mixed => is_string($p->value) ? strtoupper($p->value) : $p->value
        );
        CallbackHelper::register(
            'lower',
            fn(CallbackParameters $p): mixed => is_string($p->value) ? strtolower($p->value) : $p->value
        );

        $names = CallbackHelper::getRegisteredNames();

        expect($names)->toContain('upper');
        expect($names)->toContain('lower');
        expect(count($names))->toBe(2);
    });

    it('works with template expressions', function(): void {
        // Register callback
        CallbackHelper::register(
            'upper',
            fn(CallbackParameters $p): mixed => is_string($p->value) ? strtoupper($p->value) : $p->value
        );

        $source = [
            'user' => [
                'name' => 'alice',
            ],
        ];

        $template = [
            'profile' => [
                'name' => '{{ user.name | callback:upper }}',
            ],
        ];

        $result = DataMapper::source(['user' => $source['user']])->template($template)->map()->getTarget();

        expect($result)->toBe([
            'profile' => [
                'name' => 'ALICE',
            ],
        ]);
    });

    it('handles missing callback gracefully', function(): void {
        MapperExceptions::setCollectExceptionsEnabled(true);

        $source = [
            'user' => [
                'name' => 'alice',
            ],
        ];

        $template = [
            'profile' => [
                'name' => '{{ user.name | callback:nonexistent }}',
            ],
        ];

        $result = DataMapper::source(['user' => $source['user']])->template($template)->map()->getTarget();

        // Exception should be collected
        expect(MapperExceptions::hasExceptions())->toBeTrue();

        $exception = MapperExceptions::getLastException();
        expect($exception)->not->toBeNull();
        assert($exception instanceof InvalidArgumentException);
        expect($exception->getMessage())->toContain('not registered');

        // Original value should be returned
        expect($result)->toBe([
            'profile' => [
                'name' => 'alice',
            ],
        ]);
    });

    it('handles callback exceptions gracefully', function(): void {
        MapperExceptions::setCollectExceptionsEnabled(true);

        CallbackHelper::register('failing', function(CallbackParameters $p): void {
            throw new RuntimeException('Callback error!');
        });

        $source = [
            'user' => [
                'name' => 'alice',
            ],
        ];

        $template = [
            'profile' => [
                'name' => '{{ user.name | callback:failing }}',
            ],
        ];

        $result = DataMapper::source(['user' => $source['user']])->template($template)->map()->getTarget();

        // Exception should be collected
        expect(MapperExceptions::hasExceptions())->toBeTrue();

        $exception = MapperExceptions::getLastException();
        expect($exception)->not->toBeNull();
        assert($exception instanceof RuntimeException);
        expect($exception->getMessage())->toContain('Callback "failing" failed');

        // Original value should be returned
        expect($result)->toBe([
            'profile' => [
                'name' => 'alice',
            ],
        ]);
    });

    it('can use multiple registered callbacks', function(): void {
        CallbackHelper::register(
            'upper',
            fn(CallbackParameters $p): mixed => is_string($p->value) ? strtoupper($p->value) : $p->value
        );
        CallbackHelper::register(
            'prefix',
            fn(CallbackParameters $p): mixed => is_string($p->value) ? 'Mr. ' . $p->value : $p->value
        );

        $source = [
            'user' => [
                'firstName' => 'john',
                'lastName' => 'doe',
            ],
        ];

        $template = [
            'profile' => [
                'firstName' => '{{ user.firstName | callback:upper }}',
                'lastName' => '{{ user.lastName | callback:prefix }}',
            ],
        ];

        $result = DataMapper::source(['user' => $source['user']])->template($template)->map()->getTarget();

        expect($result)->toBe([
            'profile' => [
                'firstName' => 'JOHN',
                'lastName' => 'Mr. doe',
            ],
        ]);
    });

    it('returns null when getting non-existent callback', function(): void {
        expect(CallbackHelper::get('nonexistent'))->toBeNull();
        expect(CallbackHelper::has('nonexistent'))->toBeFalse();
    });

    it('callback names are case-sensitive', function(): void {
        CallbackHelper::register('Upper', fn($p): mixed => is_string($p->value) ? strtoupper($p->value) : $p->value);

        expect(CallbackHelper::has('Upper'))->toBeTrue();
        expect(CallbackHelper::has('upper'))->toBeFalse();
        expect(CallbackHelper::has('UPPER'))->toBeFalse();
    });

    it('handles special characters in callback names', function(): void {
        CallbackHelper::register('my-callback', fn($p): mixed => $p->value);
        CallbackHelper::register('my_callback', fn($p): mixed => $p->value);
        CallbackHelper::register('my.callback', fn($p): mixed => $p->value);

        expect(CallbackHelper::has('my-callback'))->toBeTrue();
        expect(CallbackHelper::has('my_callback'))->toBeTrue();
        expect(CallbackHelper::has('my.callback'))->toBeTrue();
        expect(CallbackHelper::count())->toBe(3);
    });

    it('works with nested array values', function(): void {
        CallbackHelper::register('processArray', function(CallbackParameters $p): mixed {
            if (is_array($p->value)) {
                return array_map(fn($v): mixed => is_string($v) ? strtoupper($v) : $v, $p->value);
            }
            return $p->value;
        });

        $template = [
            'tags' => '{{ post.tags | callback:processArray }}',
        ];

        $result = DataMapper::source([
            'post' => ['tags' => ['php', 'javascript']],
        ])->template($template)->map()->getTarget();

        expect($result)->toBe([
            'tags' => ['PHP', 'JAVASCRIPT'],
        ]);
    });

    it('can chain callbacks with other filters', function(): void {
        CallbackHelper::register('double', fn($p): mixed => is_numeric($p->value) ? $p->value * 2 : $p->value);
        CallbackHelper::register(
            'roundTwo',
            fn($p): mixed => is_numeric($p->value) ? round((float)$p->value, 2) : $p->value
        );

        $template = [
            'price' => '{{ product.price | callback:double | callback:roundTwo }}',
        ];

        $result = DataMapper::source([
            'product' => ['price' => 10.5],
        ])->template($template)->map()->getTarget();

        expect($result)->toBe([
            'price' => 21.0,
        ]);
    });

    it('handles empty callback registry gracefully', function(): void {
        CallbackHelper::clear();

        expect(CallbackHelper::count())->toBe(0);
        expect(CallbackHelper::getRegisteredNames())->toBe([]);
        expect(CallbackHelper::get('anything'))->toBeNull();
    });

    it('can handle many registered callbacks', function(): void {
        // Register 100 callbacks
        for ($i = 0; 100 > $i; $i++) {
            CallbackHelper::register('callback' . $i, fn($p): mixed => $p->value);
        }

        expect(CallbackHelper::count())->toBe(100);

        // All should be retrievable
        for ($i = 0; 100 > $i; $i++) {
            expect(CallbackHelper::has('callback' . $i))->toBeTrue();
        }

        // Clear should remove all
        CallbackHelper::clear();
        expect(CallbackHelper::count())->toBe(0);
    });
});
