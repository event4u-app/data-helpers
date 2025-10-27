<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Benchmarks;

use event4u\DataHelpers\DataMapper;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use RuntimeException;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Benchmark comparing DataMapper with Symfony Serializer for Dto mapping.
 *
 * Scenario: Map nested JSON data to a Dto structure
 */
#[BeforeMethods('setUp')]
class DtoSerializationBench
{
    private string $nestedJson;
    /** @var array<string, mixed> */
    private array $nestedData;
    private Serializer $serializer;

    public function setUp(): void
    {
        // Nested JSON data (realistic e-commerce user profile)
        $json = json_encode([
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
                    'street' => '123 Main St',
                    'city' => 'New York',
                    'zipCode' => '10001',
                    'country' => 'USA',
                ],
            ],
            'orders' => [
                ['id' => 1, 'total' => 99.99, 'status' => 'completed'],
                ['id' => 2, 'total' => 149.99, 'status' => 'pending'],
                ['id' => 3, 'total' => 79.99, 'status' => 'completed'],
            ],
        ]);

        if (false === $json) {
            throw new RuntimeException('Failed to encode JSON');
        }

        $this->nestedJson = $json;

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($this->nestedJson, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Failed to decode JSON');
        }

        $this->nestedData = $decoded;

        // Setup Symfony Serializer
        $reflectionExtractor = new ReflectionExtractor();
        $phpDocExtractor = new PhpDocExtractor();
        $propertyInfoExtractor = new PropertyInfoExtractor(
            [$reflectionExtractor],
            [$phpDocExtractor, $reflectionExtractor],
            [$phpDocExtractor],
            [$reflectionExtractor],
            [$reflectionExtractor]
        );

        $this->serializer = new Serializer(
            [
                new ObjectNormalizer(null, null, null, $propertyInfoExtractor),
                new ArrayDenormalizer(),
            ],
            [new JsonEncoder()]
        );
    }

    /** Benchmark: DataMapper with template syntax (nested to flat) */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchDataMapperTemplate(): void
    {
        $template = [
            'firstName' => '{{ user.profile.firstName }}',
            'lastName' => '{{ user.profile.lastName }}',
            'age' => '{{ user.profile.age }}',
            'email' => '{{ user.contact.email }}',
            'phone' => '{{ user.contact.phone }}',
            'street' => '{{ user.address.street }}',
            'city' => '{{ user.address.city }}',
            'zipCode' => '{{ user.address.zipCode }}',
            'country' => '{{ user.address.country }}',
        ];

        DataMapper::source($this->nestedData)
            ->target([])
            ->template($template)
            ->map();
    }

    /** Benchmark: DataMapper with simple path mapping (nested to flat) */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchDataMapperSimplePaths(): void
    {
        $mapping = [
            'firstName' => 'user.profile.firstName',
            'lastName' => 'user.profile.lastName',
            'age' => 'user.profile.age',
            'email' => 'user.contact.email',
            'phone' => 'user.contact.phone',
            'street' => 'user.address.street',
            'city' => 'user.address.city',
            'zipCode' => 'user.address.zipCode',
            'country' => 'user.address.country',
        ];

        DataMapper::source($this->nestedData)
            ->target([])
            ->template($mapping)
            ->map();
    }

    /** Benchmark: Symfony Serializer from JSON (nested structure) */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchSymfonySerializerJson(): void
    {
        $this->serializer->deserialize(
            $this->nestedJson,
            UserDataDto::class,
            'json'
        );
    }

    /** Benchmark: Symfony Serializer from array (nested structure) */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchSymfonySerializerArray(): void
    {
        $this->serializer->denormalize(
            $this->nestedData,
            UserDataDto::class
        );
    }

    /** Benchmark: Manual mapping (baseline - nested to flat) */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchManualMapping(): void
    {
        $user = $this->nestedData['user'];
        assert(is_array($user));

        $profile = $user['profile'];
        assert(is_array($profile));

        $contact = $user['contact'];
        assert(is_array($contact));

        $address = $user['address'];
        assert(is_array($address));

        // Create Dto (result is used implicitly by benchmark framework)
        /** @phpstan-ignore new.resultUnused */
        new UserProfileDto(
            (string)$profile['firstName'],
            (string)$profile['lastName'],
            (int)$profile['age'],
            (string)$contact['email'],
            (string)$contact['phone'],
            (string)$address['street'],
            (string)$address['city'],
            (string)$address['zipCode'],
            (string)$address['country']
        );
    }
}

/**
 * Flat Dto for DataMapper and manual mapping
 */
class UserProfileDto
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public int $age,
        public string $email,
        public string $phone,
        public string $street,
        public string $city,
        public string $zipCode,
        public string $country
    ) {}
}

/**
 * Nested Dto structure for Symfony Serializer
 */
class UserDataDto
{
    public UserDto $user;
    /** @var OrderDto[] */
    public array $orders;
}

class UserDto
{
    public ProfileDto $profile;
    public ContactDto $contact;
    public AddressDto $address;
}

class ProfileDto
{
    public string $firstName;
    public string $lastName;
    public int $age;
}

class ContactDto
{
    public string $email;
    public string $phone;
}

class AddressDto
{
    public string $street;
    public string $city;
    public string $zipCode;
    public string $country;
}

class OrderDto
{
    public int $id;
    public float $total;
    public string $status;
}
