<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;

describe('DataAccessor Typed Getters', function(): void {
    describe('getString', function(): void {
        it('returns string value', function(): void {
            $accessor = new DataAccessor(['name' => 'John']);

            expect($accessor->getString('name'))->toBe('John');
        });

        it('converts numeric to string', function(): void {
            $accessor = new DataAccessor(['age' => 42]);

            expect($accessor->getString('age'))->toBe('42');
        });

        it('converts boolean to string', function(): void {
            $accessor = new DataAccessor(['active' => true]);

            expect($accessor->getString('active'))->toBe('1');
        });

        it('returns default for non-existent path', function(): void {
            $accessor = new DataAccessor(['name' => 'John']);

            expect($accessor->getString('email', 'default@example.com'))->toBe('default@example.com');
        });

        it('returns default for null value', function(): void {
            $accessor = new DataAccessor(['name' => null]);

            expect($accessor->getString('name', 'default'))->toBe('default');
        });

        it('returns default for array value', function(): void {
            $accessor = new DataAccessor(['data' => ['foo' => 'bar']]);

            expect($accessor->getString('data', 'default'))->toBe('default');
        });

        it('returns null when no default provided', function(): void {
            $accessor = new DataAccessor(['name' => 'John']);

            expect($accessor->getString('email'))->toBeNull();
        });
    });

    describe('getInt', function(): void {
        it('returns integer value', function(): void {
            $accessor = new DataAccessor(['age' => 42]);

            expect($accessor->getInt('age'))->toBe(42);
        });

        it('converts numeric string to integer', function(): void {
            $accessor = new DataAccessor(['age' => '42']);

            expect($accessor->getInt('age'))->toBe(42);
        });

        it('converts float to integer', function(): void {
            $accessor = new DataAccessor(['age' => 42.7]);

            expect($accessor->getInt('age'))->toBe(42);
        });

        it('returns default for non-numeric value', function(): void {
            $accessor = new DataAccessor(['age' => 'not a number']);

            expect($accessor->getInt('age', 0))->toBe(0);
        });

        it('returns default for non-existent path', function(): void {
            $accessor = new DataAccessor(['age' => 42]);

            expect($accessor->getInt('count', 10))->toBe(10);
        });

        it('returns default for null value', function(): void {
            $accessor = new DataAccessor(['age' => null]);

            expect($accessor->getInt('age', 0))->toBe(0);
        });

        it('returns null when no default provided', function(): void {
            $accessor = new DataAccessor(['age' => 42]);

            expect($accessor->getInt('count'))->toBeNull();
        });
    });

    describe('getFloat', function(): void {
        it('returns float value', function(): void {
            $accessor = new DataAccessor(['price' => 19.99]);

            expect($accessor->getFloat('price'))->toBe(19.99);
        });

        it('converts integer to float', function(): void {
            $accessor = new DataAccessor(['price' => 20]);

            expect($accessor->getFloat('price'))->toBe(20.0);
        });

        it('converts numeric string to float', function(): void {
            $accessor = new DataAccessor(['price' => '19.99']);

            expect($accessor->getFloat('price'))->toBe(19.99);
        });

        it('returns default for non-numeric value', function(): void {
            $accessor = new DataAccessor(['price' => 'not a number']);

            expect($accessor->getFloat('price', 0.0))->toBe(0.0);
        });

        it('returns default for non-existent path', function(): void {
            $accessor = new DataAccessor(['price' => 19.99]);

            expect($accessor->getFloat('discount', 0.0))->toBe(0.0);
        });

        it('returns default for null value', function(): void {
            $accessor = new DataAccessor(['price' => null]);

            expect($accessor->getFloat('price', 0.0))->toBe(0.0);
        });

        it('returns null when no default provided', function(): void {
            $accessor = new DataAccessor(['price' => 19.99]);

            expect($accessor->getFloat('discount'))->toBeNull();
        });
    });

    describe('getBool', function(): void {
        it('returns boolean value', function(): void {
            $accessor = new DataAccessor(['active' => true]);

            expect($accessor->getBool('active'))->toBeTrue();
        });

        it('converts string "true" to boolean', function(): void {
            $accessor = new DataAccessor(['active' => 'true']);

            expect($accessor->getBool('active'))->toBeTrue();
        });

        it('converts string "false" to boolean', function(): void {
            $accessor = new DataAccessor(['active' => 'false']);

            expect($accessor->getBool('active'))->toBeFalse();
        });

        it('converts string "1" to boolean', function(): void {
            $accessor = new DataAccessor(['active' => '1']);

            expect($accessor->getBool('active'))->toBeTrue();
        });

        it('converts string "0" to boolean', function(): void {
            $accessor = new DataAccessor(['active' => '0']);

            expect($accessor->getBool('active'))->toBeFalse();
        });

        it('converts string "yes" to boolean', function(): void {
            $accessor = new DataAccessor(['active' => 'yes']);

            expect($accessor->getBool('active'))->toBeTrue();
        });

        it('converts string "no" to boolean', function(): void {
            $accessor = new DataAccessor(['active' => 'no']);

            expect($accessor->getBool('active'))->toBeFalse();
        });

        it('converts string "on" to boolean', function(): void {
            $accessor = new DataAccessor(['active' => 'on']);

            expect($accessor->getBool('active'))->toBeTrue();
        });

        it('converts string "off" to boolean', function(): void {
            $accessor = new DataAccessor(['active' => 'off']);

            expect($accessor->getBool('active'))->toBeFalse();
        });

        it('converts empty string to false', function(): void {
            $accessor = new DataAccessor(['active' => '']);

            expect($accessor->getBool('active'))->toBeFalse();
        });

        it('converts integer 1 to boolean', function(): void {
            $accessor = new DataAccessor(['active' => 1]);

            expect($accessor->getBool('active'))->toBeTrue();
        });

        it('converts integer 0 to boolean', function(): void {
            $accessor = new DataAccessor(['active' => 0]);

            expect($accessor->getBool('active'))->toBeFalse();
        });

        it('returns default for non-boolean value', function(): void {
            $accessor = new DataAccessor(['active' => 'maybe']);

            expect($accessor->getBool('active', false))->toBeFalse();
        });

        it('returns default for non-existent path', function(): void {
            $accessor = new DataAccessor(['active' => true]);

            expect($accessor->getBool('enabled', false))->toBeFalse();
        });

        it('returns default for null value', function(): void {
            $accessor = new DataAccessor(['active' => null]);

            expect($accessor->getBool('active', false))->toBeFalse();
        });

        it('returns null when no default provided', function(): void {
            $accessor = new DataAccessor(['active' => true]);

            expect($accessor->getBool('enabled'))->toBeNull();
        });
    });

    describe('getArray', function(): void {
        it('returns array value', function(): void {
            $accessor = new DataAccessor(['items' => ['a', 'b', 'c']]);

            expect($accessor->getArray('items'))->toBe(['a', 'b', 'c']);
        });

        it('returns default for non-array value', function(): void {
            $accessor = new DataAccessor(['items' => 'not an array']);

            expect($accessor->getArray('items', []))->toBe([]);
        });

        it('returns default for non-existent path', function(): void {
            $accessor = new DataAccessor(['items' => ['a', 'b']]);

            expect($accessor->getArray('other', []))->toBe([]);
        });

        it('returns default for null value', function(): void {
            $accessor = new DataAccessor(['items' => null]);

            expect($accessor->getArray('items', []))->toBe([]);
        });

        it('returns null when no default provided', function(): void {
            $accessor = new DataAccessor(['items' => ['a', 'b']]);

            expect($accessor->getArray('other'))->toBeNull();
        });
    });

    describe('Nested paths', function(): void {
        it('getString works with nested paths', function(): void {
            $accessor = new DataAccessor(['user' => ['name' => 'John']]);

            expect($accessor->getString('user.name'))->toBe('John');
        });

        it('getInt works with nested paths', function(): void {
            $accessor = new DataAccessor(['user' => ['age' => 42]]);

            expect($accessor->getInt('user.age'))->toBe(42);
        });

        it('getFloat works with nested paths', function(): void {
            $accessor = new DataAccessor(['product' => ['price' => 19.99]]);

            expect($accessor->getFloat('product.price'))->toBe(19.99);
        });

        it('getBool works with nested paths', function(): void {
            $accessor = new DataAccessor(['user' => ['active' => true]]);

            expect($accessor->getBool('user.active'))->toBeTrue();
        });

        it('getArray works with nested paths', function(): void {
            $accessor = new DataAccessor(['user' => ['tags' => ['admin', 'user']]]);

            expect($accessor->getArray('user.tags'))->toBe(['admin', 'user']);
        });
    });
});

