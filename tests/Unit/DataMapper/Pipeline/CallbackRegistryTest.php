<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\DataMapper\Pipeline\CallbackParameters;
use event4u\DataHelpers\DataMapper\Pipeline\CallbackRegistry;

describe('CallbackRegistry', function(): void {
    beforeEach(function(): void {
        CallbackRegistry::clear();
        MapperExceptions::reset();
    });

    it('registers and retrieves callbacks', function(): void {
        $callback = fn(CallbackParameters $p): mixed => is_string($p->value) ? strtoupper($p->value) : $p->value;

        CallbackRegistry::register('upper', $callback);

        expect(CallbackRegistry::has('upper'))->toBeTrue();
        expect(CallbackRegistry::get('upper'))->toBe($callback);
    });

    it('throws exception when registering duplicate callback', function(): void {
        $callback = fn(CallbackParameters $p): mixed => $p->value;

        CallbackRegistry::register('test', $callback);

        expect(fn() => CallbackRegistry::register('test', $callback))
            ->toThrow(InvalidArgumentException::class, 'already registered');
    });

    it('allows overwriting with registerOrReplace', function(): void {
        $callback1 = fn(CallbackParameters $p): string => 'first';
        $callback2 = fn(CallbackParameters $p): string => 'second';

        CallbackRegistry::register('test', $callback1);
        CallbackRegistry::registerOrReplace('test', $callback2);

        expect(CallbackRegistry::get('test'))->toBe($callback2);
    });

    it('unregisters callbacks', function(): void {
        $callback = fn(CallbackParameters $p): mixed => $p->value;

        CallbackRegistry::register('test', $callback);
        expect(CallbackRegistry::has('test'))->toBeTrue();

        CallbackRegistry::unregister('test');
        expect(CallbackRegistry::has('test'))->toBeFalse();
    });

    it('clears all callbacks', function(): void {
        CallbackRegistry::register('test1', fn($p): mixed => $p->value);
        CallbackRegistry::register('test2', fn($p): mixed => $p->value);

        expect(CallbackRegistry::count())->toBe(2);

        CallbackRegistry::clear();

        expect(CallbackRegistry::count())->toBe(0);
        expect(CallbackRegistry::has('test1'))->toBeFalse();
        expect(CallbackRegistry::has('test2'))->toBeFalse();
    });

    it('returns registered callback names', function(): void {
        CallbackRegistry::register(
            'upper',
            fn(CallbackParameters $p): mixed => is_string($p->value) ? strtoupper($p->value) : $p->value
        );
        CallbackRegistry::register(
            'lower',
            fn(CallbackParameters $p): mixed => is_string($p->value) ? strtolower($p->value) : $p->value
        );

        $names = CallbackRegistry::getRegisteredNames();

        expect($names)->toContain('upper');
        expect($names)->toContain('lower');
        expect(count($names))->toBe(2);
    });

    it('works with template expressions', function(): void {
        // Register callback
        CallbackRegistry::register(
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

        $result = DataMapper::mapFromTemplate($template, ['user' => $source['user']]);

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

        $result = DataMapper::mapFromTemplate($template, ['user' => $source['user']]);

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

        CallbackRegistry::register('failing', function(CallbackParameters $p): void {
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

        $result = DataMapper::mapFromTemplate($template, ['user' => $source['user']]);

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
        CallbackRegistry::register(
            'upper',
            fn(CallbackParameters $p): mixed => is_string($p->value) ? strtoupper($p->value) : $p->value
        );
        CallbackRegistry::register(
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

        $result = DataMapper::mapFromTemplate($template, ['user' => $source['user']]);

        expect($result)->toBe([
            'profile' => [
                'firstName' => 'JOHN',
                'lastName' => 'Mr. doe',
            ],
        ]);
    });

    it('returns null when getting non-existent callback', function(): void {
        expect(CallbackRegistry::get('nonexistent'))->toBeNull();
        expect(CallbackRegistry::has('nonexistent'))->toBeFalse();
    });

    it('callback names are case-sensitive', function(): void {
        CallbackRegistry::register('Upper', fn($p): mixed => is_string($p->value) ? strtoupper($p->value) : $p->value);

        expect(CallbackRegistry::has('Upper'))->toBeTrue();
        expect(CallbackRegistry::has('upper'))->toBeFalse();
        expect(CallbackRegistry::has('UPPER'))->toBeFalse();
    });

    it('handles special characters in callback names', function(): void {
        CallbackRegistry::register('my-callback', fn($p): mixed => $p->value);
        CallbackRegistry::register('my_callback', fn($p): mixed => $p->value);
        CallbackRegistry::register('my.callback', fn($p): mixed => $p->value);

        expect(CallbackRegistry::has('my-callback'))->toBeTrue();
        expect(CallbackRegistry::has('my_callback'))->toBeTrue();
        expect(CallbackRegistry::has('my.callback'))->toBeTrue();
        expect(CallbackRegistry::count())->toBe(3);
    });

    it('works with nested array values', function(): void {
        CallbackRegistry::register('processArray', function(CallbackParameters $p): mixed {
            if (is_array($p->value)) {
                return array_map(fn($v): mixed => is_string($v) ? strtoupper($v) : $v, $p->value);
            }
            return $p->value;
        });

        $template = [
            'tags' => '{{ post.tags | callback:processArray }}',
        ];

        $result = DataMapper::mapFromTemplate($template, [
            'post' => ['tags' => ['php', 'javascript']],
        ]);

        expect($result)->toBe([
            'tags' => ['PHP', 'JAVASCRIPT'],
        ]);
    });

    it('can chain callbacks with other filters', function(): void {
        CallbackRegistry::register('double', fn($p): mixed => is_numeric($p->value) ? $p->value * 2 : $p->value);
        CallbackRegistry::register(
            'roundTwo',
            fn($p): mixed => is_numeric($p->value) ? round((float)$p->value, 2) : $p->value
        );

        $template = [
            'price' => '{{ product.price | callback:double | callback:roundTwo }}',
        ];

        $result = DataMapper::mapFromTemplate($template, [
            'product' => ['price' => 10.5],
        ]);

        expect($result)->toBe([
            'price' => 21.0,
        ]);
    });

    it('handles empty callback registry gracefully', function(): void {
        CallbackRegistry::clear();

        expect(CallbackRegistry::count())->toBe(0);
        expect(CallbackRegistry::getRegisteredNames())->toBe([]);
        expect(CallbackRegistry::get('anything'))->toBeNull();
    });

    it('can handle many registered callbacks', function(): void {
        // Register 100 callbacks
        for ($i = 0; 100 > $i; $i++) {
            CallbackRegistry::register('callback' . $i, fn($p): mixed => $p->value);
        }

        expect(CallbackRegistry::count())->toBe(100);

        // All should be retrievable
        for ($i = 0; 100 > $i; $i++) {
            expect(CallbackRegistry::has('callback' . $i))->toBeTrue();
        }

        // Clear should remove all
        CallbackRegistry::clear();
        expect(CallbackRegistry::count())->toBe(0);
    });
});

