<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasObject;
use event4u\DataHelpers\SimpleDto\SimpleDtoObjectTrait;
use event4u\DataHelpers\Traits\ObjectMappingTrait;

/**
 * Mock plain PHP class (like Zend Framework model).
 */
class Product
{
    public int $id = 0;
    public string $name = '';
    public float $price = 0.0;
    public ?string $description = null;
}

/**
 * Mock plain PHP class with getters/setters.
 */
class Customer
{
    private int $id = 0;
    private string $name = '';
    private string $email = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}

/**
 * Mock plain PHP class with ObjectMappingTrait and HasDto attribute.
 */
#[HasDto('ProductDto')]
class ProductWithDto
{
    use ObjectMappingTrait;

    public int $id = 0;
    public string $name = '';
    public float $price = 0.0;
}

describe('Plain Object Integration - SimpleDtoObjectTrait', function(): void {
    describe('fromObject()', function(): void {
        it('creates Dto from plain PHP object with public properties', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoObjectTrait;

                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                    public readonly float $price = 0.0,
                ) {}
            };

            $product = new Product();
            $product->id = 1;
            $product->name = 'Laptop';
            $product->price = 999.99;

            $instance = $dto::fromObject($product);

            expect($instance->id)->toBe(1);
            expect($instance->name)->toBe('Laptop');
            expect($instance->price)->toBe(999.99);
        });

        it('creates Dto from object with getters', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoObjectTrait;

                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                    public readonly string $email = '',
                ) {}
            };

            $customer = new Customer();
            $customer->setId(42);
            $customer->setName('John Doe');
            $customer->setEmail('john@example.com');

            $instance = $dto::fromObject($customer);

            expect($instance->id)->toBe(42);
            expect($instance->name)->toBe('John Doe');
            expect($instance->email)->toBe('john@example.com');
        });

        it('handles object with extra properties', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoObjectTrait;

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $product = new Product();
            $product->id = 1;
            $product->name = 'Laptop';
            $product->price = 999.99;

            $instance = $dto::fromObject($product);

            expect($instance->name)->toBe('Laptop');
        });

        it('handles object with missing optional properties', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoObjectTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly ?string $description = null,
                ) {}
            };

            $product = new Product();
            $product->name = 'Laptop';

            $instance = $dto::fromObject($product);

            expect($instance->name)->toBe('Laptop');
            expect($instance->description)->toBeNull();
        });
    });

    describe('toObject()', function(): void {
        it('creates plain PHP object from Dto', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoObjectTrait;

                public function __construct(
                    public readonly int $id = 1,
                    public readonly string $name = 'Laptop',
                    public readonly float $price = 999.99,
                ) {}
            };

            $instance = $dto::fromArray([]);
            $object = $instance->toObject(Product::class);

            expect($object)->toBeInstanceOf(Product::class);
            expect($object->id)->toBe(1); // @phpstan-ignore property.notFound
            expect($object->name)->toBe('Laptop'); // @phpstan-ignore property.notFound
            expect($object->price)->toBe(999.99); // @phpstan-ignore property.notFound
        });

        it('creates object with setters from Dto', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoObjectTrait;

                public function __construct(
                    public readonly int $id = 42,
                    public readonly string $name = 'John Doe',
                    public readonly string $email = 'john@example.com',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $object = $instance->toObject(Customer::class);

            expect($object)->toBeInstanceOf(Customer::class);
            expect($object->getId())->toBe(42); // @phpstan-ignore method.notFound
            expect($object->getName())->toBe('John Doe'); // @phpstan-ignore method.notFound
            expect($object->getEmail())->toBe('john@example.com'); // @phpstan-ignore method.notFound
        });

        it('throws exception if object class does not exist', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoObjectTrait;

                public function __construct(
                    public readonly string $name = 'Test',
                ) {}
            };

            $instance = $dto::fromArray([]);

            expect(fn(): object => $instance->toObject('NonExistentClass')) // @phpstan-ignore argument.type
                ->toThrow(InvalidArgumentException::class, 'Object class NonExistentClass does not exist');
        });
    });

    describe('HasObject attribute', function(): void {
        it('uses HasObject attribute to resolve object class', function(): void {
            $dto = new #[HasObject(Product::class)] class extends SimpleDto {
                use SimpleDtoObjectTrait;

                public function __construct(
                    public readonly int $id = 1,
                    public readonly string $name = 'Laptop',
                    public readonly float $price = 999.99,
                ) {}
            };

            $instance = $dto::fromArray([]);
            $object = $instance->toObject();

            expect($object)->toBeInstanceOf(Product::class);
            expect($object->id)->toBe(1); // @phpstan-ignore property.notFound
            expect($object->name)->toBe('Laptop'); // @phpstan-ignore property.notFound
            expect($object->price)->toBe(999.99); // @phpstan-ignore property.notFound
        });

        it('creates stdClass when no object class provided and no attribute', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoObjectTrait;

                public function __construct(
                    public readonly string $name = 'Test',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $object = $instance->toObject();

            expect($object)->toBeInstanceOf('stdClass');
            expect($object->name)->toBe('Test'); // @phpstan-ignore property.notFound
        });
    });

    describe('Round-trip (Object → Dto → Object)', function(): void {
        it('preserves data in round-trip', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoObjectTrait;

                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                    public readonly float $price = 0.0,
                ) {}
            };

            $originalProduct = new Product();
            $originalProduct->id = 1;
            $originalProduct->name = 'Laptop';
            $originalProduct->price = 999.99;

            $dtoInstance = $dto::fromObject($originalProduct);
            $newProduct = $dtoInstance->toObject(Product::class);

            expect($newProduct->id)->toBe($originalProduct->id); // @phpstan-ignore property.notFound
            expect($newProduct->name)->toBe($originalProduct->name); // @phpstan-ignore property.notFound
            expect($newProduct->price)->toBe($originalProduct->price); // @phpstan-ignore property.notFound
        });
    });
});

describe('Plain Object Integration - ObjectMappingTrait', function(): void {
    describe('toDto() from plain object', function(): void {
        it('converts object to DTO using HasDto attribute', function(): void {
            $productDto = new class extends SimpleDto {
                use SimpleDtoObjectTrait;

                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                    public readonly float $price = 0.0,
                ) {}
            };

            // Register the DTO class globally for the test
            if (!class_exists('ProductDto')) {
                class_alias($productDto::class, 'ProductDto');
            }

            $product = new ProductWithDto();
            $product->id = 1;
            $product->name = 'Laptop';
            $product->price = 999.99;

            $dto = $product->toDto();

            expect($dto->id)->toBe(1); // @phpstan-ignore property.notFound
            expect($dto->name)->toBe('Laptop'); // @phpstan-ignore property.notFound
            expect($dto->price)->toBe(999.99); // @phpstan-ignore property.notFound
        });

        it('converts object to DTO with explicit DTO class', function(): void {
            $productDto = new class extends SimpleDto {
                use SimpleDtoObjectTrait;

                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                    public readonly float $price = 0.0,
                ) {}
            };

            $product = new Product();
            $product->id = 1;
            $product->name = 'Laptop';
            $product->price = 999.99;

            // Create a plain object with ObjectMappingTrait
            $objectWithTrait = new class {
                use ObjectMappingTrait;

                public int $id = 0;
                public string $name = '';
                public float $price = 0.0;
            };

            $objectWithTrait->id = $product->id;
            $objectWithTrait->name = $product->name;
            $objectWithTrait->price = $product->price;

            $dto = $objectWithTrait->toDto($productDto::class);

            expect($dto->id)->toBe(1); // @phpstan-ignore property.notFound
            expect($dto->name)->toBe('Laptop'); // @phpstan-ignore property.notFound
            expect($dto->price)->toBe(999.99); // @phpstan-ignore property.notFound
        });

        it('throws exception if no DTO class provided and no attribute', function(): void {
            $objectWithTrait = new class {
                use ObjectMappingTrait;

                public string $name = 'Test';
            };

            expect(fn(): object => $objectWithTrait->toDto())
                ->toThrow(InvalidArgumentException::class, 'No DTO class provided');
        });

        it('throws exception if DTO class does not exist', function(): void {
            $objectWithTrait = new class {
                use ObjectMappingTrait;

                public string $name = 'Test';
            };

            expect(fn(): object => $objectWithTrait->toDto('NonExistentDto')) // @phpstan-ignore argument.type
                ->toThrow(InvalidArgumentException::class, 'DTO class NonExistentDto does not exist');
        });
    });

    describe('Round-trip with ObjectMappingTrait', function(): void {
        it('preserves data in round-trip (Object with trait → DTO → Object)', function(): void {
            $productDto = new class extends SimpleDto {
                use SimpleDtoObjectTrait;

                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                    public readonly float $price = 0.0,
                ) {}
            };

            $originalObject = new class {
                use ObjectMappingTrait;

                public int $id = 0;
                public string $name = '';
                public float $price = 0.0;
            };

            $originalObject->id = 1;
            $originalObject->name = 'Laptop';
            $originalObject->price = 999.99;

            $dto = $originalObject->toDto($productDto::class);
            $newObject = $dto->toObject($originalObject::class); // @phpstan-ignore method.notFound

            expect($newObject->id)->toBe($originalObject->id); // @phpstan-ignore property.notFound
            expect($newObject->name)->toBe($originalObject->name); // @phpstan-ignore property.notFound
            expect($newObject->price)->toBe($originalObject->price); // @phpstan-ignore property.notFound
        });
    });
});
