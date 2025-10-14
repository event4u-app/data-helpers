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
});

