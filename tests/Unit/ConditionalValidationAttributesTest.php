<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\In;
use event4u\DataHelpers\SimpleDTO\Attributes\RequiredIf;
use event4u\DataHelpers\SimpleDTO\Attributes\RequiredUnless;
use event4u\DataHelpers\SimpleDTO\Attributes\RequiredWith;
use event4u\DataHelpers\SimpleDTO\Attributes\RequiredWithout;
use event4u\DataHelpers\SimpleDTO\Attributes\Sometimes;
use event4u\DataHelpers\SimpleDTO\Attributes\Nullable;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;

describe('Conditional Validation Attributes', function () {
    describe('RequiredIf Attribute', function () {
        it('generates required_if rule', function () {
            $attribute = new RequiredIf('shippingMethod', 'delivery');
            $rule = $attribute->rule();

            expect($rule)->toBe('required_if:shippingMethod,delivery');
        });

        it('handles boolean values', function () {
            $attribute = new RequiredIf('isActive', true);
            $rule = $attribute->rule();

            expect($rule)->toBe('required_if:isActive,true');
        });

        it('validates DTO with RequiredIf', function () {
            $dto = new class('delivery', '123 Main St') {
                use SimpleDTOTrait;

                public function __construct(
                    #[Required]
                    #[In(['pickup', 'delivery'])]
                    public readonly string $shippingMethod,

                    #[RequiredIf('shippingMethod', 'delivery')]
                    public readonly ?string $address = null,
                ) {}
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('address')
                ->and($rules['address'])->toContain('required_if:shippingMethod,delivery');
        });
    });

    describe('RequiredUnless Attribute', function () {
        it('generates required_unless rule', function () {
            $attribute = new RequiredUnless('paymentMethod', 'free');
            $rule = $attribute->rule();

            expect($rule)->toBe('required_unless:paymentMethod,free');
        });

        it('validates DTO with RequiredUnless', function () {
            $dto = new class('card', 'VISA-1234') {
                use SimpleDTOTrait;

                public function __construct(
                    #[Required]
                    #[In(['card', 'cash', 'free'])]
                    public readonly string $paymentMethod,

                    #[RequiredUnless('paymentMethod', 'free')]
                    public readonly ?string $paymentDetails = null,
                ) {}
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('paymentDetails')
                ->and($rules['paymentDetails'])->toContain('required_unless:paymentMethod,free');
        });
    });

    describe('RequiredWith Attribute', function () {
        it('generates required_with rule', function () {
            $attribute = new RequiredWith(['phone', 'email']);
            $rule = $attribute->rule();

            expect($rule)->toBe('required_with:phone,email');
        });

        it('validates DTO with RequiredWith', function () {
            $dto = new class('555-1234', 'mobile') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly ?string $phone = null,
                    public readonly ?string $email = null,

                    #[RequiredWith(['phone', 'email'])]
                    public readonly ?string $contactPreference = null,
                ) {}
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('contactPreference')
                ->and($rules['contactPreference'])->toContain('required_with:phone,email');
        });
    });

    describe('RequiredWithout Attribute', function () {
        it('generates required_without rule', function () {
            $attribute = new RequiredWithout(['phone']);
            $rule = $attribute->rule();

            expect($rule)->toBe('required_without:phone');
        });

        it('validates DTO with RequiredWithout', function () {
            $dto = new class('test@example.com') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly ?string $phone = null,

                    #[RequiredWithout(['phone'])]
                    public readonly ?string $email = null,
                ) {}
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('email')
                ->and($rules['email'])->toContain('required_without:phone');
        });
    });

    describe('Sometimes Attribute', function () {
        it('generates sometimes rule', function () {
            $attribute = new Sometimes();
            $rule = $attribute->rule();

            expect($rule)->toBe('sometimes');
        });

        it('validates DTO with Sometimes', function () {
            $dto = new class('test@example.com') {
                use SimpleDTOTrait;

                public function __construct(
                    #[Sometimes]
                    #[Email]
                    public readonly ?string $email = null,

                    #[Sometimes]
                    #[Min(8)]
                    public readonly ?string $password = null,
                ) {}
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('email')
                ->and($rules['email'])->toContain('sometimes')
                ->and($rules['email'])->toContain('email');
        });
    });

    describe('Nullable Attribute', function () {
        it('generates nullable rule', function () {
            $attribute = new Nullable();
            $rule = $attribute->rule();

            expect($rule)->toBe('nullable');
        });

        it('validates DTO with Nullable', function () {
            $dto = new class(null, null) {
                use SimpleDTOTrait;

                public function __construct(
                    #[Nullable]
                    #[Email]
                    public readonly ?string $email = null,

                    #[Nullable]
                    public readonly ?string $website = null,
                ) {}
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('email')
                ->and($rules['email'])->toContain('nullable')
                ->and($rules['email'])->toContain('email');
        });
    });

    describe('Complex Conditional Scenarios', function () {
        it('handles multiple conditional rules on same property', function () {
            $dto = new class('delivery', '123 Main St', 'card', 'VISA-1234') {
                use SimpleDTOTrait;

                public function __construct(
                    #[Required]
                    public readonly string $shippingMethod,

                    #[RequiredIf('shippingMethod', 'delivery')]
                    public readonly ?string $address = null,

                    #[Required]
                    public readonly string $paymentMethod,

                    #[RequiredUnless('paymentMethod', 'free')]
                    public readonly ?string $paymentDetails = null,
                ) {}
            };

            $rules = $dto::getAllRules();
            expect($rules)->toHaveKey('address')
                ->and($rules)->toHaveKey('paymentDetails');
        });
    });
});

