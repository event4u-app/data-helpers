<?php

declare(strict_types=1);

use DateTime;
use event4u\DataHelpers\LiteDto\Attributes\CastWith;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Computed;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;
use event4u\DataHelpers\SimpleDto\Attributes\ConverterMode;
use event4u\DataHelpers\SimpleDto\Attributes\DataCollectionOf;
use event4u\DataHelpers\SimpleDto\Attributes\Hidden;
use event4u\DataHelpers\SimpleDto\Attributes\HiddenFromArray;
use event4u\DataHelpers\SimpleDto\Attributes\HiddenFromJson;
use event4u\DataHelpers\SimpleDto\Attributes\Lazy;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\MapInputName;
use event4u\DataHelpers\SimpleDto\Attributes\MapOutputName;
use event4u\DataHelpers\SimpleDto\Attributes\MapTo;
use event4u\DataHelpers\SimpleDto\Attributes\UltraFast;
use event4u\DataHelpers\SimpleDto\Attributes\Visible;
use event4u\DataHelpers\SimpleDto\Enums\NamingConvention;
use event4u\DataHelpers\SimpleDto\Support\UltraFastEngine;

// Custom caster for tests
class UltraFastDateTimeCaster
{
    public static function cast(mixed $value): ?DateTime
    {
        if (null === $value) {
            return null;
        }
        return new DateTime($value);
    }
}

class UltraFastUpperCaseCaster
{
    public static function cast(mixed $value): string
    {
        return strtoupper((string)$value);
    }
}

enum UltraFastStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

// UltraFast DTOs
#[UltraFast]
class SimpleDtoUltraFastDepartmentDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly float $budget,
        public readonly int $employee_count = 0,
    ) {}
}

#[UltraFast]
class SimpleDtoUltraFastWithMapFromDto extends SimpleDto
{
    public function __construct(
        #[MapFrom('department_name')]
        public readonly string $name,

        #[MapFrom('department_code')]
        public readonly string $code,
    ) {}
}

#[UltraFast]
class SimpleDtoUltraFastWithMapToDto extends SimpleDto
{
    public function __construct(
        #[MapTo('department_name')]
        public readonly string $name,

        #[MapTo('department_code')]
        public readonly string $code,
    ) {}
}

#[UltraFast]
class SimpleDtoUltraFastCompanyDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly SimpleDtoUltraFastDepartmentDto $department,
    ) {}
}

#[UltraFast]
class SimpleDtoUltraFastWithNullableDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $code = null,
    ) {}
}

#[UltraFast]
#[ConverterMode]
class SimpleDtoUltraFastWithConverterDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

#[UltraFast]
class SimpleDtoUltraFastWithoutConverterDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

#[UltraFast]
class SimpleDtoUltraFastWithHiddenDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,

        #[Hidden]
        public readonly string $password,

        #[Hidden]
        public readonly string $apiKey,
    ) {}
}

#[UltraFast]
class SimpleDtoUltraFastWithCastWithDto extends SimpleDto
{
    public function __construct(
        #[CastWith(UltraFastUpperCaseCaster::class)]
        public readonly string $name,

        #[CastWith(UltraFastDateTimeCaster::class)]
        public readonly ?DateTime $createdAt,
    ) {}
}

#[UltraFast]
class SimpleDtoUltraFastWithConvertEmptyDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[ConvertEmptyToNull]
        public readonly ?string $description,

        #[ConvertEmptyToNull(convertZero: true)]
        public readonly ?int $count,
    ) {}
}

#[UltraFast]
class SimpleDtoUltraFastItemDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
    ) {}
}

#[UltraFast]
class SimpleDtoUltraFastWithDataCollectionDto extends SimpleDto
{
    /** @param array<SimpleDtoUltraFastItemDto> $items */
    public function __construct(
        public readonly string $orderNumber,

        #[DataCollectionOf(SimpleDtoUltraFastItemDto::class)]
        public readonly array $items,
    ) {}
}

#[UltraFast]
class SimpleDtoUltraFastWithEnumDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly UltraFastStatus $status,
    ) {}
}

#[UltraFast]
class SimpleDtoUltraFastWithVisibilityDto extends SimpleDto
{
    public function __construct(
        #[Visible]
        public readonly string $name,

        #[Visible]
        public readonly string $email,

        public readonly string $password,
        public readonly string $apiKey,
    ) {}
}

#[UltraFast]
class SimpleDtoUltraFastWithHiddenFromArrayDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,

        #[HiddenFromArray]
        public readonly string $password,
    ) {}
}

#[UltraFast]
class SimpleDtoUltraFastWithHiddenFromJsonDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,

        #[HiddenFromJson]
        public readonly string $apiKey,
    ) {}
}

class SimpleDtoNormalDepartmentDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly float $budget,
        public readonly int $employee_count = 0,
    ) {}
}

beforeEach(function(): void {
    UltraFastEngine::clearCache();
});

describe('UltraFast Mode', function(): void {
    describe('Basic Functionality', function(): void {
        it('creates DTO from array', function(): void {
            $dto = SimpleDtoUltraFastDepartmentDto::fromArray([
                'name' => 'Engineering',
                'code' => 'ENG',
                'budget' => 1000000.0,
                'employee_count' => 50,
            ]);

            expect($dto->name)->toBe('Engineering')
                ->and($dto->code)->toBe('ENG')
                ->and($dto->budget)->toBe(1000000.0)
                ->and($dto->employee_count)->toBe(50);
        });

        it('converts DTO to array', function(): void {
            $dto = SimpleDtoUltraFastDepartmentDto::fromArray([
                'name' => 'Engineering',
                'code' => 'ENG',
                'budget' => 1000000.0,
                'employee_count' => 50,
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'name' => 'Engineering',
                'code' => 'ENG',
                'budget' => 1000000.0,
                'employee_count' => 50,
            ]);
        });

        it('converts DTO to JSON', function(): void {
            $dto = SimpleDtoUltraFastDepartmentDto::fromArray([
                'name' => 'Engineering',
                'code' => 'ENG',
                'budget' => 1000000.0,
                'employee_count' => 50,
            ]);

            $json = $dto->toJson();

            expect($json)->toBe('{"name":"Engineering","code":"ENG","budget":1000000,"employee_count":50}');
        });
    });

    describe('Attribute Support', function(): void {
        it('handles MapFrom attribute', function(): void {
            /** @phpstan-ignore-next-line staticMethod.notFound */
            $dto = SimpleDtoUltraFastWithMapFromDto::fromArray([
                'department_name' => 'Engineering',
                'department_code' => 'ENG',
            ]);

            expect($dto->name)->toBe('Engineering')
                /** @phpstan-ignore-next-line property.notFound */
                ->and($dto->code)->toBe('ENG');
        });

        it('handles MapTo attribute', function(): void {
            /** @phpstan-ignore-next-line staticMethod.notFound */
            $dto = SimpleDtoUltraFastWithMapToDto::fromArray([
                'name' => 'Engineering',
                'code' => 'ENG',
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'department_name' => 'Engineering',
                'department_code' => 'ENG',
            ]);
        });
    });

    describe('Nested DTOs', function(): void {
        it('handles nested DTOs', function(): void {
            $dto = SimpleDtoUltraFastCompanyDto::fromArray([
                'name' => 'Acme Corp',
                'department' => [
                    'name' => 'Engineering',
                    'code' => 'ENG',
                    'budget' => 1000000.0,
                    'employee_count' => 50,
                ],
            ]);

            expect($dto->name)->toBe('Acme Corp')
                ->and($dto->department->name)->toBe('Engineering')
                ->and($dto->department->code)->toBe('ENG');
        });

        it('handles nested DTOs in toArray', function(): void {
            $dto = SimpleDtoUltraFastCompanyDto::fromArray([
                'name' => 'Acme Corp',
                'department' => [
                    'name' => 'Engineering',
                    'code' => 'ENG',
                    'budget' => 1000000.0,
                    'employee_count' => 50,
                ],
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'name' => 'Acme Corp',
                'department' => [
                    'name' => 'Engineering',
                    'code' => 'ENG',
                    'budget' => 1000000.0,
                    'employee_count' => 50,
                ],
            ]);
        });
    });

    describe('Default and Nullable Values', function(): void {
        it('handles default values', function(): void {
            $dto = SimpleDtoUltraFastDepartmentDto::fromArray([
                'name' => 'Engineering',
                'code' => 'ENG',
                'budget' => 1000000.0,
                // employee_count is missing, should use default
            ]);

            expect($dto->employee_count)->toBe(0);
        });

        it('handles nullable values', function(): void {
            $dto = SimpleDtoUltraFastWithNullableDto::fromArray([
                'name' => 'Engineering',
                // code is missing, should be null
            ]);

            expect($dto->name)->toBe('Engineering')
                ->and($dto->code)->toBeNull();
        });
    });

    describe('Error Handling', function(): void {
        it('throws exception for missing required parameter', function(): void {
            SimpleDtoUltraFastDepartmentDto::fromArray([
                'code' => 'ENG',
                'budget' => 1000000.0,
                'employee_count' => 50,
            ]);
        })->throws(InvalidArgumentException::class, 'Missing required parameter: name');
    });

    describe('ConverterMode Support', function(): void {
        it('accepts JSON with ConverterMode', function(): void {
            $json = '{"name": "John Doe", "email": "john@example.com"}';

            $dto = SimpleDtoUltraFastWithConverterDto::from($json);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
        });

        it('accepts XML with ConverterMode', function(): void {
            $xml = '<root><name>John Doe</name><email>john@example.com</email></root>';

            $dto = SimpleDtoUltraFastWithConverterDto::from($xml);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
        });

        it('accepts YAML with ConverterMode', function(): void {
            $yaml = "name: John Doe\nemail: john@example.com";

            $dto = SimpleDtoUltraFastWithConverterDto::from($yaml);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
        });

        it('fromJson() uses direct JSON parsing (no format detection)', function(): void {
            $json = '{"name": "Jane Doe", "email": "jane@example.com"}';

            $dto = SimpleDtoUltraFastWithConverterDto::fromJson($json);

            expect($dto->name)->toBe('Jane Doe');
            expect($dto->email)->toBe('jane@example.com');
        });

        it('fromXml() uses direct XML parsing (no format detection)', function(): void {
            $xml = '<root><name>Bob Smith</name><email>bob@example.com</email></root>';

            $dto = SimpleDtoUltraFastWithConverterDto::fromXml($xml);

            expect($dto->name)->toBe('Bob Smith');
            expect($dto->email)->toBe('bob@example.com');
        });

        it('fromYaml() uses direct YAML parsing (no format detection)', function(): void {
            $yaml = "name: Alice Brown\nemail: alice@example.com";

            $dto = SimpleDtoUltraFastWithConverterDto::fromYaml($yaml);

            expect($dto->name)->toBe('Alice Brown');
            expect($dto->email)->toBe('alice@example.com');
        });

        it('accepts arrays with ConverterMode', function(): void {
            $data = ['name' => 'John Doe', 'email' => 'john@example.com'];

            $dto = SimpleDtoUltraFastWithConverterDto::from($data);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
        });

        it('throws exception for JSON without ConverterMode', function(): void {
            $json = '{"name": "John Doe", "email": "john@example.com"}';

            SimpleDtoUltraFastWithoutConverterDto::from($json);
        })->throws(InvalidArgumentException::class, 'UltraFast mode only accepts arrays');

        it('converts JSON to array in fromArray with ConverterMode', function(): void {
            $data = ['name' => 'John Doe', 'email' => 'john@example.com'];

            $dto = SimpleDtoUltraFastWithConverterDto::fromArray($data);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
        });
    });

    describe('Performance', function(): void {
        it('is much faster than normal mode', function(): void {
            $data = [
                'name' => 'Engineering',
                'code' => 'ENG',
                'budget' => 1000000.0,
                'employee_count' => 50,
            ];

            // Warm up
            for ($i = 0; 100 > $i; $i++) {
                SimpleDtoUltraFastDepartmentDto::fromArray($data);
                SimpleDtoNormalDepartmentDto::fromArray($data);
            }

            // Benchmark UltraFast
            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                SimpleDtoUltraFastDepartmentDto::fromArray($data);
            }
            $ultraFastTime = microtime(true) - $start;

            // Benchmark Normal
            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                SimpleDtoNormalDepartmentDto::fromArray($data);
            }
            $normalTime = microtime(true) - $start;

            // UltraFast should be faster than Normal or at least as fast as Normal
            // Note: With cache, the difference is smaller. Real gains come with cache optimization.
            expect($ultraFastTime)->toBeLessThan($normalTime);
        });
    });

    describe('Hidden Attribute Support', function(): void {
        it('hides properties from toArray', function(): void {
            $dto = SimpleDtoUltraFastWithHiddenDto::fromArray([
                'name' => 'John',
                'email' => 'john@example.com',
                'password' => 'secret123',
                'apiKey' => 'key_abc',
            ]);

            $array = $dto->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('email');
            expect($array)->not()->toHaveKey('password');
            expect($array)->not()->toHaveKey('apiKey');
        });

        it('keeps hidden properties accessible', function(): void {
            $dto = SimpleDtoUltraFastWithHiddenDto::fromArray([
                'name' => 'John',
                'email' => 'john@example.com',
                'password' => 'secret123',
                'apiKey' => 'key_abc',
            ]);

            expect($dto->password)->toBe('secret123');
            expect($dto->apiKey)->toBe('key_abc');
        });

        it('hides properties from JSON', function(): void {
            $dto = SimpleDtoUltraFastWithHiddenDto::fromArray([
                'name' => 'John',
                'email' => 'john@example.com',
                'password' => 'secret123',
                'apiKey' => 'key_abc',
            ]);

            $json = $dto->toJson();
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('name');
            expect($decoded)->toHaveKey('email');
            expect($decoded)->not()->toHaveKey('password');
            expect($decoded)->not()->toHaveKey('apiKey');
        });
    });

    describe('CastWith Attribute Support', function(): void {
        it('casts values with custom caster', function(): void {
            $dto = SimpleDtoUltraFastWithCastWithDto::fromArray([
                'name' => 'john doe',
                'createdAt' => '2024-01-15 10:00:00',
            ]);

            expect($dto->name)->toBe('JOHN DOE');
            expect($dto->createdAt)->toBeInstanceOf(DateTime::class);
            expect($dto->createdAt?->format('Y-m-d'))->toBe('2024-01-15');
        });

        it('handles null values in casters', function(): void {
            $dto = SimpleDtoUltraFastWithCastWithDto::fromArray([
                'name' => 'test',
                'createdAt' => null,
            ]);

            expect($dto->name)->toBe('TEST');
            expect($dto->createdAt)->toBeNull();
        });
    });

    describe('ConvertEmptyToNull Attribute Support', function(): void {
        it('converts empty string to null', function(): void {
            $dto = SimpleDtoUltraFastWithConvertEmptyDto::fromArray([
                'name' => 'John',
                'description' => '',
                'count' => 10,
            ]);

            expect($dto->description)->toBeNull();
        });

        it('converts zero to null when enabled', function(): void {
            $dto = SimpleDtoUltraFastWithConvertEmptyDto::fromArray([
                'name' => 'John',
                'description' => 'test',
                'count' => 0,
            ]);

            expect($dto->count)->toBeNull();
        });

        it('keeps non-empty values', function(): void {
            $dto = SimpleDtoUltraFastWithConvertEmptyDto::fromArray([
                'name' => 'John',
                'description' => 'Some text',
                'count' => 5,
            ]);

            expect($dto->description)->toBe('Some text');
            expect($dto->count)->toBe(5);
        });
    });

    describe('DataCollectionOf Attribute Support', function(): void {
        it('converts array of arrays to array of DTOs', function(): void {
            $dto = SimpleDtoUltraFastWithDataCollectionDto::fromArray([
                'orderNumber' => 'ORD-123',
                'items' => [
                    ['name' => 'Item 1', 'price' => 10.50],
                    ['name' => 'Item 2', 'price' => 20.00],
                ],
            ]);

            expect($dto->items)->toBeArray();
            expect($dto->items)->toHaveCount(2);
            expect($dto->items[0])->toBeInstanceOf(SimpleDtoUltraFastItemDto::class);
            expect($dto->items[0]->name)->toBe('Item 1');
            expect($dto->items[0]->price)->toBe(10.50);
        });
    });

    describe('Enum Support', function(): void {
        it('casts string to enum', function(): void {
            $dto = SimpleDtoUltraFastWithEnumDto::fromArray([
                'name' => 'John',
                'status' => 'active',
            ]);

            expect($dto->status)->toBeInstanceOf(UltraFastStatus::class);
            expect($dto->status)->toBe(UltraFastStatus::ACTIVE);
        });
    });

    describe('Visible Attribute Support', function(): void {
        it('only includes visible properties in toArray', function(): void {
            $dto = SimpleDtoUltraFastWithVisibilityDto::fromArray([
                'name' => 'John',
                'email' => 'john@example.com',
                'password' => 'secret',
                'apiKey' => 'key_abc',
            ]);

            $array = $dto->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('email');
            expect($array)->not()->toHaveKey('password');
            expect($array)->not()->toHaveKey('apiKey');
        });
    });

    describe('HiddenFromArray Attribute Support', function(): void {
        it('hides properties only from toArray', function(): void {
            $dto = SimpleDtoUltraFastWithHiddenFromArrayDto::fromArray([
                'name' => 'John',
                'email' => 'john@example.com',
                'password' => 'secret',
            ]);

            $array = $dto->toArray();
            $json = json_decode($dto->toJson(), true);

            // Hidden from array
            expect($array)->not()->toHaveKey('password');

            // But visible in JSON
            expect($json)->toHaveKey('password');
            expect($json['password'])->toBe('secret');
        });
    });

    describe('HiddenFromJson Attribute Support', function(): void {
        it('hides properties only from toJson', function(): void {
            $dto = SimpleDtoUltraFastWithHiddenFromJsonDto::fromArray([
                'name' => 'John',
                'email' => 'john@example.com',
                'apiKey' => 'key_abc',
            ]);

            $array = $dto->toArray();
            $json = json_decode($dto->toJson(), true);

            // Visible in array
            expect($array)->toHaveKey('apiKey');
            expect($array['apiKey'])->toBe('key_abc');

            // But hidden from JSON
            expect($json)->not()->toHaveKey('apiKey');
        });
    });

    describe('MapInputName Attribute Support', function(): void {
        it('transforms input names using snake_case convention', function(): void {
            #[UltraFast]
            #[MapInputName(NamingConvention::SnakeCase)]
            class SimpleDtoUltraFastWithMapInputNameDto extends SimpleDto
            {
                public function __construct(
                    public readonly string $userName,
                    public readonly string $emailAddress,
                ) {
                }
            }

            $dto = SimpleDtoUltraFastWithMapInputNameDto::fromArray([
                'user_name' => 'John Doe',
                'email_address' => 'john@example.com',
            ]);

            expect($dto->userName)->toBe('John Doe');
            expect($dto->emailAddress)->toBe('john@example.com');
        });

        it('MapFrom overrides MapInputName', function(): void {
            #[UltraFast]
            #[MapInputName(NamingConvention::SnakeCase)]
            class SimpleDtoUltraFastWithMapInputNameOverrideDto extends SimpleDto
            {
                public function __construct(
                    public readonly string $userName,
                    #[MapFrom('custom_email')]
                    public readonly string $emailAddress,
                ) {
                }
            }

            $dto = SimpleDtoUltraFastWithMapInputNameOverrideDto::fromArray([
                'user_name' => 'John Doe',
                'custom_email' => 'john@example.com',
            ]);

            expect($dto->userName)->toBe('John Doe');
            expect($dto->emailAddress)->toBe('john@example.com');
        });
    });

    describe('MapOutputName Attribute Support', function(): void {
        it('transforms output names using snake_case convention', function(): void {
            #[UltraFast]
            #[MapOutputName(NamingConvention::SnakeCase)]
            class SimpleDtoUltraFastWithMapOutputNameDto extends SimpleDto
            {
                public function __construct(
                    public readonly string $userName,
                    public readonly string $emailAddress,
                ) {
                }
            }

            $dto = new SimpleDtoUltraFastWithMapOutputNameDto('John Doe', 'john@example.com');
            $array = $dto->toArray();

            expect($array)->toHaveKey('user_name');
            expect($array)->toHaveKey('email_address');
            expect($array['user_name'])->toBe('John Doe');
            expect($array['email_address'])->toBe('john@example.com');
        });

        it('MapTo overrides MapOutputName', function(): void {
            #[UltraFast]
            #[MapOutputName(NamingConvention::SnakeCase)]
            class SimpleDtoUltraFastWithMapOutputNameOverrideDto extends SimpleDto
            {
                public function __construct(
                    public readonly string $userName,
                    #[MapTo('custom_email')]
                    public readonly string $emailAddress,
                ) {
                }
            }

            $dto = new SimpleDtoUltraFastWithMapOutputNameOverrideDto('John Doe', 'john@example.com');
            $array = $dto->toArray();

            expect($array)->toHaveKey('user_name');
            expect($array)->toHaveKey('custom_email');
            expect($array['user_name'])->toBe('John Doe');
            expect($array['custom_email'])->toBe('john@example.com');
        });

        it('works with toJson()', function(): void {
            #[UltraFast]
            #[MapOutputName(NamingConvention::SnakeCase)]
            class SimpleDtoUltraFastWithMapOutputNameJsonDto extends SimpleDto
            {
                public function __construct(
                    public readonly string $userName,
                    public readonly string $emailAddress,
                ) {
                }
            }

            $dto = new SimpleDtoUltraFastWithMapOutputNameJsonDto('John Doe', 'john@example.com');
            $json = json_decode($dto->toJson(), true);

            expect($json)->toHaveKey('user_name');
            expect($json)->toHaveKey('email_address');
            expect($json['user_name'])->toBe('John Doe');
            expect($json['email_address'])->toBe('john@example.com');
        });
    });

    describe('Phase 3: Computed, Lazy, Optional', function(): void {
        it('supports Computed properties', function(): void {
            #[UltraFast]
            class SimpleDtoUltraFastWithComputedDto extends SimpleDto
            {
                public function __construct(
                    public readonly float $price,
                    public readonly int $quantity,
                ) {}

                #[Computed]
                public function total(): float
                {
                    return $this->price * $this->quantity;
                }

                #[Computed(name: 'taxAmount')]
                public function tax(): float
                {
                    return $this->price * $this->quantity * 0.19;
                }
            }

            $dto = SimpleDtoUltraFastWithComputedDto::fromArray([
                'price' => 100.0,
                'quantity' => 2,
            ]);

            expect($dto->price)->toBe(100.0);
            expect($dto->quantity)->toBe(2);
            expect($dto->total())->toBe(200.0);
            expect($dto->tax())->toBe(38.0);

            // Computed properties should be included in toArray()
            $array = $dto->toArray();
            expect($array)->toHaveKey('price');
            expect($array)->toHaveKey('quantity');
            expect($array)->toHaveKey('total');
            expect($array)->toHaveKey('taxAmount'); // Custom name
            expect($array['total'])->toBe(200.0);
            expect($array['taxAmount'])->toBe(38.0);
        });

        it('supports Lazy properties', function(): void {
            #[UltraFast]
            class SimpleDtoUltraFastWithLazyDto extends SimpleDto
            {
                public function __construct(
                    public readonly string $name,
                    public readonly string $email,

                    #[Lazy]
                    public readonly ?string $biography = null,

                    /** @var array<string>|null */
                    #[Lazy]
                    public readonly ?array $posts = null,
                ) {}
            }

            $dto = SimpleDtoUltraFastWithLazyDto::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'biography' => 'Long biography text...',
                'posts' => ['post1', 'post2', 'post3'],
            ]);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->biography)->toBe('Long biography text...');
            expect($dto->posts)->toBe(['post1', 'post2', 'post3']);

            // Lazy properties should NOT be included in toArray()
            $array = $dto->toArray();
            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('email');
            expect($array)->not->toHaveKey('biography');
            expect($array)->not->toHaveKey('posts');
        });

        it('supports lazy Computed properties', function(): void {
            #[UltraFast]
            class SimpleDtoUltraFastWithLazyComputedDto extends SimpleDto
            {
                public function __construct(
                    public readonly string $name,
                    public readonly int $userId,
                ) {}

                #[Computed]
                public function displayName(): string
                {
                    return strtoupper($this->name);
                }

                /** @return array<string, string> */
                #[Computed(lazy: true)]
                public function expensiveCalculation(): array
                {
                    return ['result' => 'expensive'];
                }
            }

            $dto = SimpleDtoUltraFastWithLazyComputedDto::fromArray([
                'name' => 'John Doe',
                'userId' => 123,
            ]);

            // Regular computed should be included
            $array = $dto->toArray();
            expect($array)->toHaveKey('displayName');
            expect($array['displayName'])->toBe('JOHN DOE');

            // Lazy computed should NOT be included
            expect($array)->not->toHaveKey('expensiveCalculation');
        });

        it('combines Computed and Lazy', function(): void {
            #[UltraFast]
            class SimpleDtoUltraFastCombinedDto extends SimpleDto
            {
                public function __construct(
                    public readonly string $firstName,
                    public readonly string $lastName,

                    #[Lazy]
                    public readonly ?string $avatar = null,
                ) {}

                #[Computed]
                public function fullName(): string
                {
                    return $this->firstName . ' ' . $this->lastName;
                }
            }

            $dto = SimpleDtoUltraFastCombinedDto::fromArray([
                'firstName' => 'John',
                'lastName' => 'Doe',
                'avatar' => 'base64-encoded-image-data',
            ]);

            $array = $dto->toArray();

            // Regular properties
            expect($array)->toHaveKey('firstName');
            expect($array)->toHaveKey('lastName');

            // Computed property included
            expect($array)->toHaveKey('fullName');
            expect($array['fullName'])->toBe('John Doe');

            // Lazy property excluded
            expect($array)->not->toHaveKey('avatar');
        });
    });
});
