<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\DataCollection;

class DataCollectionUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

describe('DataCollection', function(): void {
    describe('Creation', function(): void {
        it('creates collection from arrays', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $first = $collection->first();
            assert($first instanceof DataCollectionUserDTO);

            expect($collection)->toBeInstanceOf(DataCollection::class)
                ->and($collection->count())->toBe(2)
                ->and($first)->toBeInstanceOf(DataCollectionUserDTO::class)
                ->and($first->name)->toBe('John');
        });

        it('creates collection from DTOs', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                new DataCollectionUserDTO('John', 30),
                new DataCollectionUserDTO('Jane', 25),
            ]);

            $first = $collection->first();
            assert($first instanceof DataCollectionUserDTO);

            expect($collection->count())->toBe(2)
                ->and($first->name)->toBe('John');
        });

        it('creates empty collection', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class);

            expect($collection->isEmpty())->toBeTrue()
                ->and($collection->count())->toBe(0);
        });

        it('creates collection via static collection method', function(): void {
            $collection = DataCollectionUserDTO::collection([
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            expect($collection)->toBeInstanceOf(DataCollection::class)
                ->and($collection->count())->toBe(2)
                ->and($collection->getDtoClass())->toBe(DataCollectionUserDTO::class);
        });
    });

    describe('Type Safety', function(): void {
        it('returns correct DTO class', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class);

            expect($collection->getDtoClass())->toBe(DataCollectionUserDTO::class);
        });

        it('throws exception for invalid item type', function(): void {
            expect(fn(): DataCollection => DataCollection::forDto(DataCollectionUserDTO::class, [
                'invalid',
            ]))->toThrow(InvalidArgumentException::class);
        });

        it('ensures DTOs when pushing', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class);

            $collection->push(['name' => 'John', 'age' => 30]);

            $first = $collection->first();
            assert($first instanceof DataCollectionUserDTO);

            expect($first)->toBeInstanceOf(DataCollectionUserDTO::class)
                ->and($first->name)->toBe('John');
        });

        it('ensures DTOs when prepending', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'Jane', 'age' => 25],
            ]);

            $collection->prepend(['name' => 'John', 'age' => 30]);

            $first = $collection->first();
            assert($first instanceof DataCollectionUserDTO);

            expect($first)->toBeInstanceOf(DataCollectionUserDTO::class)
                ->and($first->name)->toBe('John');
        });

        it('supports array access', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $item0 = $collection[0];
            $item1 = $collection[1];
            assert($item0 instanceof DataCollectionUserDTO);
            assert($item1 instanceof DataCollectionUserDTO);

            expect($item0)->toBeInstanceOf(DataCollectionUserDTO::class)
                ->and($item0->name)->toBe('John')
                ->and($item1->name)->toBe('Jane')
                ->and(isset($collection[0]))->toBeTrue()
                ->and(isset($collection[5]))->toBeFalse();
        });
    });

    describe('Collection Methods', function(): void {
        it('filters items', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $filtered = $collection->filter(fn(DataCollectionUserDTO $user): bool => 30 <= $user->age);

            $first = $filtered->first();
            $last = $filtered->last();
            assert($first instanceof DataCollectionUserDTO);
            assert($last instanceof DataCollectionUserDTO);

            expect($filtered)->toBeInstanceOf(DataCollection::class)
                ->and($filtered->count())->toBe(2)
                ->and($first->name)->toBe('John')
                ->and($last->name)->toBe('Bob');
        });

        it('maps items', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $names = $collection->map(fn(DataCollectionUserDTO $user): string => $user->name);

            expect($names)->toBe(['John', 'Jane']);
        });

        it('reduces items', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            $totalAge = $collection->reduce(
                /** @phpstan-ignore-next-line unknown */
                fn(int $carry, DataCollectionUserDTO $user): int => $carry + $user->age,
                0
            );

            expect($totalAge)->toBe(90);
        });

        it('gets first item', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $first = $collection->first();
            assert($first instanceof DataCollectionUserDTO);

            expect($first)->toBeInstanceOf(DataCollectionUserDTO::class)
                ->and($first->name)->toBe('John');
        });

        it('gets first item with callback', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $first = $collection->first(fn(DataCollectionUserDTO $user): bool => 30 > $user->age);
            assert($first instanceof DataCollectionUserDTO);

            expect($first)->toBeInstanceOf(DataCollectionUserDTO::class)
                ->and($first->name)->toBe('Jane');
        });

        it('gets last item', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $last = $collection->last();
            assert($last instanceof DataCollectionUserDTO);

            expect($last)->toBeInstanceOf(DataCollectionUserDTO::class)
                ->and($last->name)->toBe('Jane');
        });

        it('gets last item with callback', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $last = $collection->last(fn(DataCollectionUserDTO $user): bool => 30 <= $user->age);
            assert($last instanceof DataCollectionUserDTO);

            expect($last)->toBeInstanceOf(DataCollectionUserDTO::class)
                ->and($last->name)->toBe('Bob');
        });
    });

    describe('Conversion Methods', function(): void {
        it('converts to array', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $array = $collection->toArray();

            expect($array)->toBe([
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);
        });

        it('converts to JSON', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $json = $collection->toJson();

            expect($json)->toBe('[{"name":"John","age":30},{"name":"Jane","age":25}]');
        });

        it('implements JsonSerializable', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $serialized = $collection->jsonSerialize();

            expect($serialized)->toBe([
                ['name' => 'John', 'age' => 30],
            ]);
        });
    });

    describe('Utility Methods', function(): void {
        it('checks if empty', function(): void {
            $empty = DataCollection::forDto(DataCollectionUserDTO::class);
            $notEmpty = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            expect($empty->isEmpty())->toBeTrue()
                ->and($empty->isNotEmpty())->toBeFalse()
                ->and($notEmpty->isEmpty())->toBeFalse()
                ->and($notEmpty->isNotEmpty())->toBeTrue();
        });

        it('wraps existing collection', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $wrapped = DataCollection::wrapDto(DataCollectionUserDTO::class, $collection);

            expect($wrapped)->toBe($collection);
        });

        it('wraps array', function(): void {
            $wrapped = DataCollection::wrapDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            expect($wrapped)->toBeInstanceOf(DataCollection::class)
                ->and($wrapped->count())->toBe(1);
        });

        it('gets items', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $items = $collection->items();

            expect($items)->toBeArray()
                ->and(count($items))->toBe(2)
                ->and($items[0])->toBeInstanceOf(DataCollectionUserDTO::class);
        });
    });
});

