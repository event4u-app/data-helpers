<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\In;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Nullable;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\RequiredIf;
use event4u\DataHelpers\SimpleDTO\Attributes\RequiredUnless;
use event4u\DataHelpers\SimpleDTO\Attributes\RequiredWith;
use event4u\DataHelpers\SimpleDTO\Attributes\RequiredWithout;
use event4u\DataHelpers\SimpleDTO\Attributes\Sometimes;

// Test DTOs
class ConditionalValidationTestDTO1 extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[In(['pickup', 'delivery'])]
        public readonly string $shippingMethod,
        #[RequiredIf('shippingMethod', 'delivery')]
        public readonly ?string $address = null,
    ) {}
}

class ConditionalValidationTestDTO2 extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[In(['card', 'cash', 'free'])]
        public readonly string $paymentMethod,
        #[RequiredUnless('paymentMethod', 'free')]
        public readonly ?string $paymentDetails = null,
    ) {}
}

class ConditionalValidationTestDTO3 extends SimpleDTO
{
    public function __construct(
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        #[RequiredWith(['phone', 'email'])]
        public readonly ?string $contactPreference = null,
    ) {}
}

class ConditionalValidationTestDTO4 extends SimpleDTO
{
    public function __construct(
        public readonly ?string $phone = null,
        #[RequiredWithout(['phone'])]
        public readonly ?string $email = null,
    ) {}
}

class ConditionalValidationTestDTO5 extends SimpleDTO
{
    public function __construct(
        #[Sometimes]
        #[Email]
        public readonly ?string $email = null,
        #[Sometimes]
        #[Min(8)]
        public readonly ?string $password = null,
    ) {}
}

class ConditionalValidationTestDTO6 extends SimpleDTO
{
    public function __construct(
        #[Nullable]
        #[Email]
        public readonly ?string $email = null,
        #[Nullable]
        public readonly ?string $website = null,
    ) {}
}

class ConditionalValidationTestDTO7 extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $shippingMethod,
        #[Required]
        public readonly string $paymentMethod,
        #[RequiredIf('shippingMethod', 'delivery')]
        public readonly ?string $address = null,
        #[RequiredUnless('paymentMethod', 'free')]
        public readonly ?string $paymentDetails = null,
    ) {}
}

describe('Conditional Validation Attributes', function(): void {
    describe('RequiredIf Attribute', function(): void {
        it('generates required_if rule', function(): void {
            $attribute = new RequiredIf('shippingMethod', 'delivery');
            $rule = $attribute->rule();

            expect($rule)->toBe('required_if:shippingMethod,delivery');
        });

        it('handles boolean values', function(): void {
            $attribute = new RequiredIf('isActive', true);
            $rule = $attribute->rule();

            expect($rule)->toBe('required_if:isActive,true');
        });

        it('validates DTO with RequiredIf', function(): void {
            $dto = new ConditionalValidationTestDTO1('delivery', '123 Main St');

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('address')
                ->and($rules['address'])->toContain('required_if:shippingMethod,delivery');
        });
    });

    describe('RequiredUnless Attribute', function(): void {
        it('generates required_unless rule', function(): void {
            $attribute = new RequiredUnless('paymentMethod', 'free');
            $rule = $attribute->rule();

            expect($rule)->toBe('required_unless:paymentMethod,free');
        });

        it('validates DTO with RequiredUnless', function(): void {
            $dto = new ConditionalValidationTestDTO2('card', 'VISA-1234');

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('paymentDetails')
                ->and($rules['paymentDetails'])->toContain('required_unless:paymentMethod,free');
        });
    });

    describe('RequiredWith Attribute', function(): void {
        it('generates required_with rule', function(): void {
            $attribute = new RequiredWith(['phone', 'email']);
            $rule = $attribute->rule();

            expect($rule)->toBe('required_with:phone,email');
        });

        it('validates DTO with RequiredWith', function(): void {
            $dto = new ConditionalValidationTestDTO3('555-1234', 'mobile');

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('contactPreference')
                ->and($rules['contactPreference'])->toContain('required_with:phone,email');
        });
    });

    describe('RequiredWithout Attribute', function(): void {
        it('generates required_without rule', function(): void {
            $attribute = new RequiredWithout(['phone']);
            $rule = $attribute->rule();

            expect($rule)->toBe('required_without:phone');
        });

        it('validates DTO with RequiredWithout', function(): void {
            $dto = new ConditionalValidationTestDTO4(null, 'test@example.com');

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('email')
                ->and($rules['email'])->toContain('required_without:phone');
        });
    });

    describe('Sometimes Attribute', function(): void {
        it('generates sometimes rule', function(): void {
            $attribute = new Sometimes();
            $rule = $attribute->rule();

            expect($rule)->toBe('sometimes');
        });

        it('validates DTO with Sometimes', function(): void {
            $dto = new ConditionalValidationTestDTO5('test@example.com');

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('email')
                ->and($rules['email'])->toContain('sometimes')
                ->and($rules['email'])->toContain('email');
        });
    });

    describe('Nullable Attribute', function(): void {
        it('generates nullable rule', function(): void {
            $attribute = new Nullable();
            $rule = $attribute->rule();

            expect($rule)->toBe('nullable');
        });

        it('validates DTO with Nullable', function(): void {
            $dto = new ConditionalValidationTestDTO6(null, null);

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('email')
                ->and($rules['email'])->toContain('nullable')
                ->and($rules['email'])->toContain('email');
        });
    });

    describe('Complex Conditional Scenarios', function(): void {
        it('handles multiple conditional rules on same property', function(): void {
            $dto = new ConditionalValidationTestDTO7('delivery', 'card', '123 Main St', 'VISA-1234');

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('address')
                ->and($rules)->toHaveKey('paymentDetails');
        });
    });
});
