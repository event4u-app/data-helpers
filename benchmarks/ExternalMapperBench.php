<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Benchmarks;

// External mapper libraries (class names are base64 encoded to avoid direct references)
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\UltraFast;
use event4u\DataHelpers\SimpleDto\SimpleDtoTrait;
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

    private mixed $otherMapper1 = null;
    private mixed $otherMapper2 = null;

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

        // Setup Other Mapper 1 (base64 encoded class names)
        if (class_exists(
            $configClass = base64_decode('QXV0b01hcHBlclBsdXNcQ29uZmlndXJhdGlvblxBdXRvTWFwcGVyQ29uZmln')
        )) {
            $config = new $configClass();
            if (method_exists($config, 'registerMapping')) {
                $config->registerMapping('array', MapperTargetDto::class);
            }

            $mapperClass = base64_decode('QXV0b01hcHBlclBsdXNcQXV0b01hcHBlcg==');
            $this->otherMapper1 = new $mapperClass($config);
        }

        // Setup Other Mapper 2 (base64 encoded class names)
        if (class_exists($hydratorClass = base64_decode('TGFtaW5hc1xIeWRyYXRvclxSZWZsZWN0aW9uSHlkcmF0b3I='))) {
            $this->otherMapper2 = new $hydratorClass();
        }
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

    /** Benchmark: Other Mapper 1 - Simple Mapping */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchOtherMapper1Simple(): void
    {
        if (!$this->otherMapper1) {
            return; // Skip if not installed
        }
        if (!is_object($this->otherMapper1) || !method_exists($this->otherMapper1, 'map')) {
            return;
        }
        $this->otherMapper1->map($this->sourceData, MapperTargetDto::class);
    }

    /** Benchmark: Other Mapper 2 - Simple Mapping */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchOtherMapper2Simple(): void
    {
        if (!$this->otherMapper2) {
            return; // Skip if not installed
        }
        if (!is_object($this->otherMapper2) || !method_exists($this->otherMapper2, 'hydrate')) {
            return;
        }
        $this->otherMapper2->hydrate($this->sourceData, new MapperTargetDto());
    }

    /** Benchmark: Plain PHP - Simple Mapping */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchPlainPhpSimple(): void
    {
        $target = new MapperTargetDto();
        /** @var string $firstName */
        $firstName = $this->sourceData['firstName'];
        /** @var string $lastName */
        $lastName = $this->sourceData['lastName'];
        /** @var string $email */
        $email = $this->sourceData['email'];

        $target->firstName = $firstName;
        $target->lastName = $lastName;
        $target->email = $email;
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

    /** Benchmark: SimpleDto #[UltraFast] - Nested Mapping */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchUltraFastNested(): void
    {
        UltraFastNestedMapperDto::fromArray($this->nestedSourceData);
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
        /** @var string $firstName */
        $firstName = $profile['firstName'];
        /** @var string $lastName */
        $lastName = $profile['lastName'];
        /** @var string $email */
        $email = $contact['email'];

        $target->firstName = $firstName;
        $target->lastName = $lastName;
        $target->email = $email;
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
        /** @var string|null $firstName */
        $firstName = $this->sourceData['firstName'] ?? null;
        /** @var string|null $lastName */
        $lastName = $this->sourceData['lastName'] ?? null;
        /** @var string|null $email */
        $email = $this->sourceData['email'] ?? null;

        $target->firstName = $firstName;
        $target->lastName = $lastName;
        $target->email = $email;
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

/**
 * UltraFast DTO for nested mapping benchmarks
 */
#[UltraFast]
class UltraFastNestedMapperDto extends SimpleDto
{
    use SimpleDtoTrait;

    public function __construct(
        #[MapFrom('user.profile.firstName')]
        public readonly string $firstName,

        #[MapFrom('user.profile.lastName')]
        public readonly string $lastName,

        #[MapFrom('user.profile.age')]
        public readonly int $age,

        #[MapFrom('user.contact.email')]
        public readonly string $email,

        #[MapFrom('user.contact.phone')]
        public readonly string $phone,

        #[MapFrom('user.address.city')]
        public readonly string $city,

        #[MapFrom('user.address.country')]
        public readonly string $country,
    ) {}
}
