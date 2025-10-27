<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\DataCollection;

class TestDataCollectionUserDto extends SimpleDto
{
    public function __construct(
        public string $name = '',
        public string $email = '',
        public int $age = 0,
    ) {
    }
}

describe('DataCollectionMakeTest', function(): void {
    describe('make() factory method', function(): void {
        it('creates a collection from array data', function(): void {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25],
            ];

            $collection = DataCollection::make($data, TestDataCollectionUserDto::class);

            expect($collection)->toBeInstanceOf(DataCollection::class);
            expect($collection->count())->toBe(2);
        });

        it('creates Dtos from array data', function(): void {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25],
            ];

            $collection = DataCollection::make($data, TestDataCollectionUserDto::class);

            $first = $collection->first();
            expect($first)->toBeInstanceOf(TestDataCollectionUserDto::class);
            expect($first)->not->toBeNull();
            if (null !== $first) {
                /** @phpstan-ignore-next-line property.notFound */
                expect($first->name)->toBe('John');
                /** @phpstan-ignore-next-line property.notFound */
                expect($first->age)->toBe(30);
            }
        });

        it('creates empty collection from empty array', function(): void {
            $collection = DataCollection::make([], TestDataCollectionUserDto::class);

            expect($collection)->toBeInstanceOf(DataCollection::class);
            expect($collection->count())->toBe(0);
            expect($collection->isEmpty())->toBeTrue();
        });

        it('is equivalent to forDto()', function(): void {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
            ];

            $collection1 = DataCollection::make($data, TestDataCollectionUserDto::class);
            $collection2 = DataCollection::forDto(TestDataCollectionUserDto::class, $data);

            expect($collection1->count())->toBe($collection2->count());
            $first1 = $collection1->first();
            $first2 = $collection2->first();
            expect($first1)->not->toBeNull();
            expect($first2)->not->toBeNull();
            if (null !== $first1 && null !== $first2) {
                /** @phpstan-ignore-next-line property.notFound */
                expect($first1->name)->toBe($first2->name);
            }
        });

        it('applies auto-casting to Dto properties', function(): void {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => '30'], // age as string
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => '25'], // age as string
            ];

            $collection = DataCollection::make($data, TestDataCollectionUserDto::class);

            $first = $collection->first();
            expect($first)->not->toBeNull();
            if (null !== $first) {
                /** @phpstan-ignore-next-line property.notFound */
                expect($first->age)->toBe(30);
                /** @phpstan-ignore-next-line property.notFound */
                expect($first->age)->toBeInt();
            }
        });

        it('works with filter()', function(): void {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25],
                ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => 35],
            ];

            $collection = DataCollection::make($data, TestDataCollectionUserDto::class);
            /** @phpstan-ignore-next-line property.notFound */
            $filtered = $collection->filter(fn($dto): bool => 30 <= $dto->age);

            expect($filtered->count())->toBe(2);
            $first = $filtered->first();
            expect($first)->not->toBeNull();
            if (null !== $first) {
                /** @phpstan-ignore-next-line property.notFound */
                expect($first->name)->toBe('John');
            }
        });

        it('works with map()', function(): void {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25],
            ];

            $collection = DataCollection::make($data, TestDataCollectionUserDto::class);
            /** @var array<string> $names */
            /** @phpstan-ignore-next-line property.notFound */
            $names = $collection->map(fn($dto): string => $dto->name);

            expect($names)->toBe(['John', 'Jane']);
        });

        it('works with iteration', function(): void {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25],
            ];

            $collection = DataCollection::make($data, TestDataCollectionUserDto::class);
            /** @var array<string> $names */
            $names = [];

            foreach ($collection as $dto) {
                /** @phpstan-ignore-next-line property.notFound */
                $names[] = $dto->name;
            }

            expect($names)->toBe(['John', 'Jane']);
        });

        it('works with toArray()', function(): void {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25],
            ];

            $collection = DataCollection::make($data, TestDataCollectionUserDto::class);
            $array = $collection->toArray();

            expect($array)->toBeArray();
            expect($array)->toHaveCount(2);
            expect($array[0])->toBe(['name' => 'John', 'email' => 'john@example.com', 'age' => 30]);
        });

        it('works with toJson()', function(): void {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
            ];

            $collection = DataCollection::make($data, TestDataCollectionUserDto::class);
            $json = $collection->toJson();

            expect($json)->toBeString();
            expect(json_decode($json, true))->toBe([
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
            ]);
        });

        it('handles associative arrays with string keys', function(): void {
            $data = [
                'user1' => ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
                'user2' => ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25],
            ];

            $collection = DataCollection::make($data, TestDataCollectionUserDto::class);

            expect($collection->count())->toBe(2);
            $first = $collection->first();
            expect($first)->not->toBeNull();
            if (null !== $first) {
                /** @phpstan-ignore-next-line property.notFound */
                expect($first->name)->toBe('John');
            }
        });

        it('throws exception for invalid item types', function(): void {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
                'invalid', // Not an array or Dto
            ];

            expect(fn(): DataCollection => DataCollection::make($data, TestDataCollectionUserDto::class))
                ->toThrow(InvalidArgumentException::class);
        });

        it('accepts already instantiated Dtos', function(): void {
            $dto1 = new TestDataCollectionUserDto('John', 'john@example.com', 30);
            $dto2 = new TestDataCollectionUserDto('Jane', 'jane@example.com', 25);

            $collection = DataCollection::make([$dto1, $dto2], TestDataCollectionUserDto::class);

            expect($collection->count())->toBe(2);
            expect($collection->first())->toBe($dto1);
        });

        it('mixes array data and Dto instances', function(): void {
            $dto1 = new TestDataCollectionUserDto('John', 'john@example.com', 30);
            $data2 = ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25];

            $collection = DataCollection::make([$dto1, $data2], TestDataCollectionUserDto::class);

            expect($collection->count())->toBe(2);
            expect($collection->first())->toBe($dto1);

            $last = $collection->last();
            expect($last)->not->toBeNull();
            if (null !== $last) {
                /** @phpstan-ignore-next-line property.notFound */
                expect($last->name)->toBe('Jane');
            }
        });
    });

    describe('Parameter order', function(): void {
        it('has items as first parameter', function(): void {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
            ];

            // make($items, $dtoClass)
            $collection = DataCollection::make($data, TestDataCollectionUserDto::class);

            expect($collection->count())->toBe(1);
        });

        it('differs from forDto() parameter order', function(): void {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
            ];

            // forDto($dtoClass, $items)
            $collection1 = DataCollection::forDto(TestDataCollectionUserDto::class, $data);

            // make($items, $dtoClass)
            $collection2 = DataCollection::make($data, TestDataCollectionUserDto::class);

            expect($collection1->count())->toBe($collection2->count());
        });
    });

    describe('Edge cases', function(): void {
        it('handles large collections', function(): void {
            $data = [];
            for ($i = 0; 1000 > $i; $i++) {
                $data[] = ['name' => 'User' . $i, 'email' => sprintf('user%d@example.com', $i), 'age' => $i % 100];
            }

            $collection = DataCollection::make($data, TestDataCollectionUserDto::class);

            expect($collection->count())->toBe(1000);
        });

        it('handles nested data structures', function(): void {
            /**
             * @property string $name
             * @property array<string, mixed> $metadata
             */
            $nestedDtoClass = new class ('', []) extends SimpleDto {
                /** @param array<string, mixed> $metadata */
                public function __construct(
                    public string $name = '',
                    public array $metadata = [],
                ) {
                }
            };

            $data = [
                ['name' => 'John', 'metadata' => ['role' => 'admin', 'active' => true]],
            ];

            $collection = DataCollection::make($data, $nestedDtoClass::class);

            $first = $collection->first();
            expect($first)->not->toBeNull();
            if (null !== $first) {
                /** @phpstan-ignore-next-line property.notFound */
                expect($first->metadata)->toBe(['role' => 'admin', 'active' => true]);
            }
        });
    });
});
