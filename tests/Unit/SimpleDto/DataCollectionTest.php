<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\DataCollection;

class DataCollectionUserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

describe('DataCollection', function(): void {
    describe('Creation', function(): void {
        it('creates collection from arrays', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $first = $collection->first();
            assert($first instanceof DataCollectionUserDto);

            expect($collection)->toBeInstanceOf(DataCollection::class)
                ->and($collection->count())->toBe(2)
                ->and($first)->toBeInstanceOf(DataCollectionUserDto::class)
                ->and($first->name)->toBe('John');
        });

        it('creates collection from Dtos', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class, [
                new DataCollectionUserDto('John', 30),
                new DataCollectionUserDto('Jane', 25),
            ]);

            $first = $collection->first();
            assert($first instanceof DataCollectionUserDto);

            expect($collection->count())->toBe(2)
                ->and($first->name)->toBe('John');
        });

        it('creates empty collection', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class);

            expect($collection->isEmpty())->toBeTrue()
                ->and($collection->count())->toBe(0);
        });

        it('creates collection via static collection method', function(): void {
            $collection = DataCollectionUserDto::collection([
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            expect($collection)->toBeInstanceOf(DataCollection::class)
                ->and($collection->count())->toBe(2)
                ->and($collection->getDtoClass())->toBe(DataCollectionUserDto::class);
        });
    });

    describe('Type Safety', function(): void {
        it('returns correct Dto class', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class);

            expect($collection->getDtoClass())->toBe(DataCollectionUserDto::class);
        });

        it('throws exception for invalid item type', function(): void {
            expect(fn(): DataCollection => DataCollection::forDto(DataCollectionUserDto::class, [
                'invalid',
            ]))->toThrow(InvalidArgumentException::class);
        });

        it('ensures Dtos when pushing', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class);

            $collection->push(['name' => 'John', 'age' => 30]);

            $first = $collection->first();
            assert($first instanceof DataCollectionUserDto);

            expect($first)->toBeInstanceOf(DataCollectionUserDto::class)
                ->and($first->name)->toBe('John');
        });

        it('ensures Dtos when prepending', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class, [
                ['name' => 'Jane', 'age' => 25],
            ]);

            $collection->prepend(['name' => 'John', 'age' => 30]);

            $first = $collection->first();
            assert($first instanceof DataCollectionUserDto);

            expect($first)->toBeInstanceOf(DataCollectionUserDto::class)
                ->and($first->name)->toBe('John');
        });

        it('supports array access', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $item0 = $collection[0];
            $item1 = $collection[1];
            assert($item0 instanceof DataCollectionUserDto);
            assert($item1 instanceof DataCollectionUserDto);

            expect($item0)->toBeInstanceOf(DataCollectionUserDto::class)
                ->and($item0->name)->toBe('John')
                ->and($item1->name)->toBe('Jane')
                ->and(isset($collection[0]))->toBeTrue()
                ->and(isset($collection[5]))->toBeFalse();
        });
    });

    describe('Collection Methods', function(): void {
        it('filters items', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $filtered = $collection->filter(fn(DataCollectionUserDto $user): bool => 30 <= $user->age);

            $first = $filtered->first();
            $last = $filtered->last();
            assert($first instanceof DataCollectionUserDto);
            assert($last instanceof DataCollectionUserDto);

            expect($filtered)->toBeInstanceOf(DataCollection::class)
                ->and($filtered->count())->toBe(2)
                ->and($first->name)->toBe('John')
                ->and($last->name)->toBe('Bob');
        });

        it('maps items', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $names = $collection->map(fn(DataCollectionUserDto $user): string => $user->name);

            expect($names)->toBe(['John', 'Jane']);
        });

        it('reduces items', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            $totalAge = $collection->reduce(
                /** @phpstan-ignore-next-line unknown */
                fn(int $carry, DataCollectionUserDto $user): int => $carry + $user->age,
                0
            );

            expect($totalAge)->toBe(90);
        });

        it('gets first item', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $first = $collection->first();
            assert($first instanceof DataCollectionUserDto);

            expect($first)->toBeInstanceOf(DataCollectionUserDto::class)
                ->and($first->name)->toBe('John');
        });

        it('gets first item with callback', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $first = $collection->first(fn(DataCollectionUserDto $user): bool => 30 > $user->age);
            assert($first instanceof DataCollectionUserDto);

            expect($first)->toBeInstanceOf(DataCollectionUserDto::class)
                ->and($first->name)->toBe('Jane');
        });

        it('gets last item', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $last = $collection->last();
            assert($last instanceof DataCollectionUserDto);

            expect($last)->toBeInstanceOf(DataCollectionUserDto::class)
                ->and($last->name)->toBe('Jane');
        });

        it('gets last item with callback', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $last = $collection->last(fn(DataCollectionUserDto $user): bool => 30 <= $user->age);
            assert($last instanceof DataCollectionUserDto);

            expect($last)->toBeInstanceOf(DataCollectionUserDto::class)
                ->and($last->name)->toBe('Bob');
        });
    });

    describe('Conversion Methods', function(): void {
        it('converts to array', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class, [
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
            $collection = DataCollection::forDto(DataCollectionUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $json = $collection->toJson();

            expect($json)->toBe('[{"name":"John","age":30},{"name":"Jane","age":25}]');
        });

        it('implements JsonSerializable', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class, [
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
            $empty = DataCollection::forDto(DataCollectionUserDto::class);
            $notEmpty = DataCollection::forDto(DataCollectionUserDto::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            expect($empty->isEmpty())->toBeTrue()
                ->and($empty->isNotEmpty())->toBeFalse()
                ->and($notEmpty->isEmpty())->toBeFalse()
                ->and($notEmpty->isNotEmpty())->toBeTrue();
        });

        it('wraps existing collection', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $wrapped = DataCollection::wrapDto(DataCollectionUserDto::class, $collection);

            expect($wrapped)->toBe($collection);
        });

        it('wraps array', function(): void {
            $wrapped = DataCollection::wrapDto(DataCollectionUserDto::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            expect($wrapped)->toBeInstanceOf(DataCollection::class)
                ->and($wrapped->count())->toBe(1);
        });

        it('gets items', function(): void {
            $collection = DataCollection::forDto(DataCollectionUserDto::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $items = $collection->items();

            expect($items)->toBeArray()
                ->and(count($items))->toBe(2)
                ->and($items[0])->toBeInstanceOf(DataCollectionUserDto::class);
        });
    });
});
