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

    describe('Mutable Methods with DataMutator', function(): void {
        it('sets value using dot notation', function(): void {
            $collection = DataCollection::make([
                ['user' => ['name' => 'Alice']],
            ]);

            $collection->set('0.user.age', 30);

            expect($collection->toArray())->toBe([
                ['user' => ['name' => 'Alice', 'age' => 30]],
            ]);
        });

        it('sets value and returns same instance', function(): void {
            $collection = DataCollection::make([['name' => 'Alice']]);
            $result = $collection->set('0.age', 30);

            expect($result)->toBe($collection);
        });

        it('merges values using dot notation', function(): void {
            $collection = DataCollection::make([
                ['user' => ['name' => 'Alice']],
            ]);

            $collection->merge('0.user', ['age' => 30, 'city' => 'Berlin']);

            expect($collection->toArray())->toBe([
                ['user' => ['name' => 'Alice', 'age' => 30, 'city' => 'Berlin']],
            ]);
        });

        it('merges multiple paths', function(): void {
            $collection = DataCollection::make([
                ['user' => ['name' => 'Alice']],
                ['user' => ['name' => 'Bob']],
            ]);

            $collection->merge([
                '0.user.age' => 30,
                '1.user.age' => 25,
            ]);

            expect($collection->toArray())->toBe([
                ['user' => ['name' => 'Alice', 'age' => 30]],
                ['user' => ['name' => 'Bob', 'age' => 25]],
            ]);
        });

        it('forgets value using dot notation', function(): void {
            $collection = DataCollection::make([
                ['user' => ['name' => 'Alice', 'age' => 30]],
            ]);

            $collection->forget('0.user.age');

            expect($collection->toArray())->toBe([
                ['user' => ['name' => 'Alice']],
            ]);
        });

        it('transforms value using callback', function(): void {
            $collection = DataCollection::make([
                ['user' => ['name' => 'alice']],
            ]);

            $collection->transform('0.user.name', fn($name) => strtoupper($name));

            expect($collection->toArray())->toBe([
                ['user' => ['name' => 'ALICE']],
            ]);
        });

        it('pushes to nested array', function(): void {
            $collection = DataCollection::make([
                ['user' => ['tags' => ['php']]],
            ]);

            $collection->pushTo('0.user.tags', 'laravel');

            expect($collection->toArray())->toBe([
                ['user' => ['tags' => ['php', 'laravel']]],
            ]);
        });

        it('pulls value and removes it', function(): void {
            $collection = DataCollection::make([
                ['user' => ['name' => 'Alice', 'age' => 30]],
            ]);

            $age = $collection->pull('0.user.age');

            expect($age)->toBe(30)
                ->and($collection->toArray())->toBe([
                    ['user' => ['name' => 'Alice']],
                ]);
        });

        it('pulls with default value', function(): void {
            $collection = DataCollection::make([
                ['user' => ['name' => 'Alice']],
            ]);

            $age = $collection->pull('0.user.age', 25);

            expect($age)->toBe(25);
        });

        it('chains mutable methods', function(): void {
            $collection = DataCollection::make([
                ['user' => ['name' => 'Alice']],
            ]);

            $collection
                ->set('0.user.age', 30)
                ->merge('0.user', ['city' => 'Berlin'])
                ->transform('0.user.name', fn($name) => strtoupper($name));

            expect($collection->toArray())->toBe([
                ['user' => ['name' => 'ALICE', 'age' => 30, 'city' => 'Berlin']],
            ]);
        });

        it('get() works after set()', function(): void {
            $collection = DataCollection::make([
                ['user' => ['name' => 'Alice']],
            ]);

            $collection->set('0.user.age', 30);
            $age = $collection->get('0.user.age');

            expect($age)->toBe(30);
        });
    });

    describe('Comprehensive Read Operations', function(): void {
        it('reads simple values', function(): void {
            $collection = DataCollection::make([
                'name' => 'Alice',
                'age' => 30,
                'active' => true,
            ]);

            expect($collection->get('name'))->toBe('Alice')
                ->and($collection->get('age'))->toBe(30)
                ->and($collection->get('active'))->toBe(true);
        });

        it('reads nested values', function(): void {
            $collection = DataCollection::make([
                'user' => [
                    'profile' => [
                        'name' => 'Alice',
                        'contact' => [
                            'email' => 'alice@example.com',
                            'phone' => '123-456',
                        ],
                    ],
                ],
            ]);

            expect($collection->get('user.profile.name'))->toBe('Alice')
                ->and($collection->get('user.profile.contact.email'))->toBe('alice@example.com')
                ->and($collection->get('user.profile.contact.phone'))->toBe('123-456');
        });

        it('reads array elements by index', function(): void {
            $collection = DataCollection::make([
                'users' => [
                    ['name' => 'Alice', 'age' => 30],
                    ['name' => 'Bob', 'age' => 25],
                    ['name' => 'Charlie', 'age' => 35],
                ],
            ]);

            expect($collection->get('users.0.name'))->toBe('Alice')
                ->and($collection->get('users.1.name'))->toBe('Bob')
                ->and($collection->get('users.2.name'))->toBe('Charlie')
                ->and($collection->get('users.1.age'))->toBe(25);
        });

        it('reads deeply nested array elements', function(): void {
            $collection = DataCollection::make([
                'company' => [
                    'departments' => [
                        [
                            'name' => 'Engineering',
                            'teams' => [
                                ['name' => 'Backend', 'members' => ['Alice', 'Bob']],
                                ['name' => 'Frontend', 'members' => ['Charlie', 'David']],
                            ],
                        ],
                    ],
                ],
            ]);

            expect($collection->get('company.departments.0.name'))->toBe('Engineering')
                ->and($collection->get('company.departments.0.teams.0.name'))->toBe('Backend')
                ->and($collection->get('company.departments.0.teams.0.members.0'))->toBe('Alice')
                ->and($collection->get('company.departments.0.teams.1.members.1'))->toBe('David');
        });

        it('returns default for non-existent paths', function(): void {
            $collection = DataCollection::make(['name' => 'Alice']);

            expect($collection->get('age', 25))->toBe(25)
                ->and($collection->get('user.profile.name', 'Unknown'))->toBe('Unknown')
                ->and($collection->get('missing.deeply.nested.path', null))->toBeNull();
        });

        it('reads literal keys with dots', function(): void {
            $collection = DataCollection::make([
                'key.with.dots' => 'value1',
                'another.key' => 'value2',
            ]);

            expect($collection->get('key.with.dots'))->toBe('value1')
                ->and($collection->get('another.key'))->toBe('value2');
        });

        it('reads mixed nested structures', function(): void {
            $collection = DataCollection::make([
                'data' => [
                    'users' => [
                        ['id' => 1, 'meta' => ['role' => 'admin', 'permissions' => ['read', 'write']]],
                        ['id' => 2, 'meta' => ['role' => 'user', 'permissions' => ['read']]],
                    ],
                    'settings' => [
                        'theme' => 'dark',
                        'notifications' => ['email' => true, 'sms' => false],
                    ],
                ],
            ]);

            expect($collection->get('data.users.0.meta.role'))->toBe('admin')
                ->and($collection->get('data.users.0.meta.permissions.1'))->toBe('write')
                ->and($collection->get('data.users.1.meta.permissions.0'))->toBe('read')
                ->and($collection->get('data.settings.notifications.email'))->toBe(true);
        });
    });

    describe('Comprehensive Write Operations', function(): void {
        it('sets simple values', function(): void {
            $collection = DataCollection::make([]);

            $collection
                ->set('name', 'Alice')
                ->set('age', 30)
                ->set('active', true);

            expect($collection->toArray())->toBe([
                'name' => 'Alice',
                'age' => 30,
                'active' => true,
            ]);
        });

        it('sets nested values', function(): void {
            $collection = DataCollection::make([]);

            $collection
                ->set('user.profile.name', 'Alice')
                ->set('user.profile.contact.email', 'alice@example.com')
                ->set('user.profile.contact.phone', '123-456');

            expect($collection->toArray())->toBe([
                'user' => [
                    'profile' => [
                        'name' => 'Alice',
                        'contact' => [
                            'email' => 'alice@example.com',
                            'phone' => '123-456',
                        ],
                    ],
                ],
            ]);
        });

        it('sets array elements by index', function(): void {
            $collection = DataCollection::make([
                'users' => [
                    ['name' => 'Alice'],
                    ['name' => 'Bob'],
                ],
            ]);

            $collection
                ->set('users.0.age', 30)
                ->set('users.1.age', 25);

            expect($collection->toArray())->toBe([
                'users' => [
                    ['name' => 'Alice', 'age' => 30],
                    ['name' => 'Bob', 'age' => 25],
                ],
            ]);
        });

        it('sets deeply nested values', function(): void {
            $collection = DataCollection::make([]);

            $collection
                ->set('company.departments.0.name', 'Engineering')
                ->set('company.departments.0.teams.0.name', 'Backend')
                ->set('company.departments.0.teams.0.members.0', 'Alice')
                ->set('company.departments.0.teams.0.members.1', 'Bob');

            expect($collection->toArray())->toBe([
                'company' => [
                    'departments' => [
                        [
                            'name' => 'Engineering',
                            'teams' => [
                                [
                                    'name' => 'Backend',
                                    'members' => ['Alice', 'Bob'],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        });

        it('overwrites existing values', function(): void {
            $collection = DataCollection::make([
                'user' => ['name' => 'Alice', 'age' => 30],
            ]);

            $collection
                ->set('user.name', 'Bob')
                ->set('user.age', 25);

            expect($collection->toArray())->toBe([
                'user' => ['name' => 'Bob', 'age' => 25],
            ]);
        });

        it('sets values in existing nested structures', function(): void {
            $collection = DataCollection::make([
                'data' => [
                    'users' => [
                        ['id' => 1, 'name' => 'Alice'],
                        ['id' => 2, 'name' => 'Bob'],
                    ],
                ],
            ]);

            $collection
                ->set('data.users.0.email', 'alice@example.com')
                ->set('data.users.1.email', 'bob@example.com')
                ->set('data.settings.theme', 'dark');

            expect($collection->toArray())->toBe([
                'data' => [
                    'users' => [
                        ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
                        ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
                    ],
                    'settings' => ['theme' => 'dark'],
                ],
            ]);
        });
    });

    describe('Merge Operations', function(): void {
        it('merges simple arrays', function(): void {
            $collection = DataCollection::make([
                'user' => ['name' => 'Alice'],
            ]);

            $collection->merge('user', ['age' => 30, 'city' => 'Berlin']);

            expect($collection->toArray())->toBe([
                'user' => ['name' => 'Alice', 'age' => 30, 'city' => 'Berlin'],
            ]);
        });

        it('merges nested arrays', function(): void {
            $collection = DataCollection::make([
                'user' => [
                    'profile' => ['name' => 'Alice'],
                ],
            ]);

            $collection->merge('user.profile', [
                'age' => 30,
                'contact' => ['email' => 'alice@example.com'],
            ]);

            expect($collection->toArray())->toBe([
                'user' => [
                    'profile' => [
                        'name' => 'Alice',
                        'age' => 30,
                        'contact' => ['email' => 'alice@example.com'],
                    ],
                ],
            ]);
        });

        it('merges multiple paths at once', function(): void {
            $collection = DataCollection::make([
                'users' => [
                    ['name' => 'Alice'],
                    ['name' => 'Bob'],
                    ['name' => 'Charlie'],
                ],
            ]);

            $collection->merge([
                'users.0.age' => 30,
                'users.1.age' => 25,
                'users.2.age' => 35,
            ]);

            expect($collection->toArray())->toBe([
                'users' => [
                    ['name' => 'Alice', 'age' => 30],
                    ['name' => 'Bob', 'age' => 25],
                    ['name' => 'Charlie', 'age' => 35],
                ],
            ]);
        });

        it('merges overwrites existing keys', function(): void {
            $collection = DataCollection::make([
                'user' => ['name' => 'Alice', 'age' => 30],
            ]);

            $collection->merge('user', ['age' => 31, 'city' => 'Berlin']);

            expect($collection->toArray())->toBe([
                'user' => ['name' => 'Alice', 'age' => 31, 'city' => 'Berlin'],
            ]);
        });

        it('merges deeply nested structures', function(): void {
            $collection = DataCollection::make([
                'company' => [
                    'departments' => [
                        ['name' => 'Engineering', 'budget' => 100000],
                    ],
                ],
            ]);

            $collection->merge('company.departments.0', [
                'budget' => 120000,
                'head' => 'Alice',
                'teams' => ['Backend', 'Frontend'],
            ]);

            expect($collection->toArray())->toBe([
                'company' => [
                    'departments' => [
                        [
                            'name' => 'Engineering',
                            'budget' => 120000,
                            'head' => 'Alice',
                            'teams' => ['Backend', 'Frontend'],
                        ],
                    ],
                ],
            ]);
        });
    });

    describe('Forget Operations', function(): void {
        it('forgets simple keys', function(): void {
            $collection = DataCollection::make([
                'name' => 'Alice',
                'age' => 30,
                'city' => 'Berlin',
            ]);

            $collection->forget('age');

            expect($collection->toArray())->toBe([
                'name' => 'Alice',
                'city' => 'Berlin',
            ]);
        });

        it('forgets nested keys', function(): void {
            $collection = DataCollection::make([
                'user' => [
                    'name' => 'Alice',
                    'age' => 30,
                    'contact' => [
                        'email' => 'alice@example.com',
                        'phone' => '123-456',
                    ],
                ],
            ]);

            $collection->forget('user.contact.phone');

            expect($collection->toArray())->toBe([
                'user' => [
                    'name' => 'Alice',
                    'age' => 30,
                    'contact' => [
                        'email' => 'alice@example.com',
                    ],
                ],
            ]);
        });

        it('forgets array elements', function(): void {
            $collection = DataCollection::make([
                'users' => [
                    ['name' => 'Alice', 'age' => 30],
                    ['name' => 'Bob', 'age' => 25],
                ],
            ]);

            $collection->forget('users.0.age');

            expect($collection->toArray())->toBe([
                'users' => [
                    ['name' => 'Alice'],
                    ['name' => 'Bob', 'age' => 25],
                ],
            ]);
        });

        it('forgets deeply nested keys', function(): void {
            $collection = DataCollection::make([
                'company' => [
                    'departments' => [
                        [
                            'name' => 'Engineering',
                            'teams' => [
                                ['name' => 'Backend', 'size' => 5],
                                ['name' => 'Frontend', 'size' => 3],
                            ],
                        ],
                    ],
                ],
            ]);

            $collection->forget('company.departments.0.teams.1.size');

            expect($collection->toArray())->toBe([
                'company' => [
                    'departments' => [
                        [
                            'name' => 'Engineering',
                            'teams' => [
                                ['name' => 'Backend', 'size' => 5],
                                ['name' => 'Frontend'],
                            ],
                        ],
                    ],
                ],
            ]);
        });
    });

    describe('Transform Operations', function(): void {
        it('transforms simple values', function(): void {
            $collection = DataCollection::make([
                'name' => 'alice',
                'city' => 'berlin',
            ]);

            $collection
                ->transform('name', fn($v) => strtoupper($v))
                ->transform('city', fn($v) => ucfirst($v));

            expect($collection->toArray())->toBe([
                'name' => 'ALICE',
                'city' => 'Berlin',
            ]);
        });

        it('transforms nested values', function(): void {
            $collection = DataCollection::make([
                'user' => [
                    'profile' => [
                        'name' => 'alice',
                        'bio' => 'software developer',
                    ],
                ],
            ]);

            $collection
                ->transform('user.profile.name', fn($v) => strtoupper($v))
                ->transform('user.profile.bio', fn($v) => ucwords($v));

            expect($collection->toArray())->toBe([
                'user' => [
                    'profile' => [
                        'name' => 'ALICE',
                        'bio' => 'Software Developer',
                    ],
                ],
            ]);
        });

        it('transforms array elements', function(): void {
            $collection = DataCollection::make([
                'users' => [
                    ['name' => 'alice', 'score' => 100],
                    ['name' => 'bob', 'score' => 200],
                ],
            ]);

            $collection
                ->transform('users.0.name', fn($v) => strtoupper($v))
                ->transform('users.1.name', fn($v) => strtoupper($v))
                ->transform('users.0.score', fn($v) => $v + 10)
                ->transform('users.1.score', fn($v) => $v + 20);

            expect($collection->toArray())->toBe([
                'users' => [
                    ['name' => 'ALICE', 'score' => 110],
                    ['name' => 'BOB', 'score' => 220],
                ],
            ]);
        });

        it('transforms with complex callbacks', function(): void {
            $collection = DataCollection::make([
                'prices' => [
                    ['amount' => 100, 'currency' => 'USD'],
                    ['amount' => 200, 'currency' => 'EUR'],
                ],
            ]);

            $collection
                ->transform('prices.0.amount', fn($v) => $v * 0.9)
                ->transform('prices.1.amount', fn($v) => $v * 0.85);

            expect($collection->toArray())->toBe([
                'prices' => [
                    ['amount' => 90.0, 'currency' => 'USD'],
                    ['amount' => 170.0, 'currency' => 'EUR'],
                ],
            ]);
        });
    });

    describe('PushTo Operations', function(): void {
        it('pushes to simple arrays', function(): void {
            $collection = DataCollection::make([
                'tags' => ['php', 'laravel'],
            ]);

            $collection
                ->pushTo('tags', 'symfony')
                ->pushTo('tags', 'doctrine');

            expect($collection->toArray())->toBe([
                'tags' => ['php', 'laravel', 'symfony', 'doctrine'],
            ]);
        });

        it('pushes to nested arrays', function(): void {
            $collection = DataCollection::make([
                'user' => [
                    'profile' => [
                        'skills' => ['php', 'javascript'],
                    ],
                ],
            ]);

            $collection
                ->pushTo('user.profile.skills', 'python')
                ->pushTo('user.profile.skills', 'rust');

            expect($collection->toArray())->toBe([
                'user' => [
                    'profile' => [
                        'skills' => ['php', 'javascript', 'python', 'rust'],
                    ],
                ],
            ]);
        });

        it('pushes to array elements', function(): void {
            $collection = DataCollection::make([
                'users' => [
                    ['name' => 'Alice', 'tags' => ['admin']],
                    ['name' => 'Bob', 'tags' => ['user']],
                ],
            ]);

            $collection
                ->pushTo('users.0.tags', 'moderator')
                ->pushTo('users.1.tags', 'guest');

            expect($collection->toArray())->toBe([
                'users' => [
                    ['name' => 'Alice', 'tags' => ['admin', 'moderator']],
                    ['name' => 'Bob', 'tags' => ['user', 'guest']],
                ],
            ]);
        });

        it('pushes creates array if not exists', function(): void {
            $collection = DataCollection::make([
                'user' => ['name' => 'Alice'],
            ]);

            $collection->pushTo('user.tags', 'php');

            expect($collection->toArray())->toBe([
                'user' => ['name' => 'Alice', 'tags' => ['php']],
            ]);
        });
    });

    describe('Pull Operations', function(): void {
        it('pulls simple values', function(): void {
            $collection = DataCollection::make([
                'name' => 'Alice',
                'age' => 30,
                'city' => 'Berlin',
            ]);

            $age = $collection->pull('age');

            expect($age)->toBe(30)
                ->and($collection->toArray())->toBe([
                    'name' => 'Alice',
                    'city' => 'Berlin',
                ]);
        });

        it('pulls nested values', function(): void {
            $collection = DataCollection::make([
                'user' => [
                    'profile' => [
                        'name' => 'Alice',
                        'age' => 30,
                        'city' => 'Berlin',
                    ],
                ],
            ]);

            $age = $collection->pull('user.profile.age');

            expect($age)->toBe(30)
                ->and($collection->toArray())->toBe([
                    'user' => [
                        'profile' => [
                            'name' => 'Alice',
                            'city' => 'Berlin',
                        ],
                    ],
                ]);
        });

        it('pulls array elements', function(): void {
            $collection = DataCollection::make([
                'users' => [
                    ['name' => 'Alice', 'age' => 30],
                    ['name' => 'Bob', 'age' => 25],
                ],
            ]);

            $age = $collection->pull('users.0.age');

            expect($age)->toBe(30)
                ->and($collection->toArray())->toBe([
                    'users' => [
                        ['name' => 'Alice'],
                        ['name' => 'Bob', 'age' => 25],
                    ],
                ]);
        });

        it('pulls with default for non-existent paths', function(): void {
            $collection = DataCollection::make([
                'user' => ['name' => 'Alice'],
            ]);

            $age = $collection->pull('user.age', 25);
            $city = $collection->pull('user.city', 'Unknown');

            expect($age)->toBe(25)
                ->and($city)->toBe('Unknown')
                ->and($collection->toArray())->toBe([
                    'user' => ['name' => 'Alice'],
                ]);
        });

        it('pulls deeply nested values', function(): void {
            $collection = DataCollection::make([
                'company' => [
                    'departments' => [
                        [
                            'name' => 'Engineering',
                            'teams' => [
                                ['name' => 'Backend', 'budget' => 50000],
                            ],
                        ],
                    ],
                ],
            ]);

            $budget = $collection->pull('company.departments.0.teams.0.budget');

            expect($budget)->toBe(50000)
                ->and($collection->toArray())->toBe([
                    'company' => [
                        'departments' => [
                            [
                                'name' => 'Engineering',
                                'teams' => [
                                    ['name' => 'Backend'],
                                ],
                            ],
                        ],
                    ],
                ]);
        });
    });

    describe('Complex Scenarios', function(): void {
        it('combines read and write operations', function(): void {
            $collection = DataCollection::make([
                'users' => [
                    ['name' => 'Alice', 'score' => 85],
                    ['name' => 'Bob', 'score' => 92],
                ],
            ]);

            // Read
            $aliceScore = $collection->get('users.0.score');
            expect($aliceScore)->toBe(85);

            // Write
            $collection->set('users.0.score', 90);
            expect($collection->get('users.0.score'))->toBe(90);

            // Transform
            $collection->transform('users.1.score', fn($v) => $v + 5);
            expect($collection->get('users.1.score'))->toBe(97);
        });

        it('chains all mutable operations', function(): void {
            $collection = DataCollection::make([
                'project' => [
                    'name' => 'data-helpers',
                    'version' => '1.0.0',
                ],
            ]);

            $collection
                ->set('project.author', 'event4u')
                ->merge('project', ['license' => 'MIT', 'status' => 'active'])
                ->transform('project.name', fn($v) => strtoupper($v))
                ->pushTo('project.tags', 'php')
                ->pushTo('project.tags', 'library');

            expect($collection->toArray())->toBe([
                'project' => [
                    'name' => 'DATA-HELPERS',
                    'version' => '1.0.0',
                    'author' => 'event4u',
                    'license' => 'MIT',
                    'status' => 'active',
                    'tags' => ['php', 'library'],
                ],
            ]);
        });

        it('handles complex nested modifications', function(): void {
            $collection = DataCollection::make([
                'company' => [
                    'name' => 'TechCorp',
                    'departments' => [
                        [
                            'name' => 'Engineering',
                            'employees' => [
                                ['name' => 'Alice', 'salary' => 80000],
                                ['name' => 'Bob', 'salary' => 75000],
                            ],
                        ],
                    ],
                ],
            ]);

            $collection
                ->set('company.departments.0.employees.0.bonus', 5000)
                ->set('company.departments.0.employees.1.bonus', 4000)
                ->transform('company.departments.0.employees.0.salary', fn($v) => $v * 1.1)
                ->transform('company.departments.0.employees.1.salary', fn($v) => $v * 1.1)
                ->merge('company', ['founded' => 2020])
                ->pushTo('company.departments.0.employees.0.skills', 'PHP')
                ->pushTo('company.departments.0.employees.0.skills', 'Laravel');

            $result = $collection->toArray();

            expect($result['company']['name'])->toBe('TechCorp')
                ->and($result['company']['founded'])->toBe(2020)
                ->and($result['company']['departments'][0]['employees'][0]['salary'])->toBe(88000.0)
                ->and($result['company']['departments'][0]['employees'][0]['bonus'])->toBe(5000)
                ->and($result['company']['departments'][0]['employees'][0]['skills'])->toBe(['PHP', 'Laravel'])
                ->and($result['company']['departments'][0]['employees'][1]['salary'])->toBe(82500.0);
        });

        it('modifies and reads in sequence', function(): void {
            $collection = DataCollection::make([
                'config' => [
                    'database' => ['host' => 'localhost', 'port' => 3306],
                ],
            ]);

            // Initial read
            expect($collection->get('config.database.host'))->toBe('localhost');

            // Modify
            $collection->set('config.database.host', '127.0.0.1');

            // Read after modification
            expect($collection->get('config.database.host'))->toBe('127.0.0.1');

            // Add new value
            $collection->set('config.database.username', 'root');

            // Read new value
            expect($collection->get('config.database.username'))->toBe('root');

            // Merge
            $collection->merge('config.database', ['password' => 'secret', 'charset' => 'utf8mb4']);

            // Read merged values
            expect($collection->get('config.database.password'))->toBe('secret')
                ->and($collection->get('config.database.charset'))->toBe('utf8mb4');
        });

        it('handles array of objects modifications', function(): void {
            $collection = DataCollection::make([
                'products' => [
                    ['id' => 1, 'name' => 'Laptop', 'price' => 1000, 'stock' => 5],
                    ['id' => 2, 'name' => 'Mouse', 'price' => 25, 'stock' => 50],
                    ['id' => 3, 'name' => 'Keyboard', 'price' => 75, 'stock' => 30],
                ],
            ]);

            // Apply discount to all products
            $collection
                ->transform('products.0.price', fn($v) => $v * 0.9)
                ->transform('products.1.price', fn($v) => $v * 0.9)
                ->transform('products.2.price', fn($v) => $v * 0.9);

            // Add tags
            $collection
                ->pushTo('products.0.tags', 'electronics')
                ->pushTo('products.0.tags', 'computers')
                ->pushTo('products.1.tags', 'accessories')
                ->pushTo('products.2.tags', 'accessories');

            // Update stock
            $collection
                ->set('products.0.stock', 3)
                ->set('products.1.stock', 45);

            $result = $collection->toArray();

            expect($result['products'][0]['price'])->toBe(900.0)
                ->and($result['products'][0]['stock'])->toBe(3)
                ->and($result['products'][0]['tags'])->toBe(['electronics', 'computers'])
                ->and($result['products'][1]['price'])->toBe(22.5)
                ->and($result['products'][1]['stock'])->toBe(45)
                ->and($result['products'][2]['price'])->toBe(67.5);
        });
    });

    describe('Edge Cases and Special Scenarios', function(): void {
        it('handles non-existent paths with default', function(): void {
            $collection = DataCollection::make(['name' => 'Alice']);

            expect($collection->get('missing.path', 'default'))->toBe('default');
        });

        it('handles numeric string keys', function(): void {
            $collection = DataCollection::make([
                '0' => 'zero',
                '1' => 'one',
                '2' => 'two',
            ]);

            $collection->set('0', 'ZERO');

            expect($collection->get('0'))->toBe('ZERO');
        });

        it('handles mixed key types', function(): void {
            $collection = DataCollection::make([
                'string_key' => 'value1',
                0 => 'value2',
                'nested' => [
                    'key' => 'value3',
                    0 => 'value4',
                ],
            ]);

            expect($collection->get('string_key'))->toBe('value1')
                ->and($collection->get('0'))->toBe('value2')
                ->and($collection->get('nested.key'))->toBe('value3')
                ->and($collection->get('nested.0'))->toBe('value4');
        });

        it('handles null values', function(): void {
            $collection = DataCollection::make([
                'user' => ['name' => 'Alice', 'age' => null],
            ]);

            expect($collection->get('user.age'))->toBeNull();

            $collection->set('user.age', 30);
            expect($collection->get('user.age'))->toBe(30);
        });

        it('handles boolean values', function(): void {
            $collection = DataCollection::make([
                'settings' => ['active' => true, 'debug' => false],
            ]);

            expect($collection->get('settings.active'))->toBe(true)
                ->and($collection->get('settings.debug'))->toBe(false);

            $collection->set('settings.debug', true);
            expect($collection->get('settings.debug'))->toBe(true);
        });

        it('handles array values', function(): void {
            $collection = DataCollection::make([
                'user' => ['tags' => ['php', 'laravel']],
            ]);

            $tags = $collection->get('user.tags');
            expect($tags)->toBe(['php', 'laravel']);

            $collection->set('user.tags', ['php', 'symfony']);
            expect($collection->get('user.tags'))->toBe(['php', 'symfony']);
        });

        it('preserves data types after modifications', function(): void {
            $collection = DataCollection::make([
                'data' => [
                    'string' => 'text',
                    'int' => 42,
                    'float' => 3.14,
                    'bool' => true,
                    'null' => null,
                    'array' => [1, 2, 3],
                ],
            ]);

            $collection
                ->set('data.new_string', 'new text')
                ->set('data.new_int', 100)
                ->set('data.new_float', 2.71)
                ->set('data.new_bool', false);

            expect($collection->get('data.string'))->toBeString()
                ->and($collection->get('data.int'))->toBeInt()
                ->and($collection->get('data.float'))->toBeFloat()
                ->and($collection->get('data.bool'))->toBeBool()
                ->and($collection->get('data.null'))->toBeNull()
                ->and($collection->get('data.array'))->toBeArray();
        });

        it('handles very deep nesting', function(): void {
            $collection = DataCollection::make([]);

            $collection->set('a.b.c.d.e.f.g.h.i.j', 'deep value');

            expect($collection->get('a.b.c.d.e.f.g.h.i.j'))->toBe('deep value');

            $collection->transform('a.b.c.d.e.f.g.h.i.j', fn($v) => strtoupper($v));

            expect($collection->get('a.b.c.d.e.f.g.h.i.j'))->toBe('DEEP VALUE');
        });

        it('handles large arrays efficiently', function(): void {
            $items = [];
            for ($i = 0; $i < 1000; $i++) {
                $items[] = ['id' => $i, 'value' => "item_{$i}"];
            }

            $collection = DataCollection::make(['items' => $items]);

            // Read
            expect($collection->get('items.500.id'))->toBe(500);

            // Write
            $collection->set('items.500.value', 'modified');
            expect($collection->get('items.500.value'))->toBe('modified');

            // Transform
            $collection->transform('items.999.value', fn($v) => strtoupper($v));
            expect($collection->get('items.999.value'))->toBe('ITEM_999');
        });
    });
});
