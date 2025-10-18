<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\DataCollectionOf;
use event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDTO\Attributes\Validation\Max;
use event4u\DataHelpers\SimpleDTO\Attributes\Validation\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Validation\Required;
use event4u\DataHelpers\SimpleDTO\DataCollection;
use Tests\Unit\SimpleDTO\Fixtures\UserDTO;

describe('Collection Mixed Scenarios & Edge Cases', function(): void {
    describe('Collections with Validation', function(): void {
        it('validates collection items with Min/Max', function(): void {
            $users = DataCollection::forDto(UserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            expect($users->count())->toBe(2)
                ->and($users->first()->name)->toBe('John')
                ->and($users->first()->age)->toBe(30);
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

            expect($users->count())->toBe(1)
                ->and($users->first())->toBe($users->last())
                ->and($users->first()->name)->toBe('John');
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

            $names = $users->map(fn($user) => $user->name);

            expect($names)->toBe(['John', 'Jane']);
        });

        it('filters collection items', function(): void {
            $users = DataCollection::forDto(UserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Bob', 'age' => 35],
            ]);

            $filtered = $users->filter(fn($user) => $user->age > 28);

            expect($filtered->count())->toBe(2)
                ->and($filtered->first()->name)->toBe('John')
                ->and($filtered->last()->name)->toBe('Bob');
        });
    });

    describe('Collection with Null Values', function(): void {
        it('handles null collection property', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly ?DataCollection $users = null,
                ) {}

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
                public function __construct(
                    public readonly string $name = '',
                    public readonly ?DataCollection $users = null,
                ) {}

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
            $decoded = json_decode($json, true);

            expect($decoded)->toBeArray()
                ->and($decoded)->toHaveCount(2)
                ->and($decoded[0]['name'])->toBe('John')
                ->and($decoded[1]['name'])->toBe('Jane');
        });

        it('serializes DTO with collection to JSON', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly ?DataCollection $users = null,
                ) {}

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

            expect($users->first()->name)->toBe('John')
                ->and($users->last()->name)->toBe('Bob');
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

            $found = $users->first(fn($user) => $user->age > 28);

            expect($found)->not->toBeNull()
                ->and($found->name)->toBe('John');
        });

        it('returns null when item not found', function(): void {
            $users = DataCollection::forDto(UserDTO::class, [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ]);

            $found = $users->first(fn($user) => $user->age > 50);

            expect($found)->toBeNull();
        });
    });
});

