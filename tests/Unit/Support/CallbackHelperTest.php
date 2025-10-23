<?php

declare(strict_types=1);

use event4u\DataHelpers\Support\CallbackHelper;

describe('CallbackHelper', function(): void {
    describe('Registration', function(): void {
        beforeEach(function(): void {
            CallbackHelper::clear();
        });

        it('registers a callback', function(): void {
            CallbackHelper::register('test', fn(): string => 'result');

            expect(CallbackHelper::has('test'))->toBeTrue();
            expect(CallbackHelper::count())->toBe(1);
        });

        it('throws exception when registering duplicate callback', function(): void {
            CallbackHelper::register('test', fn(): string => 'result');

            expect(fn() => CallbackHelper::register('test', fn(): string => 'other'))
                ->toThrow(InvalidArgumentException::class, 'Callback "test" is already registered');
        });

        it('allows overwriting with registerOrReplace', function(): void {
            CallbackHelper::register('test', fn(): string => 'first');
            CallbackHelper::registerOrReplace('test', fn(): string => 'second');

            $result = CallbackHelper::execute('test');
            expect($result)->toBe('second');
        });

        it('gets registered callback', function(): void {
            $callback = fn(): string => 'result';
            CallbackHelper::register('test', $callback);

            expect(CallbackHelper::get('test'))->toBe($callback);
        });

        it('returns null for non-existent callback', function(): void {
            expect(CallbackHelper::get('nonexistent'))->toBeNull();
        });

        it('unregisters a callback', function(): void {
            CallbackHelper::register('test', fn(): string => 'result');
            CallbackHelper::unregister('test');

            expect(CallbackHelper::has('test'))->toBeFalse();
        });

        it('clears all callbacks', function(): void {
            CallbackHelper::register('test1', fn(): string => 'result1');
            CallbackHelper::register('test2', fn(): string => 'result2');

            CallbackHelper::clear();

            expect(CallbackHelper::count())->toBe(0);
        });

        it('gets all registered names', function(): void {
            CallbackHelper::register('test1', fn(): string => 'result1');
            CallbackHelper::register('test2', fn(): string => 'result2');

            $names = CallbackHelper::getRegisteredNames();
            expect($names)->toBe(['test1', 'test2']);
        });
    });

    describe('Execution - Registered Callbacks', function(): void {
        beforeEach(function(): void {
            CallbackHelper::clear();
        });

        it('executes registered callback', function(): void {
            CallbackHelper::register('upper', strtoupper(...));

            $result = CallbackHelper::execute('upper', 'hello');
            expect($result)->toBe('HELLO');
        });

        it('executes registered callback with multiple arguments', function(): void {
            CallbackHelper::register('concat', fn($a, $b, $c): string => $a . $b . $c);

            $result = CallbackHelper::execute('concat', 'Hello', ' ', 'World');
            expect($result)->toBe('Hello World');
        });
    });

    describe('Execution - Static Methods', function(): void {
        beforeEach(function(): void {
            CallbackHelper::clear();
        });

        it('executes static method with :: syntax', function(): void {
            $result = CallbackHelper::execute(
                TestCallbackClass::class . '::staticMethod',
                'test'
            );
            expect($result)->toBe('STATIC: test');
        });

        it('executes static method with array syntax', function(): void {
            $result = CallbackHelper::execute(
                TestCallbackClass::staticMethod(...),
                'test'
            );
            expect($result)->toBe('STATIC: test');
        });

        it('executes instance method with array syntax', function(): void {
            $instance = new TestCallbackClass();
            $result = CallbackHelper::execute($instance->instanceMethod(...), 'test');
            expect($result)->toBe('INSTANCE: test');
        });

        it('throws exception for non-existent static method', function(): void {
            expect(fn(): mixed => CallbackHelper::execute(TestCallbackClass::class . '::nonExistent', 'test'))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws exception for non-static method with :: syntax', function(): void {
            expect(fn(): mixed => CallbackHelper::execute(TestCallbackClass::class . '::instanceMethod', 'test'))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('Execution - Instance Methods', function(): void {
        beforeEach(function(): void {
            CallbackHelper::clear();
        });

        it('executes public instance method', function(): void {
            $instance = new TestCallbackClass();

            $result = CallbackHelper::execute('instanceMethod', $instance, 'test');
            expect($result)->toBe('INSTANCE: test');
        });

        it('executes private instance method via reflection', function(): void {
            $instance = new TestCallbackClass();

            $result = CallbackHelper::execute('privateMethod', $instance, 'test');
            expect($result)->toBe('PRIVATE: test');
        });

        it('executes protected instance method via reflection', function(): void {
            $instance = new TestCallbackClass();

            $result = CallbackHelper::execute('protectedMethod', $instance, 'test');
            expect($result)->toBe('PROTECTED: test');
        });

        it('finds instance in arguments and removes it', function(): void {
            $instance = new TestCallbackClass();

            // Instance can be anywhere in arguments - it will be removed
            $result = CallbackHelper::execute('instanceMethod', 'test', $instance);
            expect($result)->toBe('INSTANCE: test');
        });
    });

    describe('Execution - Global Functions', function(): void {
        beforeEach(function(): void {
            CallbackHelper::clear();
        });

        it('executes global function', function(): void {
            $result = CallbackHelper::execute('strtoupper', 'hello');
            expect($result)->toBe('HELLO');
        });

        it('executes global function with multiple arguments', function(): void {
            $result = CallbackHelper::execute('str_replace', 'o', '0', 'hello');
            expect($result)->toBe('hell0');
        });
    });

    describe('Execution - Closures', function(): void {
        beforeEach(function(): void {
            CallbackHelper::clear();
        });

        it('executes closure', function(): void {
            $closure = strtoupper(...);

            $result = CallbackHelper::execute($closure, 'hello');
            expect($result)->toBe('HELLO');
        });

        it('executes closure with multiple arguments', function(): void {
            $closure = fn($a, $b): float|int|array => $a + $b;

            $result = CallbackHelper::execute($closure, 5, 3);
            expect($result)->toBe(8);
        });
    });

    describe('Execution Priority', function(): void {
        beforeEach(function(): void {
            CallbackHelper::clear();
        });

        it('prefers registered callback over global function', function(): void {
            CallbackHelper::register('strtoupper', fn($value): string => 'REGISTERED: ' . $value);

            $result = CallbackHelper::execute('strtoupper', 'hello');
            expect($result)->toBe('REGISTERED: hello');
        });

        it('prefers registered callback over static method', function(): void {
            CallbackHelper::register(
                TestCallbackClass::class . '::staticMethod',
                fn($value): string => 'REGISTERED: ' . $value
            );

            $result = CallbackHelper::execute(TestCallbackClass::class . '::staticMethod', 'hello');
            expect($result)->toBe('REGISTERED: hello');
        });
    });

    describe('Error Handling', function(): void {
        beforeEach(function(): void {
            CallbackHelper::clear();
        });

        it('throws exception for unresolvable callback', function(): void {
            expect(fn(): mixed => CallbackHelper::execute('nonExistentFunction', 'test'))
                ->toThrow(InvalidArgumentException::class, 'Cannot resolve callback');
        });

        it('throws exception for invalid array callback', function(): void {
            expect(fn(): mixed => CallbackHelper::execute(['InvalidClass', 'method'], 'test'))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws exception for non-callable object', function(): void {
            $object = new stdClass();

            expect(fn(): mixed => CallbackHelper::execute($object, 'test'))
                ->toThrow(InvalidArgumentException::class);
        });
    });
});

/**
 * Test class for callback testing.
 */
class TestCallbackClass
{
    public static function staticMethod(string $value): string
    {
        return 'STATIC: ' . $value;
    }

    public function instanceMethod(string $value): string
    {
        return 'INSTANCE: ' . $value;
    }

    private function privateMethod(string $value): string
    {
        return 'PRIVATE: ' . $value;
    }

    protected function protectedMethod(string $value): string
    {
        return 'PROTECTED: ' . $value;
    }
}
