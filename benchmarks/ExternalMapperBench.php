<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Benchmarks;

use AutoMapperPlus\AutoMapper;
use AutoMapperPlus\Configuration\AutoMapperConfig;
use event4u\DataHelpers\DataMapper;
use Laminas\Hydrator\ReflectionHydrator;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;

#[BeforeMethods('setUp')]
class ExternalMapperBench
{
    /** @var array<string, mixed> */
    private array $sourceData;

    /** @var array<string, mixed> */
    private array $nestedSourceData;

    private AutoMapper $autoMapper;
    private ReflectionHydrator $laminasHydrator;

    public function setUp(): void
    {
        // Simple flat data
        $this->sourceData = [
            'firstName' => 'Alice',
            'lastName' => 'Smith',
            'email' => 'alice@example.com',
            'age' => 30,
            'city' => 'Berlin',
        ];

        // Nested data
        $this->nestedSourceData = [
            'user' => [
                'profile' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Smith',
                    'age' => 30,
                ],
                'contact' => [
                    'email' => 'alice@example.com',
                    'phone' => '+1234567890',
                ],
                'address' => [
                    'city' => 'Berlin',
                    'country' => 'Germany',
                ],
            ],
        ];

        // Setup AutoMapper Plus
        $config = new AutoMapperConfig();
        $config->registerMapping('array', MapperTargetDto::class);
        $this->autoMapper = new AutoMapper($config);

        // Setup Laminas Hydrator
        $this->laminasHydrator = new ReflectionHydrator();
    }

    /** Benchmark: Our DataMapper - Simple Mapping */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchDataMapperSimple(): void
    {
        $mapping = [
            'name' => 'firstName',
            'surname' => 'lastName',
            'mail' => 'email',
        ];

        DataMapper::source($this->sourceData)
            ->target([])
            ->template($mapping)
            ->map();
    }

    /** Benchmark: AutoMapper Plus - Simple Mapping */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchAutoMapperPlusSimple(): void
    {
        $this->autoMapper->map($this->sourceData, MapperTargetDto::class);
    }

    /** Benchmark: Laminas Hydrator - Simple Mapping */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchLaminasHydratorSimple(): void
    {
        $this->laminasHydrator->hydrate($this->sourceData, new MapperTargetDto());
    }

    /** Benchmark: Plain PHP - Simple Mapping */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchPlainPhpSimple(): void
    {
        $target = new MapperTargetDto();
        $target->firstName = $this->sourceData['firstName'];
        $target->lastName = $this->sourceData['lastName'];
        $target->email = $this->sourceData['email'];
    }

    /** Benchmark: Our DataMapper - Nested Mapping */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchDataMapperNested(): void
    {
        $mapping = [
            'firstName' => 'user.profile.firstName',
            'lastName' => 'user.profile.lastName',
            'age' => 'user.profile.age',
            'email' => 'user.contact.email',
            'phone' => 'user.contact.phone',
            'city' => 'user.address.city',
            'country' => 'user.address.country',
        ];

        DataMapper::source($this->nestedSourceData)
            ->target([])
            ->template($mapping)
            ->map();
    }

    /** Benchmark: Plain PHP - Nested Mapping */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchPlainPhpNested(): void
    {
        $user = $this->nestedSourceData['user'];
        assert(is_array($user));

        $profile = $user['profile'];
        assert(is_array($profile));

        $contact = $user['contact'];
        assert(is_array($contact));

        $address = $user['address'];
        assert(is_array($address));

        $target = new MapperTargetDto();
        $target->firstName = $profile['firstName'];
        $target->lastName = $profile['lastName'];
        $target->email = $contact['email'];
    }

    /** Benchmark: Our DataMapper - Template Syntax */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchDataMapperTemplate(): void
    {
        $template = [
            'firstName' => '{{ user.profile.firstName }}',
            'lastName' => '{{ user.profile.lastName }}',
            'email' => '{{ user.contact.email }}',
        ];

        DataMapper::source($this->nestedSourceData)
            ->target([])
            ->template($template)
            ->map();
    }

    /** Benchmark: Other parser library - Parse Data */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchOtherParserLibrary(): void
    {
        // Simulating other parser library behavior
        // Chubbyphp parsing is more about validation/parsing, not mapping
        $target = new MapperTargetDto();
        $target->firstName = $this->sourceData['firstName'] ?? null;
        $target->lastName = $this->sourceData['lastName'] ?? null;
        $target->email = $this->sourceData['email'] ?? null;
    }
}

/**
 * Target DTO for mapper benchmarks
 */
class MapperTargetDto
{
    public ?string $firstName = null;
    public ?string $lastName = null;
    public ?string $email = null;
    public ?int $age = null;
    public ?string $phone = null;
    public ?string $city = null;
    public ?string $country = null;
}

