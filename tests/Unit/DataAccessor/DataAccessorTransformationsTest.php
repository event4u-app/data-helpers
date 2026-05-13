<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;

describe('DataAccessor Transformations', function(): void {
    describe('first()', function(): void {
        it('returns first item', function(): void {
            $accessor = new DataAccessor([1, 2, 3, 4, 5]);
            expect($accessor->first())->toBe(1);
        });

        it('returns first item with callback', function(): void {
            $accessor = new DataAccessor([1, 2, 3, 4, 5]);
            expect($accessor->first(fn($n): bool => 2 < $n))->toBe(3);
        });

        it('returns default when empty', function(): void {
            $accessor = new DataAccessor([]);
            expect($accessor->first(default: 'default'))->toBe('default');
        });

        it('returns default when no match', function(): void {
            $accessor = new DataAccessor([1, 2, 3]);
            expect($accessor->first(fn($n): bool => 10 < $n, 'default'))->toBe('default');
        });
    });

    describe('last()', function(): void {
        it('returns last item', function(): void {
            $accessor = new DataAccessor([1, 2, 3, 4, 5]);
            expect($accessor->last())->toBe(5);
        });

        it('returns last item with callback', function(): void {
            $accessor = new DataAccessor([1, 2, 3, 4, 5]);
            expect($accessor->last(fn($n): bool => 4 > $n))->toBe(3);
        });

        it('returns default when empty', function(): void {
            $accessor = new DataAccessor([]);
            expect($accessor->last(default: 'default'))->toBe('default');
        });

        it('returns default when no match', function(): void {
            $accessor = new DataAccessor([1, 2, 3]);
            expect($accessor->last(fn($n): bool => 10 < $n, 'default'))->toBe('default');
        });
    });

    describe('filter()', function(): void {
        it('filters items with callback', function(): void {
            $accessor = new DataAccessor([1, 2, 3, 4, 5]);
            $filtered = $accessor->filter(fn($n): bool => 2 < $n);
            expect($filtered)->toBe([2 => 3, 3 => 4, 4 => 5]);
        });

        it('filters falsy values without callback', function(): void {
            $accessor = new DataAccessor([0, 1, false, 2, null, 3, '']);
            $filtered = $accessor->filter();
            expect($filtered)->toBe([1 => 1, 3 => 2, 5 => 3]);
        });

        it('preserves keys', function(): void {
            $accessor = new DataAccessor(['a' => 1, 'b' => 2, 'c' => 3]);
            $filtered = $accessor->filter(fn($n): bool => 1 < $n);
            expect($filtered)->toBe(['b' => 2, 'c' => 3]);
        });

        it('passes key to callback', function(): void {
            $accessor = new DataAccessor(['a' => 1, 'b' => 2, 'c' => 3]);
            $filtered = $accessor->filter(fn($value, $key): bool => 'b' === $key);
            expect($filtered)->toBe(['b' => 2]);
        });
    });

    describe('map()', function(): void {
        it('maps items', function(): void {
            $accessor = new DataAccessor([1, 2, 3]);
            $mapped = $accessor->map(fn(int $n): int => $n * 2); // @phpstan-ignore argument.type
            expect($mapped)->toBe([2, 4, 6]);
        });

        it('preserves keys', function(): void {
            $accessor = new DataAccessor(['a' => 1, 'b' => 2, 'c' => 3]);
            $mapped = $accessor->map(fn(int $n): int => $n * 2); // @phpstan-ignore argument.type
            expect($mapped)->toBe(['a' => 2, 'b' => 4, 'c' => 6]);
        });

        it('passes key to callback', function(): void {
            $accessor = new DataAccessor(['a' => 1, 'b' => 2]);
            $mapped = $accessor->map(
                /** @param mixed $value @param mixed $key */
                fn($value, $key): string => $key . ':' . $value // @phpstan-ignore binaryOp.invalid
            );
            expect($mapped)->toBe(['a' => 'a:1', 'b' => 'b:2']);
        });
    });

    describe('reduce()', function(): void {
        it('reduces to single value', function(): void {
            $accessor = new DataAccessor([1, 2, 3, 4, 5]);
            $sum = $accessor->reduce(
                /** @param mixed $carry @param mixed $item */
                fn($carry, $item): int => (int)$carry + (int)$item,
                0
            );
            expect($sum)->toBe(15);
        });

        it('reduces without initial value', function(): void {
            $accessor = new DataAccessor([1, 2, 3]);
            $result = $accessor->reduce(
                /** @param mixed $carry @param mixed $item */
                fn($carry, $item): int => ((int)$carry) + (int)$item
            );
            expect($result)->toBe(6);
        });

        it('passes key to callback', function(): void {
            $accessor = new DataAccessor(['a' => 1, 'b' => 2, 'c' => 3]);
            $result = $accessor->reduce(
                /** @param mixed $carry @param mixed $item @param mixed $key */
                fn($carry, $item, $key): string => $carry . $key, // @phpstan-ignore binaryOp.invalid
                ''
            );
            expect($result)->toBe('abc');
        });
    });

    describe('lazy()', function(): void {
        it('creates generator', function(): void {
            $accessor = new DataAccessor([1, 2, 3]);
            $generator = $accessor->lazy();
            expect($generator)->toBeInstanceOf(Generator::class);
        });

        it('iterates lazily', function(): void {
            $accessor = new DataAccessor([1, 2, 3, 4, 5]);
            $result = [];
            foreach ($accessor->lazy() as $item) {
                $result[] = $item;
            }
            expect($result)->toBe([1, 2, 3, 4, 5]);
        });

        it('preserves keys', function(): void {
            $accessor = new DataAccessor(['a' => 1, 'b' => 2]);
            $result = [];
            foreach ($accessor->lazy() as $key => $item) {
                $result[$key] = $item;
            }
            expect($result)->toBe(['a' => 1, 'b' => 2]);
        });
    });

    describe('lazyFilter()', function(): void {
        it('filters lazily', function(): void {
            $accessor = new DataAccessor([1, 2, 3, 4, 5]);
            $result = [];
            foreach ($accessor->lazyFilter(fn($n): bool => 2 < $n) as $item) {
                $result[] = $item;
            }
            expect($result)->toBe([3, 4, 5]);
        });

        it('preserves keys', function(): void {
            $accessor = new DataAccessor(['a' => 1, 'b' => 2, 'c' => 3]);
            $result = [];
            foreach ($accessor->lazyFilter(fn($n): bool => 1 < $n) as $key => $item) {
                $result[$key] = $item;
            }
            expect($result)->toBe(['b' => 2, 'c' => 3]);
        });
    });

    describe('lazyMap()', function(): void {
        it('maps lazily', function(): void {
            $accessor = new DataAccessor([1, 2, 3]);
            $result = [];
            foreach ($accessor->lazyMap(fn(int $n): int => $n * 2) as $item) { // @phpstan-ignore argument.type
                $result[] = $item;
            }
            expect($result)->toBe([2, 4, 6]);
        });

        it('preserves keys', function(): void {
            $accessor = new DataAccessor(['a' => 1, 'b' => 2]);
            $result = [];
            foreach ($accessor->lazyMap(fn(int $n): int => $n * 2) as $key => $item) { // @phpstan-ignore argument.type
                $result[$key] = $item;
            }
            expect($result)->toBe(['a' => 2, 'b' => 4]);
        });
    });
});
