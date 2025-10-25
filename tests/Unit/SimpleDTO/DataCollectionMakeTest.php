<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\DataCollection;

describe('DataCollectionMakeTest', function () {
    beforeEach(function () {
        $this->userDtoClass = new class ('', '', 0) extends SimpleDTO {
            public function __construct(
                public string $name = '',
                public string $email = '',
                public int $age = 0,
            ) {
            }
        };
    });

    describe('make() factory method', function () {
        it('creates a collection from array data', function () {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25],
            ];

            $collection = DataCollection::make($data, $this->userDtoClass::class);

            expect($collection)->toBeInstanceOf(DataCollection::class);
            expect($collection->count())->toBe(2);
        });

        it('creates DTOs from array data', function () {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25],
            ];

            $collection = DataCollection::make($data, $this->userDtoClass::class);

            $first = $collection->first();
            expect($first)->toBeInstanceOf($this->userDtoClass::class);
            expect($first->name)->toBe('John');
            expect($first->age)->toBe(30);
        });

        it('creates empty collection from empty array', function () {
            $collection = DataCollection::make([], $this->userDtoClass::class);

            expect($collection)->toBeInstanceOf(DataCollection::class);
            expect($collection->count())->toBe(0);
            expect($collection->isEmpty())->toBeTrue();
        });

        it('is equivalent to forDto()', function () {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
            ];

            $collection1 = DataCollection::make($data, $this->userDtoClass::class);
            $collection2 = DataCollection::forDto($this->userDtoClass::class, $data);

            expect($collection1->count())->toBe($collection2->count());
            expect($collection1->first()->name)->toBe($collection2->first()->name);
        });

        it('applies auto-casting to DTO properties', function () {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => '30'], // age as string
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => '25'], // age as string
            ];

            $collection = DataCollection::make($data, $this->userDtoClass::class);

            $first = $collection->first();
            expect($first->age)->toBe(30);
            expect($first->age)->toBeInt();
        });

        it('works with filter()', function () {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25],
                ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => 35],
            ];

            $collection = DataCollection::make($data, $this->userDtoClass::class);
            $filtered = $collection->filter(fn($dto) => $dto->age >= 30);

            expect($filtered->count())->toBe(2);
            expect($filtered->first()->name)->toBe('John');
        });

        it('works with map()', function () {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25],
            ];

            $collection = DataCollection::make($data, $this->userDtoClass::class);
            $names = $collection->map(fn($dto) => $dto->name);

            expect($names)->toBe(['John', 'Jane']);
        });

        it('works with iteration', function () {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25],
            ];

            $collection = DataCollection::make($data, $this->userDtoClass::class);
            $names = [];

            foreach ($collection as $dto) {
                $names[] = $dto->name;
            }

            expect($names)->toBe(['John', 'Jane']);
        });

        it('works with toArray()', function () {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25],
            ];

            $collection = DataCollection::make($data, $this->userDtoClass::class);
            $array = $collection->toArray();

            expect($array)->toBeArray();
            expect($array)->toHaveCount(2);
            expect($array[0])->toBe(['name' => 'John', 'email' => 'john@example.com', 'age' => 30]);
        });

        it('works with toJson()', function () {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
            ];

            $collection = DataCollection::make($data, $this->userDtoClass::class);
            $json = $collection->toJson();

            expect($json)->toBeString();
            expect(json_decode($json, true))->toBe([
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
            ]);
        });

        it('handles associative arrays with string keys', function () {
            $data = [
                'user1' => ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
                'user2' => ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25],
            ];

            $collection = DataCollection::make($data, $this->userDtoClass::class);

            expect($collection->count())->toBe(2);
            expect($collection->first()->name)->toBe('John');
        });

        it('throws exception for invalid item types', function () {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
                'invalid', // Not an array or DTO
            ];

            expect(fn() => DataCollection::make($data, $this->userDtoClass::class))
                ->toThrow(InvalidArgumentException::class);
        });

        it('accepts already instantiated DTOs', function () {
            $dto1 = new ($this->userDtoClass)('John', 'john@example.com', 30);
            $dto2 = new ($this->userDtoClass)('Jane', 'jane@example.com', 25);

            $collection = DataCollection::make([$dto1, $dto2], $this->userDtoClass::class);

            expect($collection->count())->toBe(2);
            expect($collection->first())->toBe($dto1);
        });

        it('mixes array data and DTO instances', function () {
            $dto1 = new ($this->userDtoClass)('John', 'john@example.com', 30);
            $data2 = ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25];

            $collection = DataCollection::make([$dto1, $data2], $this->userDtoClass::class);

            expect($collection->count())->toBe(2);
            expect($collection->first())->toBe($dto1);
            expect($collection->last()->name)->toBe('Jane');
        });
    });

    describe('Parameter order', function () {
        it('has items as first parameter', function () {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
            ];

            // make($items, $dtoClass)
            $collection = DataCollection::make($data, $this->userDtoClass::class);

            expect($collection->count())->toBe(1);
        });

        it('differs from forDto() parameter order', function () {
            $data = [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
            ];

            // forDto($dtoClass, $items)
            $collection1 = DataCollection::forDto($this->userDtoClass::class, $data);

            // make($items, $dtoClass)
            $collection2 = DataCollection::make($data, $this->userDtoClass::class);

            expect($collection1->count())->toBe($collection2->count());
        });
    });

    describe('Edge cases', function () {
        it('handles large collections', function () {
            $data = [];
            for ($i = 0; $i < 1000; $i++) {
                $data[] = ['name' => "User$i", 'email' => "user$i@example.com", 'age' => $i % 100];
            }

            $collection = DataCollection::make($data, $this->userDtoClass::class);

            expect($collection->count())->toBe(1000);
        });

        it('handles nested data structures', function () {
            $nestedDtoClass = new class ('', []) extends SimpleDTO {
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

            expect($collection->first()->metadata)->toBe(['role' => 'admin', 'active' => true]);
        });
    });
});

