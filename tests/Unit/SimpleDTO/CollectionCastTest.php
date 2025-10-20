<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\DataCollectionOf;
use event4u\DataHelpers\SimpleDTO\DataCollection;
use Tests\Unit\SimpleDTO\Fixtures\UserDTO;

describe('CollectionCast', function(): void {
    describe('DataCollection', function(): void {
        it('throws exception when no DTO class is specified', function(): void {
            $dto = new class extends SimpleDTO {
                /** @phpstan-ignore-next-line missingType.generics (DataCollection type inference) */
                public function __construct(
                    public readonly ?DataCollection $tags = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['tags' => 'collection'];
                }
            };

            $dto::fromArray(['tags' => ['x', 'y', 'z']]);
        })->throws(RuntimeException::class, 'CollectionCast requires a DTO class');

        it('casts array to DataCollection with DTO class', function(): void {
            $dto = new class extends SimpleDTO {
                /** @phpstan-ignore-next-line missingType.generics (DataCollection type inference) */
                public function __construct(
                    public readonly ?DataCollection $users = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['users' => 'collection:' . UserDTO::class];
                }
            };

            $instance = $dto::fromArray([
                'users' => [
                    ['name' => 'John', 'age' => 30],
                    ['name' => 'Jane', 'age' => 25],
                ],
            ]);

            assert($instance->users instanceof DataCollection);
            $first = $instance->users->first();
            $last = $instance->users->last();
            assert($first instanceof UserDTO);
            assert($last instanceof UserDTO);

            expect($instance->users)->toBeInstanceOf(DataCollection::class)
                ->and($instance->users->count())->toBe(2)
                ->and($first)->toBeInstanceOf(UserDTO::class)
                ->and($first->name)->toBe('John')
                ->and($first->age)->toBe(30)
                ->and($last->name)->toBe('Jane')
                ->and($last->age)->toBe(25);
        });

        it('keeps existing DataCollection', function(): void {
            $collection = DataCollection::forDto(UserDTO::class, [
                ['name' => 'Alice', 'age' => 28],
                ['name' => 'Bob', 'age' => 32],
            ]);

            $dto = new class($collection) extends SimpleDTO {
                /** @phpstan-ignore-next-line missingType.generics (DataCollection type inference) */
                public function __construct(
                    public readonly DataCollection $users,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['users' => 'collection:' . UserDTO::class];
                }
            };

            $instance = $dto::fromArray(['users' => $collection]);

            expect($instance->users)->toBe($collection)
                ->and($instance->users->count())->toBe(2);
        });

        it('handles null values', function(): void {
            $dto = new class extends SimpleDTO {
                /** @phpstan-ignore-next-line missingType.generics (DataCollection type inference) */
                public function __construct(
                    public readonly ?DataCollection $users = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['users' => 'collection:' . UserDTO::class];
                }
            };

            $instance = $dto::fromArray(['users' => null]);

            expect($instance->users)->toBeNull();
        });

        it('converts DataCollection to array in toArray()', function(): void {
            $collection = DataCollection::forDto(UserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $dto = new class($collection) extends SimpleDTO {
                /** @phpstan-ignore-next-line missingType.generics (DataCollection type inference) */
                public function __construct(
                    public readonly DataCollection $users,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['users' => 'collection:' . UserDTO::class];
                }
            };

            $array = $dto->toArray();

            expect($array['users'])->toBeArray()
                ->and($array['users'])->toHaveCount(2)
                ->and($array['users'][0])->toBe(['name' => 'John', 'age' => 30])
                ->and($array['users'][1])->toBe(['name' => 'Jane', 'age' => 25]);
        });
    });

    describe('Edge Cases', function(): void {
        it('handles single item array', function(): void {
            $dto = new class extends SimpleDTO {
                /** @phpstan-ignore-next-line missingType.generics (DataCollection type inference) */
                public function __construct(
                    public readonly ?DataCollection $users = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['users' => 'collection:' . UserDTO::class];
                }
            };

            $instance = $dto::fromArray([
                'users' => [
                    ['name' => 'John', 'age' => 30],
                ],
            ]);

            assert($instance->users instanceof DataCollection);
            $first = $instance->users->first();
            assert($first instanceof UserDTO);

            expect($instance->users)->toBeInstanceOf(DataCollection::class)
                ->and($instance->users->count())->toBe(1)
                ->and($first)->toBeInstanceOf(UserDTO::class)
                ->and($first->name)->toBe('John');
        });

        it('handles empty array', function(): void {
            $dto = new class extends SimpleDTO {
                /** @phpstan-ignore-next-line missingType.generics (DataCollection type inference) */
                public function __construct(
                    public readonly ?DataCollection $users = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['users' => 'collection:' . UserDTO::class];
                }
            };

            $instance = $dto::fromArray(['users' => []]);

            assert($instance->users instanceof DataCollection);
            expect($instance->users)->toBeInstanceOf(DataCollection::class)
                ->and($instance->users->count())->toBe(0);
        });
    });

    describe('DataCollectionOf Attribute', function(): void {
        it('uses DataCollectionOf attribute for casting', function(): void {
            $dto = new class extends SimpleDTO {
                /** @phpstan-ignore-next-line missingType.generics (DataCollection type inference) */
                public function __construct(
                    #[DataCollectionOf(UserDTO::class)]
                    public readonly ?DataCollection $users = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['users' => 'collection:' . UserDTO::class];
                }
            };

            $instance = $dto::fromArray([
                'users' => [
                    ['name' => 'Alice', 'age' => 28],
                    ['name' => 'Bob', 'age' => 32],
                ],
            ]);

            assert($instance->users instanceof DataCollection);
            $first = $instance->users->first();
            $last = $instance->users->last();
            assert($first instanceof UserDTO);
            assert($last instanceof UserDTO);

            expect($instance->users)->toBeInstanceOf(DataCollection::class)
                ->and($instance->users->count())->toBe(2)
                ->and($first)->toBeInstanceOf(UserDTO::class)
                ->and($first->name)->toBe('Alice')
                ->and($last->name)->toBe('Bob');
        });
    });
});

