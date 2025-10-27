<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Between;
use event4u\DataHelpers\SimpleDto\Attributes\Confirmed;
use event4u\DataHelpers\SimpleDto\Attributes\ConfirmedBy;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\In;
use event4u\DataHelpers\SimpleDto\Attributes\Max;
use event4u\DataHelpers\SimpleDto\Attributes\Min;
use event4u\DataHelpers\SimpleDto\Attributes\NotIn;
use event4u\DataHelpers\SimpleDto\Attributes\Regex;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\Url;
use event4u\DataHelpers\SimpleDto\Attributes\Uuid;

// Test Dtos
class TestDtoWithRequiredString extends SimpleDto
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}

class TestDtoWithNullableString extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
    ) {
    }
}

class TestDtoWithInt extends SimpleDto
{
    public function __construct(
        public readonly int $age,
    ) {
    }
}

class TestDtoWithFloat extends SimpleDto
{
    public function __construct(
        public readonly float $price,
    ) {
    }
}

class TestDtoWithBool extends SimpleDto
{
    public function __construct(
        public readonly bool $active,
    ) {
    }
}

class TestDtoWithArray extends SimpleDto
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly array $tags,
    ) {
    }
}

class TestDtoWithEmailAttribute extends SimpleDto
{
    public function __construct(
        #[Email]
        public readonly string $email,
    ) {
    }
}

class TestDtoWithMinAttribute extends SimpleDto
{
    public function __construct(
        #[Min(3)]
        public readonly string $name,
    ) {
    }
}

class TestDtoWithMaxAttribute extends SimpleDto
{
    public function __construct(
        #[Max(255)]
        public readonly string $name,
    ) {
    }
}

class TestDtoWithBetweenAttribute extends SimpleDto
{
    public function __construct(
        #[Between(18, 120)]
        public readonly int $age,
    ) {
    }
}

class TestDtoWithUrlAttribute extends SimpleDto
{
    public function __construct(
        #[Url]
        public readonly string $website,
    ) {
    }
}

class TestDtoWithUuidAttribute extends SimpleDto
{
    public function __construct(
        #[Uuid]
        public readonly string $id,
    ) {
    }
}

class TestDtoWithInAttribute extends SimpleDto
{
    public function __construct(
        #[In(['admin', 'user', 'guest'])]
        public readonly string $role,
    ) {
    }
}

class TestDtoWithRegexAttribute extends SimpleDto
{
    public function __construct(
        #[Regex('/^[A-Z]{2}\d{4}$/')]
        public readonly string $code,
    ) {
    }
}

class TestDtoWithMultipleAttributes extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Email]
        #[Max(255)]
        public readonly string $email,
    ) {
    }
}

class TestDtoWithCustomRules extends SimpleDto
{
    public function __construct(
        public readonly string $email,
    ) {
    }

    protected function rules(): array
    {
        return [
            'email' => ['email', 'max:255'],
        ];
    }
}

class TestDtoWithRequiredAttribute extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly ?string $name = null,
    ) {
    }
}

class TestDtoWithNotInAttribute extends SimpleDto
{
    public function __construct(
        #[NotIn(['admin', 'root', 'system'])]
        public readonly string $username,
    ) {
    }
}

class TestDtoWithConfirmedAttribute extends SimpleDto
{
    public function __construct(
        #[Confirmed]
        public readonly string $password,
        public readonly string $password_confirmed,
    ) {
    }
}

class TestDtoWithConfirmedByAttribute extends SimpleDto
{
    public function __construct(
        #[ConfirmedBy('passwordVerification')]
        public readonly string $password,
        public readonly string $passwordVerification,
    ) {
    }
}

// Nested Dtos for testing
class ValidationAddressDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Min(3)]
        public readonly string $street,

        #[Required]
        #[Min(2)]
        public readonly string $city,

        #[Required]
        #[Regex('/^\d{5}$/')]
        public readonly string $zipCode,
    ) {
    }
}

class ValidationUserWithAddressDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        public readonly ValidationAddressDto $address,
    ) {
    }
}

class ValidationCompanyDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Min(3)]
        public readonly string $name,

        #[Required]
        public readonly ValidationAddressDto $mainAddress,

        public readonly ?ValidationAddressDto $billingAddress = null,
    ) {
    }
}

describe('SimpleDto Validation', function(): void {
    describe('Auto Rule Inferring', function(): void {
        it('infers required rule from non-nullable type', function(): void {
            $rules = TestDtoWithRequiredString::getAllRules();

            expect($rules)->toHaveKey('name')
                ->and($rules['name'])->toContain('required')
                ->and($rules['name'])->toContain('string');
        });

        it('does not infer required rule from nullable type', function(): void {
            $rules = TestDtoWithNullableString::getAllRules();

            expect($rules)->toHaveKey('name')
                ->and($rules['name'])->not->toContain('required')
                ->and($rules['name'])->toContain('string');
        });

        it('infers integer rule from int type', function(): void {
            $rules = TestDtoWithInt::getAllRules();

            expect($rules)->toHaveKey('age')
                ->and($rules['age'])->toContain('integer');
        });

        it('infers numeric rule from float type', function(): void {
            $rules = TestDtoWithFloat::getAllRules();

            expect($rules)->toHaveKey('price')
                ->and($rules['price'])->toContain('numeric');
        });

        it('infers boolean rule from bool type', function(): void {
            $rules = TestDtoWithBool::getAllRules();

            expect($rules)->toHaveKey('active')
                ->and($rules['active'])->toContain('boolean');
        });

        it('infers array rule from array type', function(): void {
            $rules = TestDtoWithArray::getAllRules();

            expect($rules)->toHaveKey('tags')
                ->and($rules['tags'])->toContain('array');
        });
    });

    describe('Validation Attributes', function(): void {
        it('applies Required attribute', function(): void {
            $rules = TestDtoWithRequiredAttribute::getAllRules();

            expect($rules)->toHaveKey('name')
                ->and($rules['name'])->toContain('required');
        });

        it('applies Email attribute', function(): void {
            $rules = TestDtoWithEmailAttribute::getAllRules();

            expect($rules)->toHaveKey('email')
                ->and($rules['email'])->toContain('email');
        });

        it('applies Min attribute', function(): void {
            $rules = TestDtoWithMinAttribute::getAllRules();

            expect($rules)->toHaveKey('name')
                ->and($rules['name'])->toContain('min:3');
        });

        it('applies Max attribute', function(): void {
            $rules = TestDtoWithMaxAttribute::getAllRules();

            expect($rules)->toHaveKey('name')
                ->and($rules['name'])->toContain('max:255');
        });

        it('applies Between attribute', function(): void {
            $rules = TestDtoWithBetweenAttribute::getAllRules();

            expect($rules)->toHaveKey('age')
                ->and($rules['age'])->toContain('between:18,120');
        });

        it('applies Url attribute', function(): void {
            $rules = TestDtoWithUrlAttribute::getAllRules();

            expect($rules)->toHaveKey('website')
                ->and($rules['website'])->toContain('url');
        });

        it('applies Uuid attribute', function(): void {
            $rules = TestDtoWithUuidAttribute::getAllRules();

            expect($rules)->toHaveKey('id')
                ->and($rules['id'])->toContain('uuid');
        });

        it('applies In attribute', function(): void {
            $rules = TestDtoWithInAttribute::getAllRules();

            expect($rules)->toHaveKey('role')
                ->and($rules['role'])->toContain('in:admin,user,guest');
        });

        it('applies Regex attribute', function(): void {
            $rules = TestDtoWithRegexAttribute::getAllRules();

            expect($rules)->toHaveKey('code')
                ->and($rules['code'])->toContain('regex:/^[A-Z]{2}\d{4}$/');
        });

        it('applies multiple attributes', function(): void {
            $rules = TestDtoWithMultipleAttributes::getAllRules();

            expect($rules)->toHaveKey('email')
                ->and($rules['email'])->toContain('required')
                ->and($rules['email'])->toContain('email')
                ->and($rules['email'])->toContain('max:255');
        });

        it('applies NotIn attribute', function(): void {
            $rules = TestDtoWithNotInAttribute::getAllRules();

            expect($rules)->toHaveKey('username')
                ->and($rules['username'])->toContain('not_in:admin,root,system');
        });

        it('applies Confirmed attribute', function(): void {
            $rules = TestDtoWithConfirmedAttribute::getAllRules();

            expect($rules)->toHaveKey('password')
                ->and($rules['password'])->toContain('confirmed');
        });

        it('applies ConfirmedBy attribute', function(): void {
            $rules = TestDtoWithConfirmedByAttribute::getAllRules();

            expect($rules)->toHaveKey('password')
                ->and($rules['password'])->toContain('same:passwordVerification');
        });
    });

    describe('Custom Rules', function(): void {
        it('merges custom rules with inferred rules', function(): void {
            $rules = TestDtoWithCustomRules::getAllRules();

            expect($rules)->toHaveKey('email')
                ->and($rules['email'])->toContain('required')
                ->and($rules['email'])->toContain('string')
                ->and($rules['email'])->toContain('email')
                ->and($rules['email'])->toContain('max:255');
        });
    });

    describe('Rules Caching', function(): void {
        it('caches rules for performance', function(): void {
            $rules1 = TestDtoWithMultipleAttributes::getAllRules();
            $rules2 = TestDtoWithMultipleAttributes::getAllRules();

            expect($rules1)->toBe($rules2);
        });

        it('can clear rules cache', function(): void {
            TestDtoWithRequiredString::getAllRules();
            TestDtoWithRequiredString::clearRulesCache();

            expect(true)->toBeTrue(); // Just test that it doesn't throw
        });
    });

    describe('Nested Validation', function(): void {
        it('validates nested Dtos automatically', function(): void {
            $rules = ValidationUserWithValidationAddressDto::getAllRules();

            expect($rules)->toHaveKey('email')
                ->and($rules)->toHaveKey('address')
                ->and($rules['address'])->toContain('required')
                ->and($rules['address'])->toContain('array')
                ->and($rules)->toHaveKey('address.street')
                ->and($rules['address.street'])->toContain('required')
                ->and($rules['address.street'])->toContain('string')
                ->and($rules['address.street'])->toContain('min:3')
                ->and($rules)->toHaveKey('address.city')
                ->and($rules['address.city'])->toContain('required')
                ->and($rules)->toHaveKey('address.zipCode')
                ->and($rules['address.zipCode'])->toContain('regex:/^\d{5}$/');
        });

        it('validates multiple nested Dtos', function(): void {
            $rules = ValidationCompanyDto::getAllRules();

            expect($rules)->toHaveKey('name')
                ->and($rules)->toHaveKey('mainAddress')
                ->and($rules['mainAddress'])->toContain('required')
                ->and($rules['mainAddress'])->toContain('array')
                ->and($rules)->toHaveKey('mainAddress.street')
                ->and($rules)->toHaveKey('billingAddress')
                ->and($rules['billingAddress'])->toContain('array')
                ->and($rules['billingAddress'])->not->toContain('required')
                ->and($rules)->toHaveKey('billingAddress.street');
        });

        it('handles deeply nested Dtos', function(): void {
            $rules = ValidationUserWithValidationAddressDto::getAllRules();

            // Check that nested rules are properly namespaced
            $nestedKeys = array_filter(array_keys($rules), fn(string $key): bool => str_starts_with($key, 'address.'));

            expect($nestedKeys)->toHaveCount(3)
                ->and($nestedKeys)->toContain('address.street')
                ->and($nestedKeys)->toContain('address.city')
                ->and($nestedKeys)->toContain('address.zipCode');
        });
    });

    describe('Validation Edge Cases', function(): void {
        it('handles nullable with default value', function(): void {
            $dto = new class() extends SimpleDto {
                public function __construct(
                    public readonly ?string $name = null,
                ) {
                }
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('name')
                ->and($rules['name'])->not->toContain('required')
                ->and($rules['name'])->toContain('string');
        });

        it('handles non-nullable with default value', function(): void {
            $dto = new class() extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'default',
                ) {
                }
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('name')
                ->and($rules['name'])->not->toContain('required')
                ->and($rules['name'])->toContain('string');
        });

        it('handles Min with zero', function(): void {
            $dto = new class(0) extends SimpleDto {
                public function __construct(
                    #[Min(0)]
                    public readonly int $count,
                ) {
                }
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('count')
                ->and($rules['count'])->toContain('min:0');
        });

        it('handles Max with zero', function(): void {
            $dto = new class(0) extends SimpleDto {
                public function __construct(
                    #[Max(0)]
                    public readonly int $count,
                ) {
                }
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('count')
                ->and($rules['count'])->toContain('max:0');
        });

        it('handles negative Min value', function(): void {
            $dto = new class(-100) extends SimpleDto {
                public function __construct(
                    #[Min(-100)]
                    public readonly int $temperature,
                ) {
                }
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('temperature')
                ->and($rules['temperature'])->toContain('min:-100');
        });

        it('handles Between with negative values', function(): void {
            $dto = new class(-50) extends SimpleDto {
                public function __construct(
                    #[Between(-100, -10)]
                    public readonly int $temperature,
                ) {
                }
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('temperature')
                ->and($rules['temperature'])->toContain('between:-100,-10');
        });

        it('handles In with single value', function(): void {
            $dto = new class('active') extends SimpleDto {
                public function __construct(
                    #[In(['active'])]
                    public readonly string $status,
                ) {
                }
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('status')
                ->and($rules['status'])->toContain('in:active');
        });

        it('handles NotIn with many values', function(): void {
            $dto = new class('user') extends SimpleDto {
                public function __construct(
                    #[NotIn(['admin', 'root', 'system', 'administrator', 'superuser'])]
                    public readonly string $username,
                ) {
                }
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('username')
                ->and($rules['username'])->toContain('not_in:admin,root,system,administrator,superuser');
        });

        it('handles multiple attributes on single property', function(): void {
            $dto = new class('test@example.com') extends SimpleDto {
                public function __construct(
                    #[Required]
                    #[Email]
                    #[Min(5)]
                    #[Max(255)]
                    public readonly string $email,
                ) {
                }
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('email')
                ->and($rules['email'])->toContain('required')
                ->and($rules['email'])->toContain('email')
                ->and($rules['email'])->toContain('min:5')
                ->and($rules['email'])->toContain('max:255')
                ->and(count($rules['email']))->toBeGreaterThanOrEqual(5);
        });

        it('handles empty custom rules', function(): void {
            $dto = new class('test') extends SimpleDto {
                public function __construct(
                    public readonly string $name,
                ) {
                }

                protected function rules(): array
                {
                    return [];
                }
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('name')
                ->and($rules['name'])->toContain('required')
                ->and($rules['name'])->toContain('string');
        });

        it('merges custom rules with inferred rules', function(): void {
            $dto = new class('test@example.com') extends SimpleDto {
                public function __construct(
                    public readonly string $email,
                ) {
                }

                protected function rules(): array
                {
                    return [
                        'email' => ['email', 'max:255'],
                    ];
                }
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('email')
                ->and($rules['email'])->toContain('required')
                ->and($rules['email'])->toContain('string')
                ->and($rules['email'])->toContain('email')
                ->and($rules['email'])->toContain('max:255');
        });

        it('handles nullable nested Dto', function(): void {
            $dto = new class('test') extends SimpleDto {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    #[Required]
                    public readonly string $name,
                    public readonly ?array $metadata = null,
                ) {
                }
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('name')
                ->and($rules['name'])->toContain('required')
                ->and($rules)->toHaveKey('metadata')
                ->and($rules['metadata'])->not->toContain('required');
        });

        it('handles empty Dto', function(): void {
            $dto = new class extends SimpleDto
            {
            };

            $rules = $dto::getAllRules();
            expect($rules)->toBeArray()->toBeEmpty();
        });

        it('caches rules per Dto class', function(): void {
            $dto1 = new class('test') extends SimpleDto {
                public function __construct(
                    #[Required]
                    public readonly string $name,
                ) {
                }
            };

            $dto2 = new class('test@example.com') extends SimpleDto {
                public function __construct(
                    #[Required]
                    #[Email]
                    public readonly string $email,
                ) {
                }
            };

            $rules1 = $dto1::getAllRules();
            $rules2 = $dto2::getAllRules();

            expect($rules1)->toHaveKey('name')
                ->and($rules2)->toHaveKey('email')
                ->and($rules1)->not->toHaveKey('email')
                ->and($rules2)->not->toHaveKey('name');
        });
    });
})->group('laravel');
