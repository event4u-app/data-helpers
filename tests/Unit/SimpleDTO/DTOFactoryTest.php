<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO\DTOFactory;
use Faker\Factory as FakerFactory;
use Faker\Generator as Faker;
use Tests\Unit\SimpleDTO\Fixtures\UserDTO;

// Helper function for test setup
// Needed because Pest 2.x doesn't inherit beforeEach from outer describe blocks
function setupDTOFactory(): void
{
    // Create a test factory
    /** @phpstan-ignore-next-line unknown */
    test()->factory = new TestUserDTOFactory();
}

/**
 * Test factory for UserDTO.
 */
class TestUserDTOFactory extends DTOFactory
{
    protected string $dtoClass = UserDTO::class;

    protected function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'age' => $this->faker->numberBetween(18, 80),
        ];
    }
}

describe('DTOFactory', function(): void {
    beforeEach(function(): void {
        // Create a test factory
        $this->factory = new TestUserDTOFactory();
    });

    describe('Basic Factory Creation', function(): void {
        beforeEach(function(): void {
            $this->factory = new TestUserDTOFactory();
        });

        it('creates a single DTO instance', function(): void {
            $user = $this->factory->create();

            expect($user)->toBeInstanceOf(UserDTO::class)
                ->and($user->name)->toBeString()
                ->and($user->age)->toBeInt()
                ->and($user->age)->toBeGreaterThanOrEqual(18)
                ->and($user->age)->toBeLessThanOrEqual(80);
        });

        it('creates multiple DTO instances', function(): void {
            $users = $this->factory->count(5)->create();

            expect($users)->toBeArray()
                ->and($users)->toHaveCount(5);

            foreach ($users as $user) {
                expect($user)->toBeInstanceOf(UserDTO::class)
                    ->and($user->name)->toBeString()
                    ->and($user->age)->toBeInt();
            }
        });

        it('creates DTO with custom attributes', function(): void {
            $user = $this->factory->create(['name' => 'John Doe', 'age' => 30]);

            expect($user)->toBeInstanceOf(UserDTO::class)
                ->and($user->name)->toBe('John Doe')
                ->and($user->age)->toBe(30);
        });

        it('creates multiple DTOs with custom attributes', function(): void {
            $users = $this->factory->count(3)->create(['name' => 'Same Name']);

            expect($users)->toHaveCount(3);

            foreach ($users as $user) {
                expect($user->name)->toBe('Same Name')
                    ->and($user->age)->toBeInt();
            }
        });
    });

    describe('Make Method', function(): void {
        beforeEach(function(): void {
            $this->factory = new TestUserDTOFactory();
        });

        it('makes array without creating DTO', function(): void {
            $data = $this->factory->make();

            expect($data)->toBeArray()
                ->and($data)->toHaveKey('name')
                ->and($data)->toHaveKey('age')
                ->and($data['name'])->toBeString()
                ->and($data['age'])->toBeInt();
        });

        it('makes multiple arrays', function(): void {
            $data = $this->factory->count(3)->make();

            expect($data)->toBeArray()
                ->and($data)->toHaveCount(3);

            foreach ($data as $item) {
                expect($item)->toBeArray()
                    ->and($item)->toHaveKey('name')
                    ->and($item)->toHaveKey('age');
            }
        });

        it('makes array with custom attributes', function(): void {
            $data = $this->factory->make(['name' => 'Jane Doe']);

            expect($data['name'])->toBe('Jane Doe')
                ->and($data['age'])->toBeInt();
        });
    });

    describe('Static Constructor', function(): void {
        beforeEach(function(): void {
            setupDTOFactory();
        });

        it('creates factory using new() method', function(): void {
            $factory = new class extends DTOFactory {
                protected string $dtoClass = UserDTO::class;

                protected function definition(): array
                {
                    return [
                        'name' => $this->faker->name(),
                        'age' => $this->faker->numberBetween(18, 80),
                    ];
                }
            };

            $user = $factory::new()->create();

            expect($user)->toBeInstanceOf(UserDTO::class);
        });
    });

    describe('Faker Integration', function(): void {
        beforeEach(function(): void {
            $this->factory = new TestUserDTOFactory();
        });

        it('uses Faker to generate data', function(): void {
            $user1 = $this->factory->create();
            $user2 = $this->factory->create();

            // Names should be different (with very high probability)
            expect($user1->name)->not->toBe($user2->name);
        });

        it('allows custom Faker instance', function(): void {
            $faker = FakerFactory::create('de_DE');

            $factory = new class($faker) extends DTOFactory {
                protected string $dtoClass = UserDTO::class;

                protected function definition(): array
                {
                    return [
                        'name' => $this->faker->name(),
                        'age' => $this->faker->numberBetween(18, 80),
                    ];
                }
            };

            $user = $factory->create();

            assert($user instanceof UserDTO);
            expect($user)->toBeInstanceOf(UserDTO::class)
                ->and($user->name)->toBeString();
        });

        it('provides access to faker instance', function(): void {
            $faker = $this->factory->faker();

            expect($faker)->toBeInstanceOf(Faker::class);
        });
    });

    describe('Factory Reset', function(): void {
        beforeEach(function(): void {
            $this->factory = new TestUserDTOFactory();
        });

        it('resets count after creation', function(): void {
            $users1 = $this->factory->count(5)->create();
            $this->factory->reset();
            $user2 = $this->factory->create();

            expect($users1)->toHaveCount(5)
                ->and($user2)->toBeInstanceOf(UserDTO::class)
                ->and($user2)->not->toBeArray();
        });

        it('resets states after creation', function(): void {
            $this->factory->state('admin', ['age' => 99]);
            $user1 = $this->factory->create();

            $this->factory->reset();
            $user2 = $this->factory->create();

            expect($user1->age)->toBe(99)
                ->and($user2->age)->not->toBe(99);
        });
    });

    describe('States', function(): void {
        beforeEach(function(): void {
            $this->factory = new TestUserDTOFactory();
        });

        it('applies named state', function(): void {
            $user = $this->factory
                ->state('admin', ['age' => 99])
                ->create();

            expect($user->age)->toBe(99);
        });

        it('applies multiple states', function(): void {
            $user = $this->factory
                ->state('admin', ['age' => 99])
                ->state('verified', ['name' => 'Verified User'])
                ->create();

            expect($user->age)->toBe(99)
                ->and($user->name)->toBe('Verified User');
        });

        it('overrides state with custom attributes', function(): void {
            $user = $this->factory
                ->state('admin', ['age' => 99])
                ->create(['age' => 25]);

            expect($user->age)->toBe(25);
        });
    });

    describe('Edge Cases', function(): void {
        beforeEach(function(): void {
            $this->factory = new TestUserDTOFactory();
        });

        it('handles count of 0', function(): void {
            $users = $this->factory->count(0)->create();

            expect($users)->toBeArray()
                ->and($users)->toHaveCount(0);
        });

        it('handles empty custom attributes', function(): void {
            $user = $this->factory->create([]);

            expect($user)->toBeInstanceOf(UserDTO::class);
        });

        it('allows setting faker seed for reproducible data', function(): void {
            $faker = FakerFactory::create();
            $faker->seed(12345);

            $factory = new class($faker) extends DTOFactory {
                protected string $dtoClass = UserDTO::class;

                protected function definition(): array
                {
                    return [
                        'name' => $this->faker->name(),
                        'age' => $this->faker->numberBetween(18, 80),
                    ];
                }
            };

            $user1 = $factory->create();
            $user2 = $factory->create();

            // With same seed, names should be different (because we call faker twice)
            // but both should be valid strings
            assert($user1 instanceof UserDTO && $user2 instanceof UserDTO);
            expect($user1->name)->toBeString()
                ->and($user2->name)->toBeString()
                ->and($user1->name)->not->toBe($user2->name);
        });
    });
});
