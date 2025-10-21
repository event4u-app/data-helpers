<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\DataCollection;
use Tests\Unit\SimpleDTO\Fixtures\UserDTO;

describe('Collection Mixed Scenarios & Edge Cases', function(): void {
    describe('Collections with Validation', function(): void {
        it('validates collection items with Min/Max', function(): void {
            $users = DataCollection::forDto(UserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $first = $users->first();
            assert($first instanceof UserDTO);

            expect($users->count())->toBe(2)
                ->and($first->name)->toBe('John')
                ->and($first->age)->toBe(30);
        });

        it('handles empty collections', function(): void {
            $users = DataCollection::forDto(UserDTO::class, []);

            expect($users->count())->toBe(0)
                ->and($users->isEmpty())->toBeTrue()
                ->and($users->isNotEmpty())->toBeFalse();
        });

        it('handles single item collections', function(): void {
            $users = DataCollection::forDto(UserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            $first = $users->first();
            assert($first instanceof UserDTO);

            expect($users->count())->toBe(1)
                ->and($first)->toBe($users->last())
                ->and($first->name)->toBe('John');
        });
    });

    describe('Collection toArray() Scenarios', function(): void {
        it('converts collection to array correctly', function(): void {
            $users = DataCollection::forDto(UserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $array = $users->toArray();

            expect($array)->toBeArray()
                ->and($array)->toHaveCount(2)
                ->and($array[0])->toBe(['name' => 'John', 'age' => 30])
                ->and($array[1])->toBe(['name' => 'Jane', 'age' => 25]);
        });
    });

    describe('Collection Iteration', function(): void {
        it('iterates over collection items', function(): void {
            $users = DataCollection::forDto(UserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            $names = [];
            /** @var UserDTO $user */
            foreach ($users as $user) {
                $names[] = $user->name;
            }

            expect($names)->toBe(['John', 'Jane', 'Bob']);
        });

        it('maps over collection items', function(): void {
            $users = DataCollection::forDto(UserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $names = $users->map(fn(UserDTO $user): string => $user->name);

            expect($names)->toBe(['John', 'Jane']);
        });

        it('filters collection items', function(): void {
            $users = DataCollection::forDto(UserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $filtered = $users->filter(fn(UserDTO $user): bool => 28 < $user->age);

            $first = $filtered->first();
            $last = $filtered->last();
            assert($first instanceof UserDTO);
            assert($last instanceof UserDTO);

            expect($filtered->count())->toBe(2)
                ->and($first->name)->toBe('John')
                ->and($last->name)->toBe('Bob');
        });
    });

    describe('Collection with Null Values', function(): void {
        it('handles null collection property', function(): void {
            $dto = new class extends SimpleDTO {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    public readonly string $name = '',
                    public readonly ?DataCollection $users = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['users' => 'collection:' . UserDTO::class];
                }
            };

            $instance = $dto::fromArray([
                'name' => 'Test',
                'users' => null,
            ]);

            expect($instance->name)->toBe('Test')
                ->and($instance->users)->toBeNull();
        });

        it('handles missing collection property', function(): void {
            $dto = new class extends SimpleDTO {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    public readonly string $name = '',
                    public readonly ?DataCollection $users = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['users' => 'collection:' . UserDTO::class];
                }
            };

            $instance = $dto::fromArray([
                'name' => 'Test',
            ]);

            expect($instance->name)->toBe('Test')
                ->and($instance->users)->toBeNull();
        });
    });

    describe('Collection JSON Serialization', function(): void {
        it('serializes collection to JSON', function(): void {
            $users = DataCollection::forDto(UserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $json = json_encode($users);
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded)->toBeArray()
                ->and($decoded)->toHaveCount(2)
                ->and($decoded[0]['name'])->toBe('John')
                ->and($decoded[1]['name'])->toBe('Jane');
        });

        it('serializes DTO with collection to JSON', function(): void {
            $dto = new class extends SimpleDTO {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    public readonly string $name = '',
                    public readonly ?DataCollection $users = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['users' => 'collection:' . UserDTO::class];
                }
            };

            $instance = $dto::fromArray([
                'name' => 'Team',
                'users' => [
                    ['name' => 'John', 'age' => 30],
                    ['name' => 'Jane', 'age' => 25],
                ],
            ]);

            $json = json_encode($instance);
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded['name'])->toBe('Team')
                ->and($decoded['users'])->toBeArray()
                ->and($decoded['users'])->toHaveCount(2);
        });
    });

    describe('Collection Count and Size', function(): void {
        it('counts collection items correctly', function(): void {
            $users = DataCollection::forDto(UserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            expect($users->count())->toBe(3)
                ->and(count($users))->toBe(3);
        });

        it('checks if collection is empty', function(): void {
            $empty = DataCollection::forDto(UserDTO::class, []);
            $notEmpty = DataCollection::forDto(UserDTO::class, [
                ['name' => 'John', 'age' => 30],
            ]);

            expect($empty->isEmpty())->toBeTrue()
                ->and($empty->isNotEmpty())->toBeFalse()
                ->and($notEmpty->isEmpty())->toBeFalse()
                ->and($notEmpty->isNotEmpty())->toBeTrue();
        });
    });

    describe('Collection First and Last', function(): void {
        it('gets first and last items', function(): void {
            $users = DataCollection::forDto(UserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            $first = $users->first();
            $last = $users->last();
            assert($first instanceof UserDTO);
            assert($last instanceof UserDTO);

            expect($first->name)->toBe('John')
                ->and($last->name)->toBe('Bob');
        });

        it('returns null for first/last on empty collection', function(): void {
            $users = DataCollection::forDto(UserDTO::class, []);

            expect($users->first())->toBeNull()
                ->and($users->last())->toBeNull();
        });
    });

    describe('Collection Find', function(): void {
        it('finds item by callback', function(): void {
            $users = DataCollection::forDto(UserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $found = $users->first(fn(UserDTO $user): bool => 28 < $user->age);

            assert($found instanceof UserDTO);
            expect($found)->not->toBeNull()
                ->and($found->name)->toBe('John');
        });

        it('returns null when item not found', function(): void {
            $users = DataCollection::forDto(UserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            /** @phpstan-ignore-next-line unknown */
            $found = $users->first(fn(UserDTO $user): bool => 50 < $user->age);

            expect($found)->toBeNull();
        });
    });
});

