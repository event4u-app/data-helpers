<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataCollection;
use event4u\DataHelpers\Exceptions\TypeMismatchException;

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

        it('throws exception for array value', function(): void {
            $accessor = new DataAccessor(['data' => ['foo' => 'bar']]);

            expect(fn(): ?string => $accessor->getString('data'))
                ->toThrow(TypeMismatchException::class, 'Expected single value for path "data", but got an array');
        });

        it('throws exception for object without __toString', function(): void {
            $accessor = new DataAccessor(['obj' => new stdClass()]);

            expect(fn(): ?string => $accessor->getString('obj'))
                ->toThrow(TypeMismatchException::class, 'Cannot convert value at path "obj" to string');
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

        it('converts boolean to integer', function(): void {
            $accessor = new DataAccessor(['active' => true]);

            expect($accessor->getInt('active'))->toBe(1);
        });

        it('throws exception for non-numeric value', function(): void {
            $accessor = new DataAccessor(['age' => 'not a number']);

            expect(fn(): ?int => $accessor->getInt('age'))
                ->toThrow(TypeMismatchException::class, 'Cannot convert value at path "age" to int');
        });

        it('throws exception for array value', function(): void {
            $accessor = new DataAccessor(['data' => [1, 2, 3]]);

            expect(fn(): ?int => $accessor->getInt('data'))
                ->toThrow(TypeMismatchException::class, 'Expected single value for path "data", but got an array');
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

        it('converts boolean to float', function(): void {
            $accessor = new DataAccessor(['active' => true]);

            expect($accessor->getFloat('active'))->toBe(1.0);
        });

        it('throws exception for non-numeric value', function(): void {
            $accessor = new DataAccessor(['price' => 'not a number']);

            expect(fn(): ?float => $accessor->getFloat('price'))
                ->toThrow(TypeMismatchException::class, 'Cannot convert value at path "price" to float');
        });

        it('throws exception for array value', function(): void {
            $accessor = new DataAccessor(['data' => [1.5, 2.5]]);

            expect(fn(): ?float => $accessor->getFloat('data'))
                ->toThrow(TypeMismatchException::class, 'Expected single value for path "data", but got an array');
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

        it('converts any value to boolean', function(): void {
            $accessor = new DataAccessor(['active' => 'any string']);

            expect($accessor->getBool('active'))->toBeTrue();
        });

        it('converts integer 1 to boolean', function(): void {
            $accessor = new DataAccessor(['active' => 1]);

            expect($accessor->getBool('active'))->toBeTrue();
        });

        it('converts integer 0 to boolean', function(): void {
            $accessor = new DataAccessor(['active' => 0]);

            expect($accessor->getBool('active'))->toBeFalse();
        });

        it('throws exception for array value', function(): void {
            $accessor = new DataAccessor(['data' => [true, false]]);

            expect(fn(): ?bool => $accessor->getBool('data'))
                ->toThrow(TypeMismatchException::class, 'Expected single value for path "data", but got an array');
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

        it('throws exception for non-array value', function(): void {
            $accessor = new DataAccessor(['items' => 'not an array']);

            expect(fn(): ?array => $accessor->getArray('items'))
                ->toThrow(TypeMismatchException::class, 'Expected array for path "items"');
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

    describe('Collection Getters', function(): void {
        describe('getIntCollection', function(): void {
            it('returns array of integers with wildcards', function(): void {
                $accessor = new DataAccessor([
                    'users' => [
                        ['age' => 25],
                        ['age' => 30],
                        ['age' => '35'],
                    ],
                ]);

                expect($accessor->getIntCollection('users.*.age')->toArray())->toBe([
                    'users.0.age' => 25,
                    'users.1.age' => 30,
                    'users.2.age' => 35,
                ]);
            });

            it('converts numeric strings to integers', function(): void {
                $accessor = new DataAccessor([
                    'items' => [
                        ['count' => '10'],
                        ['count' => '20'],
                    ],
                ]);

                expect($accessor->getIntCollection('items.*.count')->toArray())->toBe([
                    'items.0.count' => 10,
                    'items.1.count' => 20,
                ]);
            });

            it('throws exception when path has no wildcard', function(): void {
                $accessor = new DataAccessor(['age' => 25]);

                expect(fn(): array => $accessor->getIntCollection('age')) // @phpstan-ignore return.type
                    ->toThrow(TypeMismatchException::class, 'Path "age" does not contain wildcards');
            });

            it('throws exception when value cannot be converted to int', function(): void {
                $accessor = new DataAccessor([
                    'users' => [
                        ['name' => 'John'],
                    ],
                ]);

                expect(fn(): array => $accessor->getIntCollection('users.*.name')) // @phpstan-ignore return.type
                    ->toThrow(
                        TypeMismatchException::class,
                        'Cannot convert value at key "users.0.name" in path "users.*.name" to int'
                    );
            });
        });

        describe('getStringCollection', function(): void {
            it('returns array of strings with wildcards', function(): void {
                $accessor = new DataAccessor([
                    'users' => [
                        ['name' => 'Alice'],
                        ['name' => 'Bob'],
                    ],
                ]);

                expect($accessor->getStringCollection('users.*.name')->toArray())->toBe([
                    'users.0.name' => 'Alice',
                    'users.1.name' => 'Bob',
                ]);
            });

            it('converts numbers to strings', function(): void {
                $accessor = new DataAccessor([
                    'items' => [
                        ['id' => 123],
                        ['id' => 456],
                    ],
                ]);

                expect($accessor->getStringCollection('items.*.id')->toArray())->toBe([
                    'items.0.id' => '123',
                    'items.1.id' => '456',
                ]);
            });

            it('throws exception when path has no wildcard', function(): void {
                $accessor = new DataAccessor(['name' => 'John']);

                expect(fn(): array => $accessor->getStringCollection('name')) // @phpstan-ignore return.type
                    ->toThrow(TypeMismatchException::class, 'Path "name" does not contain wildcards');
            });

            it('throws exception for object without __toString', function(): void {
                $accessor = new DataAccessor([
                    'items' => [
                        ['obj' => new stdClass()],
                    ],
                ]);

                expect(fn(): array => $accessor->getStringCollection('items.*.obj')) // @phpstan-ignore return.type
                    ->toThrow(
                        TypeMismatchException::class,
                        'Cannot convert value at key "items.0.obj" in path "items.*.obj" to string'
                    );
            });
        });

        describe('getBoolCollection', function(): void {
            it('returns array of booleans with wildcards', function(): void {
                $accessor = new DataAccessor([
                    'users' => [
                        ['active' => true],
                        ['active' => false],
                        ['active' => 1],
                    ],
                ]);

                expect($accessor->getBoolCollection('users.*.active')->toArray())->toBe([
                    'users.0.active' => true,
                    'users.1.active' => false,
                    'users.2.active' => true,
                ]);
            });

            it('throws exception when path has no wildcard', function(): void {
                $accessor = new DataAccessor(['active' => true]);

                expect(fn(): DataCollection => $accessor->getBoolCollection('active')) // @phpstan-ignore return.type
                    ->toThrow(TypeMismatchException::class, 'Path "active" does not contain wildcards');
            });
        });

        describe('getFloatCollection', function(): void {
            it('returns array of floats with wildcards', function(): void {
                $accessor = new DataAccessor([
                    'products' => [
                        ['price' => 19.99],
                        ['price' => 29.99],
                        ['price' => '39.99'],
                    ],
                ]);

                expect($accessor->getFloatCollection('products.*.price')->toArray())->toBe([
                    'products.0.price' => 19.99,
                    'products.1.price' => 29.99,
                    'products.2.price' => 39.99,
                ]);
            });

            it('throws exception when path has no wildcard', function(): void {
                $accessor = new DataAccessor(['price' => 19.99]);

                expect(fn(): DataCollection => $accessor->getFloatCollection('price')) // @phpstan-ignore return.type
                    ->toThrow(TypeMismatchException::class, 'Path "price" does not contain wildcards');
            });

            it('throws exception when value cannot be converted to float', function(): void {
                $accessor = new DataAccessor([
                    'products' => [
                        ['name' => 'Product'],
                    ],
                ]);

                expect(
                    fn(): DataCollection => $accessor->getFloatCollection('products.*.name')
                ) // @phpstan-ignore return.type
                    ->toThrow(
                        TypeMismatchException::class,
                        'Cannot convert value at key "products.0.name" in path "products.*.name" to float'
                    );
            });
        });

        describe('getArrayCollection', function(): void {
            it('returns array of arrays with wildcards', function(): void {
                $accessor = new DataAccessor([
                    'users' => [
                        ['tags' => ['admin', 'user']],
                        ['tags' => ['guest']],
                    ],
                ]);

                expect($accessor->getArrayCollection('users.*.tags')->toArray())->toBe([
                    'users.0.tags' => ['admin', 'user'],
                    'users.1.tags' => ['guest'],
                ]);
            });

            it('throws exception when path has no wildcard', function(): void {
                $accessor = new DataAccessor(['tags' => ['admin']]);

                expect(fn(): DataCollection => $accessor->getArrayCollection('tags'))
                    ->toThrow(TypeMismatchException::class, 'Path "tags" does not contain wildcards');
            });

            it('throws exception when value is not an array', function(): void {
                $accessor = new DataAccessor([
                    'users' => [
                        ['name' => 'John'],
                    ],
                ]);

                expect(fn(): DataCollection => $accessor->getArrayCollection('users.*.name'))
                    ->toThrow(
                        TypeMismatchException::class,
                        'Expected array at key "users.0.name" in path "users.*.name"'
                    );
            });
        });
    });
});
