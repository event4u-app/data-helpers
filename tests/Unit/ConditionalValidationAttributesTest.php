<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\In;
use event4u\DataHelpers\SimpleDto\Attributes\Min;
use event4u\DataHelpers\SimpleDto\Attributes\Nullable;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\RequiredIf;
use event4u\DataHelpers\SimpleDto\Attributes\RequiredUnless;
use event4u\DataHelpers\SimpleDto\Attributes\RequiredWith;
use event4u\DataHelpers\SimpleDto\Attributes\RequiredWithout;
use event4u\DataHelpers\SimpleDto\Attributes\Sometimes;

// Test Dtos
class ConditionalValidationTestDto1 extends SimpleDto
{
    public function __construct(
        #[Required]
        #[In(['pickup', 'delivery'])]
        public readonly string $shippingMethod,
        #[RequiredIf('shippingMethod', 'delivery')]
        public readonly ?string $address = null,
    ) {}
}

class ConditionalValidationTestDto2 extends SimpleDto
{
    public function __construct(
        #[Required]
        #[In(['card', 'cash', 'free'])]
        public readonly string $paymentMethod,
        #[RequiredUnless('paymentMethod', 'free')]
        public readonly ?string $paymentDetails = null,
    ) {}
}

class ConditionalValidationTestDto3 extends SimpleDto
{
    public function __construct(
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        #[RequiredWith(['phone', 'email'])]
        public readonly ?string $contactPreference = null,
    ) {}
}

class ConditionalValidationTestDto4 extends SimpleDto
{
    public function __construct(
        public readonly ?string $phone = null,
        #[RequiredWithout(['phone'])]
        public readonly ?string $email = null,
    ) {}
}

class ConditionalValidationTestDto5 extends SimpleDto
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

class ConditionalValidationTestDto6 extends SimpleDto
{
    public function __construct(
        #[Nullable]
        #[Email]
        public readonly ?string $email = null,
        #[Nullable]
        public readonly ?string $website = null,
    ) {}
}

class ConditionalValidationTestDto7 extends SimpleDto
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

        it('validates Dto with RequiredIf', function(): void {
            $dto = new ConditionalValidationTestDto1('delivery', '123 Main St');

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

        it('validates Dto with RequiredUnless', function(): void {
            $dto = new ConditionalValidationTestDto2('card', 'VISA-1234');

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

        it('validates Dto with RequiredWith', function(): void {
            $dto = new ConditionalValidationTestDto3('555-1234', 'mobile');

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

        it('validates Dto with RequiredWithout', function(): void {
            $dto = new ConditionalValidationTestDto4(null, 'test@example.com');

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

        it('validates Dto with Sometimes', function(): void {
            $dto = new ConditionalValidationTestDto5('test@example.com');

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

        it('validates Dto with Nullable', function(): void {
            $dto = new ConditionalValidationTestDto6(null, null);

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('email')
                ->and($rules['email'])->toContain('nullable')
                ->and($rules['email'])->toContain('email');
        });
    });

    describe('Complex Conditional Scenarios', function(): void {
        it('handles multiple conditional rules on same property', function(): void {
            $dto = new ConditionalValidationTestDto7('delivery', 'card', '123 Main St', 'VISA-1234');

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('address')
                ->and($rules)->toHaveKey('paymentDetails');
        });
    });
});
