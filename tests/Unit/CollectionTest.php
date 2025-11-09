<?php

declare(strict_types=1);

use event4u\DataHelpers\DataCollection;

describe('Collection', function(): void {
    describe('Creation', function(): void {
        it('creates empty collection', function(): void {
            $collection = DataCollection::make();

            expect($collection->isEmpty())->toBeTrue()
                ->and($collection->count())->toBe(0)
                ->and($collection->toArray())->toBe([]);
        });

        it('creates collection from array', function(): void {
            $collection = DataCollection::make([1, 2, 3]);

            expect($collection->count())->toBe(3)
                ->and($collection->toArray())->toBe([1, 2, 3]);
        });

        it('creates collection with associative array', function(): void {
            $collection = DataCollection::make(['a' => 1, 'b' => 2]);

            expect($collection->count())->toBe(2)
                ->and($collection->toArray())->toBe(['a' => 1, 'b' => 2]);
        });

        it('wraps collection in collection', function(): void {
            $inner = DataCollection::make([1, 2, 3]);
            $outer = DataCollection::wrap($inner);

            expect($outer)->toBe($inner);
        });

        it('wraps array in collection', function(): void {
            $collection = DataCollection::wrap([1, 2, 3]);

            expect($collection)->toBeInstanceOf(DataCollection::class)
                ->and($collection->toArray())->toBe([1, 2, 3]);
        });
    });

    describe('Array Access', function(): void {
        it('gets item by offset', function(): void {
            $collection = DataCollection::make(['a' => 1, 'b' => 2]);

            expect($collection['a'])->toBe(1)
                ->and($collection['b'])->toBe(2);
        });

        it('sets item by offset', function(): void {
            $collection = DataCollection::make(['a' => 1]);
            $collection['b'] = 2;

            expect($collection['b'])->toBe(2)
                ->and($collection->count())->toBe(2);
        });

        it('checks if offset exists', function(): void {
            $collection = DataCollection::make(['a' => 1]);

            expect(isset($collection['a']))->toBeTrue()
                ->and(isset($collection['b']))->toBeFalse();
        });

        it('unsets item by offset', function(): void {
            $collection = DataCollection::make(['a' => 1, 'b' => 2]);
            unset($collection['a']);

            expect(isset($collection['a']))->toBeFalse()
                ->and($collection->count())->toBe(1);
        });

        it('appends item without key', function(): void {
            $collection = DataCollection::make([1, 2]);
            $collection[] = 3;

            expect($collection->toArray())->toBe([1, 2, 3]);
        });
    });

    describe('Iteration', function(): void {
        it('iterates over items', function(): void {
            $collection = DataCollection::make(['a' => 1, 'b' => 2, 'c' => 3]);
            $result = [];

            foreach ($collection as $key => $value) {
                $result[$key] = $value;
            }

            expect($result)->toBe(['a' => 1, 'b' => 2, 'c' => 3]);
        });

        it('iterates over empty collection', function(): void {
            $collection = DataCollection::make();
            $count = 0;

            foreach ($collection as $item) {
                $count++;
            }

            expect($count)->toBe(0);
        });
    });

    describe('Filter', function(): void {
        it('filters items with callback', function(): void {
            $collection = DataCollection::make([1, 2, 3, 4, 5]);
            $filtered = $collection->filter(fn($item): bool => 2 < $item);

            expect($filtered->toArray())->toBe([2 => 3, 3 => 4, 4 => 5]);
        });

        it('filters items without callback (removes falsy)', function(): void {
            $collection = DataCollection::make([0, 1, false, 2, null, 3, '', 4]);
            $filtered = $collection->filter();

            expect($filtered->toArray())->toBe([1 => 1, 3 => 2, 5 => 3, 7 => 4]);
        });

        it('filters with key and value', function(): void {
            $collection = DataCollection::make(['a' => 1, 'b' => 2, 'c' => 3]);
            $filtered = $collection->filter(fn($value, $key): bool => 'b' !== $key);

            expect($filtered->toArray())->toBe(['a' => 1, 'c' => 3]);
        });

        it('returns new collection instance', function(): void {
            $collection = DataCollection::make([1, 2, 3]);
            $filtered = $collection->filter(fn($item): bool => 1 < $item);

            expect($filtered)->not->toBe($collection);
        });
    });

    describe('Map', function(): void {
        it('maps items with callback', function(): void {
            $collection = DataCollection::make([1, 2, 3]);
            $mapped = $collection->map(fn($item): int => $item * 2);

            expect($mapped->toArray())->toBe([2, 4, 6]);
        });

        it('maps with key and value', function(): void {
            $collection = DataCollection::make(['a' => 1, 'b' => 2]);
            $mapped = $collection->map(fn($value, $key): string => $key . ':' . $value);

            expect($mapped->toArray())->toBe(['a' => 'a:1', 'b' => 'b:2']);
        });

        it('preserves keys', function(): void {
            $collection = DataCollection::make(['a' => 1, 'b' => 2]);
            $mapped = $collection->map(fn($item): int => $item * 2);

            expect($mapped->toArray())->toBe(['a' => 2, 'b' => 4]);
        });

        it('returns new collection instance', function(): void {
            $collection = DataCollection::make([1, 2, 3]);
            $mapped = $collection->map(fn($item): int => $item * 2);

            expect($mapped)->not->toBe($collection);
        });
    });

    describe('Reduce', function(): void {
        it('reduces to single value', function(): void {
            $collection = DataCollection::make([1, 2, 3, 4]);
            $sum = $collection->reduce(fn($carry, $item): float|int => $carry + $item, 0);

            expect($sum)->toBe(10);
        });

        it('reduces with initial value', function(): void {
            $collection = DataCollection::make([1, 2, 3]);
            $result = $collection->reduce(fn($carry, $item): float|int => $carry + $item, 10);

            expect($result)->toBe(16);
        });

        it('reduces with key', function(): void {
            $collection = DataCollection::make(['a' => 1, 'b' => 2, 'c' => 3]);
            $result = $collection->reduce(fn($carry, $value, $key): string => $carry . $key, '');

            expect($result)->toBe('abc');
        });

        it('reduces empty collection returns initial', function(): void {
            $collection = DataCollection::make();
            $result = $collection->reduce(fn($carry, $item) => $carry + $item, 42);

            expect($result)->toBe(42);
        });
    });

    describe('First and Last', function(): void {
        it('gets first item', function(): void {
            $collection = DataCollection::make([1, 2, 3]);

            expect($collection->first())->toBe(1);
        });

        it('gets first item with callback', function(): void {
            $collection = DataCollection::make([1, 2, 3, 4]);
            $first = $collection->first(fn($item): bool => 2 < $item);

            expect($first)->toBe(3);
        });

        it('gets first item with default', function(): void {
            $collection = DataCollection::make();

            expect($collection->first(default: 'default'))->toBe('default');
        });

        it('gets last item', function(): void {
            $collection = DataCollection::make([1, 2, 3]);

            expect($collection->last())->toBe(3);
        });

        it('gets last item with callback', function(): void {
            $collection = DataCollection::make([1, 2, 3, 4]);
            $last = $collection->last(fn($item): bool => 3 > $item);

            expect($last)->toBe(2);
        });

        it('gets last item with default', function(): void {
            $collection = DataCollection::make();

            expect($collection->last(default: 'default'))->toBe('default');
        });
    });

    describe('Get', function(): void {
        it('gets item by key', function(): void {
            $collection = DataCollection::make(['a' => 1, 'b' => 2]);

            expect($collection->get('a'))->toBe(1);
        });

        it('gets item with default', function(): void {
            $collection = DataCollection::make(['a' => 1]);

            expect($collection->get('b', 'default'))->toBe('default');
        });

        it('gets null for missing key without default', function(): void {
            $collection = DataCollection::make(['a' => 1]);

            expect($collection->get('b'))->toBeNull();
        });
    });

    describe('Push and Prepend', function(): void {
        it('pushes single item', function(): void {
            $collection = DataCollection::make([1, 2]);
            $result = $collection->push(3);

            expect($result->toArray())->toBe([1, 2, 3]);
        });

        it('pushes multiple items', function(): void {
            $collection = DataCollection::make([1]);
            $result = $collection->push(2, 3, 4);

            expect($result->toArray())->toBe([1, 2, 3, 4]);
        });

        it('prepends item', function(): void {
            $collection = DataCollection::make([2, 3]);
            $result = $collection->prepend(1);

            expect($result->toArray())->toBe([1, 2, 3]);
        });

        it('returns same collection instance (fluent)', function(): void {
            $collection = DataCollection::make([1, 2]);
            $pushed = $collection->push(3);

            expect($pushed)->toBe($collection)
                ->and($collection->toArray())->toBe([1, 2, 3]);
        });
    });

    describe('Keys and Values', function(): void {
        it('gets all keys', function(): void {
            $collection = DataCollection::make(['a' => 1, 'b' => 2, 'c' => 3]);

            expect($collection->keys()->toArray())->toBe(['a', 'b', 'c']);
        });

        it('gets all values', function(): void {
            $collection = DataCollection::make(['a' => 1, 'b' => 2, 'c' => 3]);

            expect($collection->values()->toArray())->toBe([1, 2, 3]);
        });

        it('values reindexes array', function(): void {
            $collection = DataCollection::make([5 => 'a', 10 => 'b', 15 => 'c']);

            expect($collection->values()->toArray())->toBe(['a', 'b', 'c']);
        });
    });

    describe('Has', function(): void {
        it('checks if key exists', function(): void {
            $collection = DataCollection::make(['a' => 1, 'b' => 2]);

            expect($collection->has('a'))->toBeTrue()
                ->and($collection->has('c'))->toBeFalse();
        });

        it('checks numeric keys', function(): void {
            $collection = DataCollection::make([1, 2, 3]);

            expect($collection->has(0))->toBeTrue()
                ->and($collection->has(1))->toBeTrue()
                ->and($collection->has(3))->toBeFalse();
        });
    });

    describe('IsEmpty and IsNotEmpty', function(): void {
        it('checks if empty', function(): void {
            $empty = DataCollection::make();
            $notEmpty = DataCollection::make([1]);

            expect($empty->isEmpty())->toBeTrue()
                ->and($notEmpty->isEmpty())->toBeFalse();
        });

        it('checks if not empty', function(): void {
            $empty = DataCollection::make();
            $notEmpty = DataCollection::make([1]);

            expect($empty->isNotEmpty())->toBeFalse()
                ->and($notEmpty->isNotEmpty())->toBeTrue();
        });
    });

    describe('Count', function(): void {
        it('counts items', function(): void {
            $collection = DataCollection::make([1, 2, 3]);

            expect($collection->count())->toBe(3);
        });

        it('counts empty collection', function(): void {
            $collection = DataCollection::make();

            expect($collection->count())->toBe(0);
        });

        it('counts with count() function', function(): void {
            $collection = DataCollection::make([1, 2, 3, 4, 5]);

            expect(count($collection))->toBe(5);
        });
    });

    describe('All and Items', function(): void {
        it('gets all items', function(): void {
            $collection = DataCollection::make(['a' => 1, 'b' => 2]);

            expect($collection->all())->toBe(['a' => 1, 'b' => 2]);
        });

        it('gets items', function(): void {
            $collection = DataCollection::make(['a' => 1, 'b' => 2]);

            expect($collection->items())->toBe(['a' => 1, 'b' => 2]);
        });

        it('all and items return same result', function(): void {
            $collection = DataCollection::make([1, 2, 3]);

            expect($collection->all())->toBe($collection->items());
        });
    });

    describe('Lazy Evaluation', function(): void {
        it('creates lazy generator', function(): void {
            $collection = DataCollection::make([1, 2, 3, 4, 5]);
            $lazy = $collection->lazy();

            expect($lazy)->toBeInstanceOf(Generator::class);

            $result = [];
            foreach ($lazy as $key => $value) {
                $result[$key] = $value;
            }

            expect($result)->toBe([1, 2, 3, 4, 5]);
        });

        it('lazy filters items', function(): void {
            $collection = DataCollection::make([1, 2, 3, 4, 5]);
            $lazy = $collection->lazyFilter(fn($item): bool => 2 < $item);

            expect($lazy)->toBeInstanceOf(Generator::class);

            $result = [];
            foreach ($lazy as $key => $value) {
                $result[$key] = $value;
            }

            expect($result)->toBe([2 => 3, 3 => 4, 4 => 5]);
        });

        it('lazy maps items', function(): void {
            $collection = DataCollection::make([1, 2, 3]);
            $lazy = $collection->lazyMap(fn($item): int => $item * 2);

            expect($lazy)->toBeInstanceOf(Generator::class);

            $result = [];
            foreach ($lazy as $key => $value) {
                $result[$key] = $value;
            }

            expect($result)->toBe([2, 4, 6]);
        });

        it('lazy evaluation does not execute immediately', function(): void {
            $executed = false;
            $collection = DataCollection::make([1, 2, 3]);

            $lazy = $collection->lazyMap(function($item) use (&$executed): int {
                $executed = true;
                return $item * 2;
            });

            expect($executed)->toBeFalse();

            // Trigger execution
            iterator_to_array($lazy);

            expect($executed)->toBeTrue();
        });
    });

    describe('JSON Serialization', function(): void {
        it('serializes to JSON', function(): void {
            $collection = DataCollection::make(['a' => 1, 'b' => 2]);

            expect($collection->toJson())->toBe('{"a":1,"b":2}');
        });

        it('serializes empty collection', function(): void {
            $collection = DataCollection::make();

            expect($collection->toJson())->toBe('[]');
        });

        it('serializes with json_encode', function(): void {
            $collection = DataCollection::make([1, 2, 3]);

            expect(json_encode($collection))->toBe('[1,2,3]');
        });

        it('serializes nested collections', function(): void {
            $inner = DataCollection::make([1, 2]);
            $outer = DataCollection::make(['data' => $inner]);

            expect($outer->toJson())->toBe('{"data":[1,2]}');
        });
    });

    describe('Edge Cases', function(): void {
        it('handles null values', function(): void {
            $collection = DataCollection::make([1, null, 3]);

            expect($collection->count())->toBe(3)
                ->and($collection->toArray())->toBe([1, null, 3]);
        });

        it('handles mixed types', function(): void {
            $collection = DataCollection::make([1, 'string', true, null, ['array']]);

            expect($collection->count())->toBe(5);
        });

        it('handles large collections', function(): void {
            $items = range(1, 10000);
            $collection = DataCollection::make($items);

            expect($collection->count())->toBe(10000);
        });

        it('handles nested arrays', function(): void {
            $collection = DataCollection::make([
                ['a' => 1],
                ['b' => 2],
                ['c' => 3],
            ]);

            expect($collection->count())->toBe(3)
                ->and($collection->first())->toBe(['a' => 1]);
        });

        it('handles objects', function(): void {
            $obj1 = (object)['id' => 1];
            $obj2 = (object)['id' => 2];
            $collection = DataCollection::make([$obj1, $obj2]);

            expect($collection->count())->toBe(2)
                ->and($collection->first())->toBe($obj1);
        });

        it('handles string keys with special characters', function(): void {
            $collection = DataCollection::make([
                'key.with.dots' => 1,
                'key-with-dashes' => 2,
                'key_with_underscores' => 3,
            ]);

            expect($collection->count())->toBe(3)
                ->and($collection->get('key.with.dots'))->toBe(1);
        });

        it('handles numeric string keys', function(): void {
            $collection = DataCollection::make(['0' => 'a', '1' => 'b', '2' => 'c']);

            expect($collection->count())->toBe(3)
                ->and($collection->get('0'))->toBe('a');
        });
    });

    describe('Chaining', function(): void {
        it('chains multiple operations', function(): void {
            $collection = DataCollection::make([1, 2, 3, 4, 5]);

            $result = $collection
                ->filter(fn($item): bool => 2 < $item)
                ->map(fn($item): int => $item * 2)
                ->values();

            expect($result->toArray())->toBe([6, 8, 10]);
        });

        it('chains with push and filter', function(): void {
            $collection = DataCollection::make([1, 2, 3]);

            $result = $collection
                ->push(4, 5, 6)
                ->filter(fn($item): bool => $item % 2 === 0);

            expect($result->toArray())->toBe([1 => 2, 3 => 4, 5 => 6]);
        });

        it('chains with prepend and map', function(): void {
            $collection = DataCollection::make([2, 3]);

            $result = $collection
                ->prepend(1)
                ->map(fn($item): int => $item * 10);

            expect($result->toArray())->toBe([10, 20, 30]);
        });
    });
});
