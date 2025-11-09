<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\DtoCollection;

// Test Dtos
class DataCollectionEdgeCaseUserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

class DataCollectionEdgeCaseProductDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
    ) {}
}

describe('DataCollection Edge Cases', function(): void {
    describe('Constructor Edge Cases', function(): void {
        it('handles empty array', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, []);

            expect($collection->count())->toBe(0)
                ->and($collection->isEmpty())->toBeTrue()
                ->and($collection->all())->toBe([]);
        });

        it('handles mixed Dtos and arrays', function(): void {
            $dto = new DataCollectionEdgeCaseUserDto('John', 30);
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                $dto,
                ['name' => 'Jane', 'age' => 25],
            ]);

            $last = $collection->last();
            assert($last instanceof DataCollectionEdgeCaseUserDto);

            expect($collection->count())->toBe(2)
                ->and($collection->first())->toBe($dto)
                ->and($last)->toBeInstanceOf(DataCollectionEdgeCaseUserDto::class)
                ->and($last->name)->toBe('Jane');
        });

        it('throws exception for invalid item type', function(): void {
            expect(fn(): DtoCollection => DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [ // @phpstan-ignore return.type
                'invalid string',
            ]))->toThrow(InvalidArgumentException::class);
        });

        it('throws exception for wrong Dto class', function(): void {
            $wrongDto = new DataCollectionEdgeCaseProductDto('Product', 99.99);

            try {
                DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [$wrongDto]);
                expect(true)->toBeFalse('Expected InvalidArgumentException to be thrown');
            } catch (InvalidArgumentException $invalidArgumentException) {
                expect($invalidArgumentException->getMessage())->toContain('DataCollectionEdgeCaseUserDto');
            }
        });

        it('handles null values in array data', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            expect($collection->count())->toBe(2);
        });
    });

    describe('Filter Edge Cases', function(): void {
        it('filters with null callback removes falsy values', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $filtered = $collection->filter();

            expect($filtered->count())->toBe(2);
        });

        it('filter removes all items', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $filtered = $collection->filter(fn(): false => false);

            expect($filtered->count())->toBe(0)
                ->and($filtered->isEmpty())->toBeTrue();
        });

        it('filter on empty collection', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, []);

            /** @phpstan-ignore-next-line unknown */
            $filtered = $collection->filter(fn(DataCollectionEdgeCaseUserDto $u): bool => 20 < $u->age);

            expect($filtered->count())->toBe(0);
        });

        it('filter preserves original collection', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $filtered = $collection->filter(fn(DataCollectionEdgeCaseUserDto $u): bool => 25 < $u->age);

            expect($collection->count())->toBe(2)
                ->and($filtered->count())->toBe(1);
        });

        it('filter resets array keys', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $filtered = $collection->filter(fn(DataCollectionEdgeCaseUserDto $u): bool => 25 < $u->age)->values();

            $first = $filtered->get(0);
            $second = $filtered->get(1);
            assert($first instanceof DataCollectionEdgeCaseUserDto);
            assert($second instanceof DataCollectionEdgeCaseUserDto);

            expect($first->name)->toBe('John')
                ->and($second->name)->toBe('Bob')
                ->and($filtered->get(2))->toBeNull();
        });
    });

    describe('Map Edge Cases', function(): void {
        it('maps to different types', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            // Note: DtoCollection::map() returns DTOs, not arbitrary values
            // Use array_column() or array_map() to extract scalar values
            $names = array_column($collection->toArray(), 'name');
            $ages = array_column($collection->toArray(), 'age');
            $combined = array_map(
                fn(array $item): string => sprintf('%s:%d', $item['name'], $item['age']),
                $collection->toArray()
            );

            expect($names)->toBe(['John', 'Jane'])
                ->and($ages)->toBe([30, 25])
                ->and($combined)->toBe(['John:30', 'Jane:25']);
        });

        it('maps on empty collection', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, []);

            // Note: DtoCollection::map() returns DTOs, not arbitrary values
            $result = array_column($collection->toArray(), 'name');

            expect($result)->toBe([]);
        });

        it('map preserves original collection', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            // Note: DtoCollection::map() returns DTOs, not arbitrary values
            $names = array_column($collection->toArray(), 'name');

            expect($collection->count())->toBe(1)
                ->and($collection->first())->toBeInstanceOf(DataCollectionEdgeCaseUserDto::class);
        });

        it('maps to complex structures', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            // Note: DtoCollection::map() returns DTOs, not arbitrary values
            $result = array_map(
                fn(array $item): array => [
                    'fullName' => strtoupper((string)$item['name']),
                    'ageInMonths' => ((int)$item['age']) * 12,
                ],
                $collection->toArray()
            );

            expect($result)->toBe([
                ['fullName' => 'JOHN', 'ageInMonths' => 360],
                ['fullName' => 'JANE', 'ageInMonths' => 300],
            ]);
        });
    });

    describe('Reduce Edge Cases', function(): void {
        it('reduces without initial value', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $result = $collection->reduce(
                /** @phpstan-ignore-next-line unknown */
                fn(?int $carry, DataCollectionEdgeCaseUserDto $u): int => ($carry ?? 0) + $u->age
            );

            expect($result)->toBe(55);
        });

        it('reduces on empty collection with initial value', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, []);

            $result = $collection->reduce(
                /** @phpstan-ignore-next-line unknown */
                fn(int $carry, DataCollectionEdgeCaseUserDto $u): int => $carry + $u->age,
                100
            );

            expect($result)->toBe(100);
        });

        it('reduces on empty collection without initial value', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, []);

            $result = $collection->reduce(
                /** @phpstan-ignore-next-line unknown */
                fn(?int $carry, DataCollectionEdgeCaseUserDto $u): int => ($carry ?? 0) + $u->age
            );

            expect($result)->toBeNull();
        });

        it('reduces to complex structure', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            $result = $collection->reduce(
                /** @phpstan-ignore-next-line unknown */
                fn(array $carry, DataCollectionEdgeCaseUserDto $u): array => array_merge($carry, [$u->name => $u->age]),
                []
            );

            expect($result)->toBe(['John' => 30, 'Jane' => 25, 'Bob' => 35]);
        });
    });

    describe('First/Last Edge Cases', function(): void {
        it('first on empty collection returns null', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, []);

            expect($collection->first())->toBeNull();
        });

        it('last on empty collection returns null', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, []);

            expect($collection->last())->toBeNull();
        });

        it('first with callback that finds nothing returns default', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $default = new DataCollectionEdgeCaseUserDto('Default', 0);
            /** @phpstan-ignore-next-line unknown */
            $result = $collection->first(fn(DataCollectionEdgeCaseUserDto $u): bool => 100 < $u->age, $default);

            expect($result)->toBe($default);
        });

        it('last with callback that finds nothing returns default', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $default = new DataCollectionEdgeCaseUserDto('Default', 0);
            /** @phpstan-ignore-next-line unknown */
            $result = $collection->last(fn(DataCollectionEdgeCaseUserDto $u): bool => 100 < $u->age, $default);

            expect($result)->toBe($default);
        });

        it('first with callback returns first match', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 35],
                ['name' => 'Bob', 'age' => 40],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $result = $collection->first(fn(DataCollectionEdgeCaseUserDto $u): bool => 30 < $u->age);
            assert($result instanceof DataCollectionEdgeCaseUserDto);

            expect($result->name)->toBe('Jane');
        });

        it('last with callback returns last match', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 35],
                ['name' => 'Bob', 'age' => 40],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $result = $collection->last(fn(DataCollectionEdgeCaseUserDto $u): bool => 40 > $u->age);
            assert($result instanceof DataCollectionEdgeCaseUserDto);

            expect($result->name)->toBe('Jane');
        });
    });

    describe('Array Access Edge Cases', function(): void {
        it('handles negative indices', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            expect($collection[-1])->toBeNull();
        });

        it('handles out of bounds access', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            expect($collection[100])->toBeNull()
                ->and(isset($collection[100]))->toBeFalse();
        });

        it('can set items via array access', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $collection[0] = ['name' => 'Jane', 'age' => 25];

            $item = $collection[0];
            /** @phpstan-ignore-next-line unknown */
            /** @phpstan-ignore-next-line unknown */
            assert($item instanceof DataCollectionEdgeCaseUserDto);
            expect($item->name)->toBe('Jane');
        });

        it('can append via array access with null offset', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $collection[] = ['name' => 'Jane', 'age' => 25];

            $item = $collection[1];
            assert($item instanceof DataCollectionEdgeCaseUserDto);

            expect($collection->count())->toBe(2)
                ->and($item->name)->toBe('Jane');
        });

        it('can unset items', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            unset($collection[0]);

            expect(isset($collection[0]))->toBeFalse()
                ->and(isset($collection[1]))->toBeTrue();
        });

        it('throws exception when setting invalid data via array access', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, []);

            /** @phpstan-ignore-next-line unknown */
            expect(fn(): string => $collection[0] = 'invalid')->toThrow(InvalidArgumentException::class);
        });
    });

    describe('Iteration Edge Cases', function(): void {
        it('iterates over empty collection', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, []);

            $count = 0;
            foreach ($collection as $item) {
                $count++;
            }

            expect($count)->toBe(0);
        });

        it('can break during iteration', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            $names = [];
            /** @var DataCollectionEdgeCaseUserDto $user */
            foreach ($collection as $user) {
                $names[] = $user->name;
                if ('Jane' === $user->name) {
                    break;
                }
            }

            expect($names)->toBe(['John', 'Jane']);
        });

        it('provides correct items during iteration', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $items = [];
            foreach ($collection as $item) {
                expect($item)->toBeInstanceOf(DataCollectionEdgeCaseUserDto::class);
                $items[] = $item;
            }

            expect(count($items))->toBe(2);
        });
    });

    describe('Push/Prepend Edge Cases', function(): void {
        it('push throws exception for invalid data', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, []);

            /** @phpstan-ignore-next-line unknown */
            expect(fn(): DataCollection => $collection->push('invalid'))->toThrow(InvalidArgumentException::class);
        });

        it('prepend throws exception for invalid data', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, []);

            /** @phpstan-ignore-next-line unknown */
            expect(fn(): DataCollection => $collection->prepend('invalid'))->toThrow(InvalidArgumentException::class);
        });

        it('push multiple items at once', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $collection->push(
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35]
            );

            $last = $collection->last();
            assert($last instanceof DataCollectionEdgeCaseUserDto);

            expect($collection->count())->toBe(3)
                ->and($last->name)->toBe('Bob');
        });

        it('push returns collection for chaining', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, []);

            $result = $collection
                ->push(['name' => 'John', 'age' => 30])
                ->push(['name' => 'Jane', 'age' => 25]);

            expect($result)->toBe($collection)
                ->and($collection->count())->toBe(2);
        });

        it('prepend returns collection for chaining', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, []);

            $result = $collection
                ->prepend(['name' => 'John', 'age' => 30])
                ->prepend(['name' => 'Jane', 'age' => 25]);

            $first = $collection->first();
            assert($first instanceof DataCollectionEdgeCaseUserDto);

            expect($result)->toBe($collection)
                ->and($collection->count())->toBe(2)
                ->and($first->name)->toBe('Jane');
        });
    });

    describe('Conversion Edge Cases', function(): void {
        it('toArray on empty collection', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, []);

            expect($collection->toArray())->toBe([]);
        });

        it('toJson on empty collection', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, []);

            expect($collection->toJson())->toBe('[]');
        });

        it('toJson with options', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $json = $collection->toJson(JSON_PRETTY_PRINT);

            expect($json)->toContain("\n")
                ->and($json)->toContain('John');
        });

        it('jsonSerialize returns correct structure', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
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

            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, $items);

            $first = $collection->first();
            $last = $collection->last();
            assert($first instanceof DataCollectionEdgeCaseUserDto);
            assert($last instanceof DataCollectionEdgeCaseUserDto);

            expect($collection->count())->toBe(1000)
                ->and($first->name)->toBe('User0')
                ->and($last->name)->toBe('User999');
        });
    });

    describe('WrapDto Edge Cases', function(): void {
        it('wraps existing collection of same type', function(): void {
            $original = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $wrapped = DtoCollection::wrapDto(DataCollectionEdgeCaseUserDto::class, $original);

            expect($wrapped)->toBe($original);
        });

        it('creates new collection for different Dto class', function(): void {
            $original = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $wrapped = DtoCollection::wrapDto(DataCollectionEdgeCaseProductDto::class, []);

            expect($wrapped)->not->toBe($original)
                ->and($wrapped->getDtoClass())->toBe(DataCollectionEdgeCaseProductDto::class);
        });

        it('wraps single item array', function(): void {
            $wrapped = DtoCollection::wrapDto(
                DataCollectionEdgeCaseUserDto::class,
                [['name' => 'John', 'age' => 30]]
            );

            $first = $wrapped->first();
            assert($first instanceof DataCollectionEdgeCaseUserDto);

            expect($wrapped->count())->toBe(1)
                ->and($first->name)->toBe('John');
        });

        it('wraps empty array', function(): void {
            $wrapped = DtoCollection::wrapDto(DataCollectionEdgeCaseUserDto::class, []);

            expect($wrapped->count())->toBe(0);
        });
    });

    describe('Utility Edge Cases', function(): void {
        it('get returns null for non-existent index', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            expect($collection->get(5))->toBeNull();
        });

        it('items returns internal array', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $items = $collection->items();

            expect($items)->toBeArray()
                ->and(count($items))->toBe(2)
                ->and($items[0])->toBeInstanceOf(DataCollectionEdgeCaseUserDto::class);
        });

        it('all returns same as items', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            expect($collection->all())->toBe($collection->items());
        });

        it('getDtoClass returns correct class', function(): void {
            $collection = DtoCollection::forDto(DataCollectionEdgeCaseUserDto::class, []);

            expect($collection->getDtoClass())->toBe(DataCollectionEdgeCaseUserDto::class);
        });
    });
});
