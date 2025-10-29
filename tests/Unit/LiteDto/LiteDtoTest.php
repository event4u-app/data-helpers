<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\ConvertEmptyToNull;
use event4u\DataHelpers\LiteDto\Attributes\ConverterMode;
use event4u\DataHelpers\LiteDto\Attributes\Hidden;
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;
use event4u\DataHelpers\LiteDto\Attributes\MapTo;
use event4u\DataHelpers\LiteDto\LiteDto;

// Test DTOs
class LiteDtoBasicLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

class LiteDtoMappedLiteDto extends LiteDto
{
    public function __construct(
        #[MapFrom('user_name')]
        public readonly string $name,
        #[MapFrom('user_age')]
        public readonly int $age,
    ) {}
}

class LiteDtoOutputMappedLiteDto extends LiteDto
{
    public function __construct(
        #[MapTo('user_name')]
        public readonly string $name,
        #[MapTo('user_age')]
        public readonly int $age,
    ) {}
}

class LiteDtoHiddenLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[Hidden]
        public readonly string $password,
    ) {}
}

class LiteDtoConvertEmptyLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[ConvertEmptyToNull]
        public readonly ?string $description,
    ) {}
}

class LiteDtoNestedAddressDto extends LiteDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

class LiteDtoNestedUserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly LiteDtoNestedAddressDto $address,
    ) {}
}

class LiteDtoCollectionTeamDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        /** @var array<LiteDtoBasicLiteDto> */
        public readonly array $members,
    ) {}
}

#[ConverterMode]
class LiteDtoConverterLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

describe('LiteDto', function(): void {
    describe('Basic Functionality', function(): void {
        it('can be created from array', function(): void {
            $dto = LiteDtoBasicLiteDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBe(30);
        });

        it('can be converted to array', function(): void {
            $dto = LiteDtoBasicLiteDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'name' => 'John',
                'age' => 30,
            ]);
        });

        it('can be converted to JSON', function(): void {
            $dto = LiteDtoBasicLiteDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            $json = $dto->toJson();

            expect($json)->toBe('{"name":"John","age":30}');
        });

        it('implements JsonSerializable', function(): void {
            $dto = LiteDtoBasicLiteDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            $json = json_encode($dto);

            expect($json)->toBe('{"name":"John","age":30}');
        });
    });

    describe('#[MapFrom] Attribute', function(): void {
        it('maps properties from source keys', function(): void {
            $dto = LiteDtoMappedLiteDto::from([
                'user_name' => 'John',
                'user_age' => 30,
            ]);

            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBe(30);
        });

        it('throws exception for missing required property', function(): void {
            expect(fn(): \LiteDtoMappedLiteDto => LiteDtoMappedLiteDto::from([
                'user_name' => 'John',
            ]))->toThrow(TypeError::class);
        });
    });

    describe('#[MapTo] Attribute', function(): void {
        it('maps properties to target keys', function(): void {
            $dto = LiteDtoOutputMappedLiteDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'user_name' => 'John',
                'user_age' => 30,
            ]);
        });
    });

    describe('#[Hidden] Attribute', function(): void {
        it('excludes properties from serialization', function(): void {
            $dto = LiteDtoHiddenLiteDto::from([
                'name' => 'John',
                'password' => 'secret',
            ]);

            $array = $dto->toArray();

            expect($array)->toBe(['name' => 'John'])
                ->and($dto->password)->toBe('secret'); // Still accessible
        });

        it('excludes hidden properties from JSON', function(): void {
            $dto = LiteDtoHiddenLiteDto::from([
                'name' => 'John',
                'password' => 'secret',
            ]);

            $json = $dto->toJson();

            expect($json)->toBe('{"name":"John"}');
        });
    });

    describe('#[ConvertEmptyToNull] Attribute', function(): void {
        it('converts empty string to null', function(): void {
            $dto = LiteDtoConvertEmptyLiteDto::from([
                'name' => 'John',
                'description' => '',
            ]);

            expect($dto->description)->toBeNull();
        });

        it('converts empty array to null', function(): void {
            $dto = LiteDtoConvertEmptyLiteDto::from([
                'name' => 'John',
                'description' => [],
            ]);

            expect($dto->description)->toBeNull();
        });

        it('keeps non-empty values', function(): void {
            $dto = LiteDtoConvertEmptyLiteDto::from([
                'name' => 'John',
                'description' => 'A description',
            ]);

            expect($dto->description)->toBe('A description');
        });
    });

    describe('Nested DTOs', function(): void {
        it('handles nested DTOs', function(): void {
            $dto = LiteDtoNestedUserDto::from([
                'name' => 'John',
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'New York',
                ],
            ]);

            expect($dto->name)->toBe('John')
                ->and($dto->address)->toBeInstanceOf(LiteDtoNestedAddressDto::class)
                ->and($dto->address->street)->toBe('123 Main St')
                ->and($dto->address->city)->toBe('New York');
        });

        it('serializes nested DTOs', function(): void {
            $dto = LiteDtoNestedUserDto::from([
                'name' => 'John',
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'New York',
                ],
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'name' => 'John',
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'New York',
                ],
            ]);
        });
    });

    describe('Collections', function(): void {
        it('handles collections of DTOs', function(): void {
            $dto = LiteDtoCollectionTeamDto::from([
                'name' => 'Engineering',
                'members' => [
                    ['name' => 'John', 'age' => 30],
                    ['name' => 'Jane', 'age' => 25],
                ],
            ]);

            expect($dto->name)->toBe('Engineering')
                ->and($dto->members)->toHaveCount(2)
                ->and($dto->members[0])->toBeInstanceOf(LiteDtoBasicLiteDto::class)
                ->and($dto->members[0]->name)->toBe('John')
                ->and($dto->members[1]->name)->toBe('Jane');
        });

        it('serializes collections', function(): void {
            $dto = LiteDtoCollectionTeamDto::from([
                'name' => 'Engineering',
                'members' => [
                    ['name' => 'John', 'age' => 30],
                    ['name' => 'Jane', 'age' => 25],
                ],
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'name' => 'Engineering',
                'members' => [
                    ['name' => 'John', 'age' => 30],
                    ['name' => 'Jane', 'age' => 25],
                ],
            ]);
        });
    });

    describe('#[ConverterMode]', function(): void {
        it('accepts JSON strings', function(): void {
            $dto = LiteDtoConverterLiteDto::from('{"name":"John","age":30}');

            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBe(30);
        });

        it('accepts XML strings', function(): void {
            $xml = '<?xml version="1.0"?><root><name>John</name><age>30</age></root>';
            $dto = LiteDtoConverterLiteDto::from($xml);

            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBe(30); // XML converts to int via type casting
        });

        it('accepts objects', function(): void {
            $obj = (object)['name' => 'John', 'age' => 30];
            $dto = LiteDtoConverterLiteDto::from($obj);

            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBe(30);
        });

        it('throws exception without ConverterMode', function(): void {
            expect(fn(): \LiteDtoBasicLiteDto => LiteDtoBasicLiteDto::from('{"name":"John","age":30}'))
                ->toThrow(InvalidArgumentException::class);
        });
    });
});
