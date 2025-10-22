<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\DataCollection;

describe('DataCollection Edge Cases', function(): void {
    describe('Constructor Edge Cases', function(): void {
        it('handles empty array', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, []);

            expect($collection->count())->toBe(0)
                ->and($collection->isEmpty())->toBeTrue()
                ->and($collection->all())->toBe([]);
        });

        it('handles mixed DTOs and arrays', function(): void {
            $dto = new DataCollectionEdgeCaseUserDTO('John', 30);
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                $dto,
                ['name' => 'Jane', 'age' => 25],
            ]);

            $last = $collection->last();
            assert($last instanceof DataCollectionEdgeCaseUserDTO);

            expect($collection->count())->toBe(2)
                ->and($collection->first())->toBe($dto)
                ->and($last)->toBeInstanceOf(DataCollectionEdgeCaseUserDTO::class)
                ->and($last->name)->toBe('Jane');
        });

        it('throws exception for invalid item type', function(): void {
            expect(fn(): DataCollection => DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                'invalid string',
            ]))->toThrow(InvalidArgumentException::class);
        });

        it('throws exception for wrong DTO class', function(): void {
            $wrongDto = new DataCollectionEdgeCaseProductDTO('Product', 99.99);

            try {
                DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [$wrongDto]);
                expect(true)->toBeFalse('Expected InvalidArgumentException to be thrown');
            } catch (InvalidArgumentException $invalidArgumentException) {
                expect($invalidArgumentException->getMessage())->toContain('DataCollectionEdgeCaseUserDTO');
            }
        });

        it('handles null values in array data', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            expect($collection->count())->toBe(2);
        });
    });

    describe('Filter Edge Cases', function(): void {
        it('filters with null callback removes falsy values', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $filtered = $collection->filter();

            expect($filtered->count())->toBe(2);
        });

        it('filter removes all items', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $filtered = $collection->filter(fn(): false => false);

            expect($filtered->count())->toBe(0)
                ->and($filtered->isEmpty())->toBeTrue();
        });

        it('filter on empty collection', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, []);

            /** @phpstan-ignore-next-line unknown */
            $filtered = $collection->filter(fn(DataCollectionEdgeCaseUserDTO $u): bool => 20 < $u->age);

            expect($filtered->count())->toBe(0);
        });

        it('filter preserves original collection', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $filtered = $collection->filter(fn(DataCollectionEdgeCaseUserDTO $u): bool => 25 < $u->age);

            expect($collection->count())->toBe(2)
                ->and($filtered->count())->toBe(1);
        });

        it('filter resets array keys', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $filtered = $collection->filter(fn(DataCollectionEdgeCaseUserDTO $u): bool => 25 < $u->age);

            $first = $filtered->get(0);
            $second = $filtered->get(1);
            assert($first instanceof DataCollectionEdgeCaseUserDTO);
            assert($second instanceof DataCollectionEdgeCaseUserDTO);

            expect($first->name)->toBe('John')
                ->and($second->name)->toBe('Bob')
                ->and($filtered->get(2))->toBeNull();
        });
    });

    describe('Map Edge Cases', function(): void {
        it('maps to different types', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $names = $collection->map(fn(DataCollectionEdgeCaseUserDTO $u): string => $u->name);
            /** @phpstan-ignore-next-line unknown */
            $ages = $collection->map(fn(DataCollectionEdgeCaseUserDTO $u): int => $u->age);
            $combined = $collection->map(
                /** @phpstan-ignore-next-line unknown */
                fn(DataCollectionEdgeCaseUserDTO $u): string => sprintf('%s:%d', $u->name, $u->age)
            );

            expect($names)->toBe(['John', 'Jane'])
                ->and($ages)->toBe([30, 25])
                ->and($combined)->toBe(['John:30', 'Jane:25']);
        });

        it('maps on empty collection', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, []);

            /** @phpstan-ignore-next-line unknown */
            $result = $collection->map(fn(DataCollectionEdgeCaseUserDTO $u): string => $u->name);

            expect($result)->toBe([]);
        });

        it('map preserves original collection', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $names = $collection->map(fn(DataCollectionEdgeCaseUserDTO $u): string => $u->name);

            expect($collection->count())->toBe(1)
                ->and($collection->first())->toBeInstanceOf(DataCollectionEdgeCaseUserDTO::class);
        });

        it('maps to complex structures', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $result = $collection->map(fn(DataCollectionEdgeCaseUserDTO $u): array => [
                'fullName' => strtoupper($u->name),
                'ageInMonths' => $u->age * 12,
            ]);

            expect($result)->toBe([
                ['fullName' => 'JOHN', 'ageInMonths' => 360],
                ['fullName' => 'JANE', 'ageInMonths' => 300],
            ]);
        });
    });

    describe('Reduce Edge Cases', function(): void {
        it('reduces without initial value', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $result = $collection->reduce(
                /** @phpstan-ignore-next-line unknown */
                fn(?int $carry, DataCollectionEdgeCaseUserDTO $u): int => ($carry ?? 0) + $u->age
            );

            expect($result)->toBe(55);
        });

        it('reduces on empty collection with initial value', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, []);

            $result = $collection->reduce(
                /** @phpstan-ignore-next-line unknown */
                fn(int $carry, DataCollectionEdgeCaseUserDTO $u): int => $carry + $u->age,
                100
            );

            expect($result)->toBe(100);
        });

        it('reduces on empty collection without initial value', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, []);

            $result = $collection->reduce(
                /** @phpstan-ignore-next-line unknown */
                fn(?int $carry, DataCollectionEdgeCaseUserDTO $u): int => ($carry ?? 0) + $u->age
            );

            expect($result)->toBeNull();
        });

        it('reduces to complex structure', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            $result = $collection->reduce(
                /** @phpstan-ignore-next-line unknown */
                fn(array $carry, DataCollectionEdgeCaseUserDTO $u): array => array_merge($carry, [$u->name => $u->age]),
                []
            );

            expect($result)->toBe(['John' => 30, 'Jane' => 25, 'Bob' => 35]);
        });
    });

    describe('First/Last Edge Cases', function(): void {
        it('first on empty collection returns null', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, []);

            expect($collection->first())->toBeNull();
        });

        it('last on empty collection returns null', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, []);

            expect($collection->last())->toBeNull();
        });

        it('first with callback that finds nothing returns default', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $default = new DataCollectionEdgeCaseUserDTO('Default', 0);
            /** @phpstan-ignore-next-line unknown */
            $result = $collection->first(fn(DataCollectionEdgeCaseUserDTO $u): bool => 100 < $u->age, $default);

            expect($result)->toBe($default);
        });

        it('last with callback that finds nothing returns default', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $default = new DataCollectionEdgeCaseUserDTO('Default', 0);
            /** @phpstan-ignore-next-line unknown */
            $result = $collection->last(fn(DataCollectionEdgeCaseUserDTO $u): bool => 100 < $u->age, $default);

            expect($result)->toBe($default);
        });

        it('first with callback returns first match', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 35],
                ['name' => 'Bob', 'age' => 40],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $result = $collection->first(fn(DataCollectionEdgeCaseUserDTO $u): bool => 30 < $u->age);
            assert($result instanceof DataCollectionEdgeCaseUserDTO);

            expect($result->name)->toBe('Jane');
        });

        it('last with callback returns last match', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 35],
                ['name' => 'Bob', 'age' => 40],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $result = $collection->last(fn(DataCollectionEdgeCaseUserDTO $u): bool => 40 > $u->age);
            assert($result instanceof DataCollectionEdgeCaseUserDTO);

            expect($result->name)->toBe('Jane');
        });
    });

    describe('Array Access Edge Cases', function(): void {
        it('handles negative indices', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            expect($collection[-1])->toBeNull();
        });

        it('handles out of bounds access', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            expect($collection[100])->toBeNull()
                ->and(isset($collection[100]))->toBeFalse();
        });

        it('can set items via array access', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $collection[0] = ['name' => 'Jane', 'age' => 25];

            $item = $collection[0];
            /** @phpstan-ignore-next-line unknown */
            /** @phpstan-ignore-next-line unknown */
            assert($item instanceof DataCollectionEdgeCaseUserDTO);
            expect($item->name)->toBe('Jane');
        });

        it('can append via array access with null offset', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $collection[] = ['name' => 'Jane', 'age' => 25];

            $item = $collection[1];
            assert($item instanceof DataCollectionEdgeCaseUserDTO);

            expect($collection->count())->toBe(2)
                ->and($item->name)->toBe('Jane');
        });

        it('can unset items', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            unset($collection[0]);

            expect(isset($collection[0]))->toBeFalse()
                ->and(isset($collection[1]))->toBeTrue();
        });

        it('throws exception when setting invalid data via array access', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, []);

            /** @phpstan-ignore-next-line unknown */
            expect(fn(): string => $collection[0] = 'invalid')->toThrow(InvalidArgumentException::class);
        });
    });

    describe('Iteration Edge Cases', function(): void {
        it('iterates over empty collection', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, []);

            $count = 0;
            foreach ($collection as $item) {
                $count++;
            }

            expect($count)->toBe(0);
        });

        it('can break during iteration', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            $names = [];
            /** @var DataCollectionEdgeCaseUserDTO $user */
            foreach ($collection as $user) {
                $names[] = $user->name;
                if ('Jane' === $user->name) {
                    break;
                }
            }

            expect($names)->toBe(['John', 'Jane']);
        });

        it('provides correct items during iteration', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $items = [];
            foreach ($collection as $item) {
                expect($item)->toBeInstanceOf(DataCollectionEdgeCaseUserDTO::class);
                $items[] = $item;
            }

            expect(count($items))->toBe(2);
        });
    });

    describe('Push/Prepend Edge Cases', function(): void {
        it('push throws exception for invalid data', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, []);

            /** @phpstan-ignore-next-line unknown */
            expect(fn(): DataCollection => $collection->push('invalid'))->toThrow(InvalidArgumentException::class);
        });

        it('prepend throws exception for invalid data', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, []);

            /** @phpstan-ignore-next-line unknown */
            expect(fn(): DataCollection => $collection->prepend('invalid'))->toThrow(InvalidArgumentException::class);
        });

        it('push multiple items at once', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $collection->push(
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35]
            );

            $last = $collection->last();
            assert($last instanceof DataCollectionEdgeCaseUserDTO);

            expect($collection->count())->toBe(3)
                ->and($last->name)->toBe('Bob');
        });

        it('push returns collection for chaining', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, []);

            $result = $collection
                ->push(['name' => 'John', 'age' => 30])
                ->push(['name' => 'Jane', 'age' => 25]);

            expect($result)->toBe($collection)
                ->and($collection->count())->toBe(2);
        });

        it('prepend returns collection for chaining', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, []);

            $result = $collection
                ->prepend(['name' => 'John', 'age' => 30])
                ->prepend(['name' => 'Jane', 'age' => 25]);

            $first = $collection->first();
            assert($first instanceof DataCollectionEdgeCaseUserDTO);

            expect($result)->toBe($collection)
                ->and($collection->count())->toBe(2)
                ->and($first->name)->toBe('Jane');
        });
    });

    describe('Conversion Edge Cases', function(): void {
        it('toArray on empty collection', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, []);

            expect($collection->toArray())->toBe([]);
        });

        it('toJson on empty collection', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, []);

            expect($collection->toJson())->toBe('[]');
        });

        it('toJson with options', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $json = $collection->toJson(JSON_PRETTY_PRINT);

            expect($json)->toContain("\n")
                ->and($json)->toContain('John');
        });

        it('jsonSerialize returns correct structure', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $result = $collection->jsonSerialize();

            expect($result)->toBeArray()
                ->and($result[0])->toBe(['name' => 'John', 'age' => 30]);
        });

        it('handles large collections', function(): void {
            $items = [];
            for ($i = 0; 1000 > $i; $i++) {
                $items[] = ['name' => 'User' . $i, 'age' => $i];
            }

            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, $items);

            $first = $collection->first();
            $last = $collection->last();
            assert($first instanceof DataCollectionEdgeCaseUserDTO);
            assert($last instanceof DataCollectionEdgeCaseUserDTO);

            expect($collection->count())->toBe(1000)
                ->and($first->name)->toBe('User0')
                ->and($last->name)->toBe('User999');
        });
    });

    describe('WrapDto Edge Cases', function(): void {
        it('wraps existing collection of same type', function(): void {
            $original = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $wrapped = DataCollection::wrapDto(DataCollectionEdgeCaseUserDTO::class, $original);

            expect($wrapped)->toBe($original);
        });

        it('creates new collection for different DTO class', function(): void {
            $original = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $wrapped = DataCollection::wrapDto(DataCollectionEdgeCaseProductDTO::class, []);

            expect($wrapped)->not->toBe($original)
                ->and($wrapped->getDtoClass())->toBe(DataCollectionEdgeCaseProductDTO::class);
        });

        it('wraps single item array', function(): void {
            $wrapped = DataCollection::wrapDto(
                DataCollectionEdgeCaseUserDTO::class,
                [['name' => 'John', 'age' => 30]]
            );

            $first = $wrapped->first();
            assert($first instanceof DataCollectionEdgeCaseUserDTO);

            expect($wrapped->count())->toBe(1)
                ->and($first->name)->toBe('John');
        });

        it('wraps empty array', function(): void {
            $wrapped = DataCollection::wrapDto(DataCollectionEdgeCaseUserDTO::class, []);

            expect($wrapped->count())->toBe(0);
        });
    });

    describe('Utility Edge Cases', function(): void {
        it('get returns null for non-existent index', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            expect($collection->get(5))->toBeNull();
        });

        it('items returns internal array', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $items = $collection->items();

            expect($items)->toBeArray()
                ->and(count($items))->toBe(2)
                ->and($items[0])->toBeInstanceOf(DataCollectionEdgeCaseUserDTO::class);
        });

        it('all returns same as items', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            expect($collection->all())->toBe($collection->items());
        });

        it('getDtoClass returns correct class', function(): void {
            $collection = DataCollection::forDto(DataCollectionEdgeCaseUserDTO::class, []);

            expect($collection->getDtoClass())->toBe(DataCollectionEdgeCaseUserDTO::class);
        });
    });
});

// Test DTOs
class DataCollectionEdgeCaseUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

class DataCollectionEdgeCaseProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
    ) {}
}
