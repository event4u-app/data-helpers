<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use Faker\Factory as FakerFactory;
use Faker\Generator as Faker;

/**
 * Base factory class for creating DTO instances with fake data.
 *
 * This class provides a convenient way to generate test data for DTOs
 * using the Faker library. It's inspired by Laravel's model factories
 * but adapted for DTOs.
 *
 * Example:
 *   class UserDTOFactory extends DTOFactory {
 *       protected string $dtoClass = UserDTO::class;
 *
 *       protected function definition(): array {
 *           return [
 *               'name' => $this->faker->name(),
 *               'email' => $this->faker->email(),
 *               'age' => $this->faker->numberBetween(18, 80),
 *           ];
 *       }
 *   }
 *
 *   // Create a single DTO
 *   $user = UserDTOFactory::new()->create();
 *
 *   // Create multiple DTOs
 *   $users = UserDTOFactory::new()->count(5)->create();
 *
 *   // Create with custom attributes
 *   $user = UserDTOFactory::new()->create(['name' => 'John Doe']);
 *
 *   // Make array without creating DTO
 *   $data = UserDTOFactory::new()->make();
 */
abstract class DTOFactory
{
    protected Faker $faker;
    protected int $count = 1;

    /** @var array<string, callable(): array<string, mixed>> */
    protected array $states = [];

    /**
     * The DTO class to create.
     *
     * @var class-string
     */
    protected string $dtoClass;

    /** Create a new factory instance. */
    public function __construct(?Faker $faker = null)
    {
        $this->faker = $faker ?? FakerFactory::create();
    }

    /** Create a new factory instance. */
    public static function new(?Faker $faker = null): static
    {
        return new static($faker);
    }

    /**
     * Define the DTO's default state.
     *
     * This method should return an array of attribute values that will be used
     * to create the DTO. You can use $this->faker to generate fake data.
     *
     * @return array<string, mixed>
     */
    abstract protected function definition(): array;

    /** Set the number of DTOs to create. */
    public function count(int $count): static
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Create a DTO instance or array of instances.
     *
     * @param array<string, mixed> $attributes Additional attributes to override
     * @return mixed Single DTO or array of DTOs
     */
    public function create(array $attributes = []): mixed
    {
        if (1 === $this->count) {
            return $this->createOne($attributes);
        }

        $dtos = [];
        for ($i = 0; $i < $this->count; $i++) {
            $dtos[] = $this->createOne($attributes);
        }

        return $dtos;
    }

    /**
     * Create a single DTO instance.
     *
     * @param array<string, mixed> $attributes
     */
    protected function createOne(array $attributes = []): mixed
    {
        $data = $this->makeOne($attributes);

        return $this->dtoClass::fromArray($data);
    }

    /**
     * Make an array of attributes without creating a DTO.
     *
     * @param array<string, mixed> $attributes Additional attributes to override
     * @return array<string, mixed>|array<int, array<string, mixed>>
     */
    public function make(array $attributes = []): array
    {
        if (1 === $this->count) {
            return $this->makeOne($attributes);
        }

        $data = [];
        for ($i = 0; $i < $this->count; $i++) {
            $data[] = $this->makeOne($attributes);
        }

        return $data;
    }

    /**
     * Make a single array of attributes.
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    protected function makeOne(array $attributes = []): array
    {
        $definition = $this->definition();

        // Apply states
        foreach ($this->states as $state) {
            $definition = array_merge($definition, $state);
        }

        // Override with custom attributes
        return array_merge($definition, $attributes);
    }

    /**
     * Apply a named state to the factory.
     *
     * @param string $state State name
     * @param array<string, mixed> $attributes State attributes
     */
    public function state(string $state, array $attributes = []): static
    {
        $this->states[$state] = $attributes;

        return $this;
    }

    /** Get the Faker instance. */
    public function faker(): Faker
    {
        return $this->faker;
    }

    /** Set a custom Faker instance. */
    public function withFaker(Faker $faker): static
    {
        $this->faker = $faker;

        return $this;
    }

    /** Reset the factory to its initial state. */
    public function reset(): static
    {
        $this->count = 1;
        $this->states = [];

        return $this;
    }
}

