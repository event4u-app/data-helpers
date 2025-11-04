<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\ConverterMode;
use event4u\DataHelpers\LiteDto\Attributes\UltraFast;
use event4u\DataHelpers\LiteDto\LiteDto;

// UltraFast DTOs
#[UltraFast]
class LiteDtoUltraFastUserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
    ) {}
}

#[UltraFast]
class LiteDtoUltraFastProductDto extends LiteDto
{
    public function __construct(
        public readonly string $title,
        public readonly float $price,
        public readonly int $stock,
    ) {}
}

#[UltraFast]
#[ConverterMode]
class LiteDtoUltraFastWithConverterDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

#[UltraFast]
class LiteDtoUltraFastWithoutConverterDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

describe('UltraFast Mode', function(): void {
    describe('Basic Functionality', function(): void {
        it('creates DTO from array', function(): void {
            $dto = LiteDtoUltraFastUserDto::from([
                'name' => 'John',
                'age' => 30,
                'email' => 'john@example.com',
            ]);

            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBe(30)
                ->and($dto->email)->toBe('john@example.com');
        });

        it('converts DTO to array', function(): void {
            $dto = LiteDtoUltraFastUserDto::from([
                'name' => 'John',
                'age' => 30,
                'email' => 'john@example.com',
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'name' => 'John',
                'age' => 30,
                'email' => 'john@example.com',
            ]);
        });

        it('converts DTO to JSON', function(): void {
            $dto = LiteDtoUltraFastUserDto::from([
                'name' => 'John',
                'age' => 30,
                'email' => 'john@example.com',
            ]);

            $json = $dto->toJson();

            expect($json)->toBe('{"name":"John","age":30,"email":"john@example.com"}');
        });
    });

    describe('Performance Optimizations', function(): void {
        it('throws TypeError for missing required properties', function(): void {
            LiteDtoUltraFastUserDto::from([
                'name' => 'John',
                // age and email missing - will cause TypeError
            ]);
        })->throws(TypeError::class);

        it('ignores extra properties', function(): void {
            $dto = LiteDtoUltraFastUserDto::from([
                'name' => 'John',
                'age' => 30,
                'email' => 'john@example.com',
                'extra' => 'ignored',
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'name' => 'John',
                'age' => 30,
                'email' => 'john@example.com',
            ]);
        });
    });

    describe('Multiple DTOs', function(): void {
        it('handles different DTO types', function(): void {
            $user = LiteDtoUltraFastUserDto::from([
                'name' => 'John',
                'age' => 30,
                'email' => 'john@example.com',
            ]);

            $product = LiteDtoUltraFastProductDto::from([
                'title' => 'Laptop',
                'price' => 999.99,
                'stock' => 10,
            ]);

            expect($user->name)->toBe('John')
                ->and($product->title)->toBe('Laptop')
                ->and($product->price)->toBe(999.99);
        });
    });

    describe('Error Handling', function(): void {
        it('throws exception for non-array data', function(): void {
            LiteDtoUltraFastUserDto::from('{"name":"John"}');
        })->throws(
            InvalidArgumentException::class,
            'UltraFast mode only accepts arrays'
        );

        it('throws exception for objects', function(): void {
            LiteDtoUltraFastUserDto::from((object)['name' => 'John']);
        })->throws(
            InvalidArgumentException::class,
            'UltraFast mode only accepts arrays'
        );
    });

    describe('ConverterMode Support', function(): void {
        it('accepts JSON with ConverterMode', function(): void {
            $json = '{"name": "John Doe", "email": "john@example.com"}';

            $dto = LiteDtoUltraFastWithConverterDto::from($json);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
        });

        it('accepts XML with ConverterMode', function(): void {
            $xml = '<root><name>John Doe</name><email>john@example.com</email></root>';

            $dto = LiteDtoUltraFastWithConverterDto::from($xml);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
        });

        it('accepts YAML with ConverterMode', function(): void {
            $yaml = "name: John Doe\nemail: john@example.com";

            $dto = LiteDtoUltraFastWithConverterDto::from($yaml);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
        });

        it('accepts arrays with ConverterMode', function(): void {
            $data = ['name' => 'John Doe', 'email' => 'john@example.com'];

            $dto = LiteDtoUltraFastWithConverterDto::from($data);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
        });

        it('throws exception for JSON without ConverterMode', function(): void {
            $json = '{"name": "John Doe", "email": "john@example.com"}';

            LiteDtoUltraFastWithoutConverterDto::from($json);
        })->throws(InvalidArgumentException::class, 'UltraFast mode only accepts arrays');
    });

    describe('Caching', function(): void {
        it('caches parameter names for performance', function(): void {
            // First call
            $dto1 = LiteDtoUltraFastUserDto::from([
                'name' => 'John',
                'age' => 30,
                'email' => 'john@example.com',
            ]);

            // Second call (should use cache)
            $dto2 = LiteDtoUltraFastUserDto::from([
                'name' => 'Jane',
                'age' => 25,
                'email' => 'jane@example.com',
            ]);

            expect($dto1->name)->toBe('John')
                ->and($dto2->name)->toBe('Jane');
        });
    });
});
