<?php

declare(strict_types=1);

use Doctrine\Common\Collections\ArrayCollection;
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\DataCollectionOf;
use Illuminate\Support\Collection;
use Tests\Unit\SimpleDTO\Fixtures\UserDTO;

describe('CollectionCast', function(): void {
    describe('Laravel Collection', function(): void {
        it('casts array to Laravel Collection', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly Collection $tags = new Collection(),
                ) {}

                protected function casts(): array
                {
                    return ['tags' => 'collection'];
                }
            };

            $instance = $dto::fromArray(['tags' => ['x', 'y', 'z']]);

            expect($instance->tags)->toBeInstanceOf(Collection::class)
                ->and($instance->tags->toArray())->toBe(['x', 'y', 'z'])
                ->and($instance->tags->count())->toBe(3);
        });

        it('keeps existing Laravel Collection', function(): void {
            $collection = new Collection(['a', 'b', 'c']);

            $dto = new class($collection) extends SimpleDTO {
                public function __construct(
                    public readonly Collection $tags,
                ) {}

                protected function casts(): array
                {
                    return ['tags' => 'collection'];
                }
            };

            $instance = $dto::fromArray(['tags' => $collection]);

            expect($instance->tags)->toBe($collection)
                ->and($instance->tags->toArray())->toBe(['a', 'b', 'c']);
        });

        it('handles null values', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?Collection $tags = null,
                ) {}

                protected function casts(): array
                {
                    return ['tags' => 'collection'];
                }
            };

            $instance = $dto::fromArray(['tags' => null]);

            expect($instance->tags)->toBeNull();
        });

        it('converts collection to array in toArray()', function(): void {
            $dto = new class(new Collection(['a', 'b', 'c'])) extends SimpleDTO {
                public function __construct(
                    public readonly Collection $tags,
                ) {}

                protected function casts(): array
                {
                    return ['tags' => 'collection'];
                }
            };

            $array = $dto->toArray();

            expect($array['tags'])->toBeArray()
                ->and($array['tags'])->toBe(['a', 'b', 'c']);
        });
    });

    describe('Doctrine Collection', function(): void {
        it('casts array to Doctrine Collection', function(): void {
            $dto = new class(new ArrayCollection()) extends SimpleDTO {
                public function __construct(
                    public readonly ArrayCollection $tags,
                ) {}

                protected function casts(): array
                {
                    return ['tags' => 'collection:doctrine'];
                }
            };

            $instance = $dto::fromArray(['tags' => ['x', 'y', 'z']]);

            expect($instance->tags)->toBeInstanceOf(ArrayCollection::class)
                ->and($instance->tags->toArray())->toBe(['x', 'y', 'z'])
                ->and($instance->tags->count())->toBe(3);
        });

        it('converts Doctrine collection to array in toArray()', function(): void {
            $dto = new class(new ArrayCollection(['a', 'b', 'c'])) extends SimpleDTO {
                public function __construct(
                    public readonly ArrayCollection $tags,
                ) {}

                protected function casts(): array
                {
                    return ['tags' => 'collection:doctrine'];
                }
            };

            $array = $dto->toArray();

            expect($array['tags'])->toBeArray()
                ->and($array['tags'])->toBe(['a', 'b', 'c']);
        });
    });

    describe('Typed Collections (DTOs)', function(): void {
        it('casts array of arrays to Collection of DTOs', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly Collection $users = new Collection(),
                ) {}

                protected function casts(): array
                {
                    return ['users' => 'collection:laravel,' . UserDTO::class];
                }
            };

            $instance = $dto::fromArray([
                'users' => [
                    ['name' => 'John', 'age' => 30],
                    ['name' => 'Jane', 'age' => 25],
                ],
            ]);

            expect($instance->users)->toBeInstanceOf(Collection::class)
                ->and($instance->users->count())->toBe(2)
                ->and($instance->users->first())->toBeInstanceOf(UserDTO::class)
                ->and($instance->users->first()->name)->toBe('John')
                ->and($instance->users->first()->age)->toBe(30)
                ->and($instance->users->last()->name)->toBe('Jane')
                ->and($instance->users->last()->age)->toBe(25);
        });

        it('converts Collection of DTOs to array in toArray()', function(): void {
            $userDTO = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $users = new Collection([
                $userDTO::fromArray(['name' => 'John', 'age' => 30]),
                $userDTO::fromArray(['name' => 'Jane', 'age' => 25]),
            ]);

            $dto = new class($users, $userDTO::class) extends SimpleDTO {
                public function __construct(
                    public readonly Collection $users,
                    private readonly string $userDTOClass,
                ) {}

                protected function casts(): array
                {
                    return ['users' => 'collection:laravel,' . $this->userDTOClass];
                }
            };

            $array = $dto->toArray();

            expect($array['users'])->toBeArray()
                ->and($array['users'])->toHaveCount(2)
                ->and($array['users'][0])->toBe(['name' => 'John', 'age' => 30])
                ->and($array['users'][1])->toBe(['name' => 'Jane', 'age' => 25]);
        });
    });

    describe('DataCollectionOf Attribute', function(): void {
        it('uses DataCollectionOf attribute for casting', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[DataCollectionOf(UserDTO::class)]
                    public readonly Collection $users = new Collection(),
                ) {}
            };

            $instance = $dto::fromArray([
                'users' => [
                    ['name' => 'Alice', 'age' => 28],
                    ['name' => 'Bob', 'age' => 32],
                ],
            ]);

            expect($instance->users)->toBeInstanceOf(Collection::class)
                ->and($instance->users->count())->toBe(2)
                ->and($instance->users->first())->toBeInstanceOf(UserDTO::class)
                ->and($instance->users->first()->name)->toBe('Alice')
                ->and($instance->users->last()->name)->toBe('Bob');
        });
    });
});


