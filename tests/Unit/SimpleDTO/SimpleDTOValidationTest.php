<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Between;
use event4u\DataHelpers\SimpleDTO\Attributes\Confirmed;
use event4u\DataHelpers\SimpleDTO\Attributes\ConfirmedBy;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\In;
use event4u\DataHelpers\SimpleDTO\Attributes\Max;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\NotIn;
use event4u\DataHelpers\SimpleDTO\Attributes\Regex;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Url;
use event4u\DataHelpers\SimpleDTO\Attributes\Uuid;

// Test DTOs
class TestDTOWithRequiredString extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}

class TestDTOWithNullableString extends SimpleDTO
{
    public function __construct(
        public readonly ?string $name = null,
    ) {
    }
}

class TestDTOWithInt extends SimpleDTO
{
    public function __construct(
        public readonly int $age,
    ) {
    }
}

class TestDTOWithFloat extends SimpleDTO
{
    public function __construct(
        public readonly float $price,
    ) {
    }
}

class TestDTOWithBool extends SimpleDTO
{
    public function __construct(
        public readonly bool $active,
    ) {
    }
}

class TestDTOWithArray extends SimpleDTO
{
    public function __construct(
        public readonly array $tags,
    ) {
    }
}

class TestDTOWithEmailAttribute extends SimpleDTO
{
    public function __construct(
        #[Email]
        public readonly string $email,
    ) {
    }
}

class TestDTOWithMinAttribute extends SimpleDTO
{
    public function __construct(
        #[Min(3)]
        public readonly string $name,
    ) {
    }
}

class TestDTOWithMaxAttribute extends SimpleDTO
{
    public function __construct(
        #[Max(255)]
        public readonly string $name,
    ) {
    }
}

class TestDTOWithBetweenAttribute extends SimpleDTO
{
    public function __construct(
        #[Between(18, 120)]
        public readonly int $age,
    ) {
    }
}

class TestDTOWithUrlAttribute extends SimpleDTO
{
    public function __construct(
        #[Url]
        public readonly string $website,
    ) {
    }
}

class TestDTOWithUuidAttribute extends SimpleDTO
{
    public function __construct(
        #[Uuid]
        public readonly string $id,
    ) {
    }
}

class TestDTOWithInAttribute extends SimpleDTO
{
    public function __construct(
        #[In(['admin', 'user', 'guest'])]
        public readonly string $role,
    ) {
    }
}

class TestDTOWithRegexAttribute extends SimpleDTO
{
    public function __construct(
        #[Regex('/^[A-Z]{2}\d{4}$/')]
        public readonly string $code,
    ) {
    }
}

class TestDTOWithMultipleAttributes extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        #[Max(255)]
        public readonly string $email,
    ) {
    }
}

class TestDTOWithCustomRules extends SimpleDTO
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

class TestDTOWithRequiredAttribute extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly ?string $name = null,
    ) {
    }
}

class TestDTOWithNotInAttribute extends SimpleDTO
{
    public function __construct(
        #[NotIn(['admin', 'root', 'system'])]
        public readonly string $username,
    ) {
    }
}

class TestDTOWithConfirmedAttribute extends SimpleDTO
{
    public function __construct(
        #[Confirmed]
        public readonly string $password,
        public readonly string $password_confirmed,
    ) {
    }
}

class TestDTOWithConfirmedByAttribute extends SimpleDTO
{
    public function __construct(
        #[ConfirmedBy('passwordVerification')]
        public readonly string $password,
        public readonly string $passwordVerification,
    ) {
    }
}

// Nested DTOs for testing
class AddressDTO extends SimpleDTO
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

class UserWithAddressDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        public readonly AddressDTO $address,
    ) {
    }
}

class CompanyDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Min(3)]
        public readonly string $name,

        #[Required]
        public readonly AddressDTO $mainAddress,

        public readonly ?AddressDTO $billingAddress = null,
    ) {
    }
}

describe('SimpleDTO Validation', function(): void {
    describe('Auto Rule Inferring', function(): void {
        it('infers required rule from non-nullable type', function(): void {
            $rules = TestDTOWithRequiredString::getAllRules();

            expect($rules)->toHaveKey('name')
                ->and($rules['name'])->toContain('required')
                ->and($rules['name'])->toContain('string');
        });

        it('does not infer required rule from nullable type', function(): void {
            $rules = TestDTOWithNullableString::getAllRules();

            expect($rules)->toHaveKey('name')
                ->and($rules['name'])->not->toContain('required')
                ->and($rules['name'])->toContain('string');
        });

        it('infers integer rule from int type', function(): void {
            $rules = TestDTOWithInt::getAllRules();

            expect($rules)->toHaveKey('age')
                ->and($rules['age'])->toContain('integer');
        });

        it('infers numeric rule from float type', function(): void {
            $rules = TestDTOWithFloat::getAllRules();

            expect($rules)->toHaveKey('price')
                ->and($rules['price'])->toContain('numeric');
        });

        it('infers boolean rule from bool type', function(): void {
            $rules = TestDTOWithBool::getAllRules();

            expect($rules)->toHaveKey('active')
                ->and($rules['active'])->toContain('boolean');
        });

        it('infers array rule from array type', function(): void {
            $rules = TestDTOWithArray::getAllRules();

            expect($rules)->toHaveKey('tags')
                ->and($rules['tags'])->toContain('array');
        });
    });

    describe('Validation Attributes', function(): void {
        it('applies Required attribute', function(): void {
            $rules = TestDTOWithRequiredAttribute::getAllRules();

            expect($rules)->toHaveKey('name')
                ->and($rules['name'])->toContain('required');
        });

        it('applies Email attribute', function(): void {
            $rules = TestDTOWithEmailAttribute::getAllRules();

            expect($rules)->toHaveKey('email')
                ->and($rules['email'])->toContain('email');
        });

        it('applies Min attribute', function(): void {
            $rules = TestDTOWithMinAttribute::getAllRules();

            expect($rules)->toHaveKey('name')
                ->and($rules['name'])->toContain('min:3');
        });

        it('applies Max attribute', function(): void {
            $rules = TestDTOWithMaxAttribute::getAllRules();

            expect($rules)->toHaveKey('name')
                ->and($rules['name'])->toContain('max:255');
        });

        it('applies Between attribute', function(): void {
            $rules = TestDTOWithBetweenAttribute::getAllRules();

            expect($rules)->toHaveKey('age')
                ->and($rules['age'])->toContain('between:18,120');
        });

        it('applies Url attribute', function(): void {
            $rules = TestDTOWithUrlAttribute::getAllRules();

            expect($rules)->toHaveKey('website')
                ->and($rules['website'])->toContain('url');
        });

        it('applies Uuid attribute', function(): void {
            $rules = TestDTOWithUuidAttribute::getAllRules();

            expect($rules)->toHaveKey('id')
                ->and($rules['id'])->toContain('uuid');
        });

        it('applies In attribute', function(): void {
            $rules = TestDTOWithInAttribute::getAllRules();

            expect($rules)->toHaveKey('role')
                ->and($rules['role'])->toContain('in:admin,user,guest');
        });

        it('applies Regex attribute', function(): void {
            $rules = TestDTOWithRegexAttribute::getAllRules();

            expect($rules)->toHaveKey('code')
                ->and($rules['code'])->toContain('regex:/^[A-Z]{2}[0-9]{4}$/');
        });

        it('applies multiple attributes', function(): void {
            $rules = TestDTOWithMultipleAttributes::getAllRules();

            expect($rules)->toHaveKey('email')
                ->and($rules['email'])->toContain('required')
                ->and($rules['email'])->toContain('email')
                ->and($rules['email'])->toContain('max:255');
        });

        it('applies NotIn attribute', function(): void {
            $rules = TestDTOWithNotInAttribute::getAllRules();

            expect($rules)->toHaveKey('username')
                ->and($rules['username'])->toContain('not_in:admin,root,system');
        });

        it('applies Confirmed attribute', function(): void {
            $rules = TestDTOWithConfirmedAttribute::getAllRules();

            expect($rules)->toHaveKey('password')
                ->and($rules['password'])->toContain('confirmed');
        });

        it('applies ConfirmedBy attribute', function(): void {
            $rules = TestDTOWithConfirmedByAttribute::getAllRules();

            expect($rules)->toHaveKey('password')
                ->and($rules['password'])->toContain('same:passwordVerification');
        });
    });

    describe('Custom Rules', function(): void {
        it('merges custom rules with inferred rules', function(): void {
            $rules = TestDTOWithCustomRules::getAllRules();

            expect($rules)->toHaveKey('email')
                ->and($rules['email'])->toContain('required')
                ->and($rules['email'])->toContain('string')
                ->and($rules['email'])->toContain('email')
                ->and($rules['email'])->toContain('max:255');
        });
    });

    describe('Rules Caching', function(): void {
        it('caches rules for performance', function(): void {
            $rules1 = TestDTOWithMultipleAttributes::getAllRules();
            $rules2 = TestDTOWithMultipleAttributes::getAllRules();

            expect($rules1)->toBe($rules2);
        });

        it('can clear rules cache', function(): void {
            TestDTOWithRequiredString::getAllRules();
            TestDTOWithRequiredString::clearRulesCache();

            expect(true)->toBeTrue(); // Just test that it doesn't throw
        });
    });

    describe('Nested Validation', function(): void {
        it('validates nested DTOs automatically', function(): void {
            $rules = UserWithAddressDTO::getAllRules();

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

        it('validates multiple nested DTOs', function(): void {
            $rules = CompanyDTO::getAllRules();

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

        it('handles deeply nested DTOs', function(): void {
            $rules = UserWithAddressDTO::getAllRules();

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
            $dto = new class() extends SimpleDTO {
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
            $dto = new class() extends SimpleDTO {
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
            $dto = new class(0) extends SimpleDTO {
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
            $dto = new class(0) extends SimpleDTO {
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
            $dto = new class(-100) extends SimpleDTO {
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
            $dto = new class(-50) extends SimpleDTO {
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
            $dto = new class('active') extends SimpleDTO {
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
            $dto = new class('user') extends SimpleDTO {
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
            $dto = new class('test@example.com') extends SimpleDTO {
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
            $dto = new class('test') extends SimpleDTO {
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
            $dto = new class('test@example.com') extends SimpleDTO {
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

        it('handles nullable nested DTO', function(): void {
            $dto = new class('test') extends SimpleDTO {
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

        it('handles empty DTO', function(): void {
            $dto = new class extends SimpleDTO
            {
            };

            $rules = $dto::getAllRules();
            expect($rules)->toBeArray()->toBeEmpty();
        });

        it('caches rules per DTO class', function(): void {
            $dto1 = new class('test') extends SimpleDTO {
                public function __construct(
                    #[Required]
                    public readonly string $name,
                ) {
                }
            };

            $dto2 = new class('test@example.com') extends SimpleDTO {
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

