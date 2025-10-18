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

            expect($collection)->toBeInstanceOf(DataCollection::class)
                ->and($collection->count())->toBe(2)
                ->and($collection->first())->toBeInstanceOf(DataCollectionUserDTO::class)
                ->and($collection->first()->name)->toBe('John');
        });

        it('creates collection from DTOs', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                new DataCollectionUserDTO('John', 30),
                new DataCollectionUserDTO('Jane', 25),
            ]);

            expect($collection->count())->toBe(2)
                ->and($collection->first()->name)->toBe('John');
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
            expect(fn() => DataCollection::forDto(DataCollectionUserDTO::class, [
                'invalid',
            ]))->toThrow(InvalidArgumentException::class);
        });

        it('ensures DTOs when pushing', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class);

            $collection->push(['name' => 'John', 'age' => 30]);

            expect($collection->first())->toBeInstanceOf(DataCollectionUserDTO::class)
                ->and($collection->first()->name)->toBe('John');
        });

        it('ensures DTOs when prepending', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'Jane', 'age' => 25],
            ]);

            $collection->prepend(['name' => 'John', 'age' => 30]);

            expect($collection->first())->toBeInstanceOf(DataCollectionUserDTO::class)
                ->and($collection->first()->name)->toBe('John');
        });

        it('supports array access', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            expect($collection[0])->toBeInstanceOf(DataCollectionUserDTO::class)
                ->and($collection[0]->name)->toBe('John')
                ->and($collection[1]->name)->toBe('Jane')
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

            $filtered = $collection->filter(fn(DataCollectionUserDTO $user) => $user->age >= 30);

            expect($filtered)->toBeInstanceOf(DataCollection::class)
                ->and($filtered->count())->toBe(2)
                ->and($filtered->first()->name)->toBe('John')
                ->and($filtered->last()->name)->toBe('Bob');
        });

        it('maps items', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $names = $collection->map(fn(DataCollectionUserDTO $user) => $user->name);

            expect($names)->toBe(['John', 'Jane']);
        });

        it('reduces items', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            $totalAge = $collection->reduce(
                fn(int $carry, DataCollectionUserDTO $user) => $carry + $user->age,
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

            expect($first)->toBeInstanceOf(DataCollectionUserDTO::class)
                ->and($first->name)->toBe('John');
        });

        it('gets first item with callback', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $first = $collection->first(fn(DataCollectionUserDTO $user) => $user->age < 30);

            expect($first)->toBeInstanceOf(DataCollectionUserDTO::class)
                ->and($first->name)->toBe('Jane');
        });

        it('gets last item', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $last = $collection->last();

            expect($last)->toBeInstanceOf(DataCollectionUserDTO::class)
                ->and($last->name)->toBe('Jane');
        });

        it('gets last item with callback', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            $last = $collection->last(fn(DataCollectionUserDTO $user) => $user->age >= 30);

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

