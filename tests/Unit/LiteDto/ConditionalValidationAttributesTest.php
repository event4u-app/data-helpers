<?php

declare(strict_types=1);

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Required;
use event4u\DataHelpers\LiteDto\Attributes\Validation\RequiredIf;
use event4u\DataHelpers\LiteDto\Attributes\Validation\RequiredUnless;
use event4u\DataHelpers\LiteDto\Attributes\Validation\RequiredWith;
use event4u\DataHelpers\LiteDto\Attributes\Validation\RequiredWithout;
use event4u\DataHelpers\LiteDto\LiteDto;

// Test DTOs
class ConditionalValidationTestRequiredIfDto extends LiteDto
{
    public function __construct(
        #[Required]
        public readonly string $shippingMethod,
        #[RequiredIf('shippingMethod', 'delivery')]
        public readonly ?string $address = null,
    ) {}
}

class ConditionalValidationTestRequiredUnlessDto extends LiteDto
{
    public function __construct(
        #[Required]
        public readonly string $paymentMethod,
        #[RequiredUnless('paymentMethod', 'free')]
        public readonly ?string $paymentDetails = null,
    ) {}
}

class ConditionalValidationTestRequiredWithDto extends LiteDto
{
    public function __construct(
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        #[RequiredWith(['phone', 'email'])]
        public readonly ?string $contactPreference = null,
    ) {}
}

class ConditionalValidationTestRequiredWithoutDto extends LiteDto
{
    public function __construct(
        public readonly ?string $phone = null,
        #[RequiredWithout(['phone'])]
        public readonly ?string $email = null,
    ) {}
}

class ConditionalValidationTestMultipleDto extends LiteDto
{
    public function __construct(
        public readonly string $type,
        #[RequiredIf('type', 'premium')]
        public readonly ?string $premiumCode = null,
        #[RequiredUnless('type', 'free')]
        public readonly ?string $paymentMethod = null,
    ) {}
}

describe('LiteDto Conditional Validation Attributes', function(): void {
    describe('RequiredIf Attribute', function(): void {
        it('validates when condition is met and field is present', function(): void {
            $result = ConditionalValidationTestRequiredIfDto::validate([
                'shippingMethod' => 'delivery',
                'address' => '123 Main St',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when condition is met but field is missing', function(): void {
            $result = ConditionalValidationTestRequiredIfDto::validate([
                'shippingMethod' => 'delivery',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('address'))->toBeTrue();
        });

        it('validates when condition is not met and field is missing', function(): void {
            $result = ConditionalValidationTestRequiredIfDto::validate([
                'shippingMethod' => 'pickup',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('validates when condition is not met and field is present', function(): void {
            $result = ConditionalValidationTestRequiredIfDto::validate([
                'shippingMethod' => 'pickup',
                'address' => '123 Main St',
            ]);
            expect($result->isValid())->toBeTrue();
        });
    });

    describe('RequiredUnless Attribute', function(): void {
        it('validates when condition is met and field is missing', function(): void {
            $result = ConditionalValidationTestRequiredUnlessDto::validate([
                'paymentMethod' => 'free',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('validates when condition is met and field is present', function(): void {
            $result = ConditionalValidationTestRequiredUnlessDto::validate([
                'paymentMethod' => 'free',
                'paymentDetails' => 'N/A',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when condition is not met and field is missing', function(): void {
            $result = ConditionalValidationTestRequiredUnlessDto::validate([
                'paymentMethod' => 'card',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('paymentDetails'))->toBeTrue();
        });

        it('validates when condition is not met and field is present', function(): void {
            $result = ConditionalValidationTestRequiredUnlessDto::validate([
                'paymentMethod' => 'card',
                'paymentDetails' => '1234-5678-9012-3456',
            ]);
            expect($result->isValid())->toBeTrue();
        });
    });

    describe('RequiredWith Attribute', function(): void {
        it('validates when no trigger fields are present', function(): void {
            $result = ConditionalValidationTestRequiredWithDto::validate([]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when trigger field is present but required field is missing', function(): void {
            $result = ConditionalValidationTestRequiredWithDto::validate([
                'phone' => '+49123456789',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('contactPreference'))->toBeTrue();
        });

        it('validates when trigger field and required field are both present', function(): void {
            $result = ConditionalValidationTestRequiredWithDto::validate([
                'phone' => '+49123456789',
                'contactPreference' => 'phone',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when any trigger field is present but required field is missing', function(): void {
            $result = ConditionalValidationTestRequiredWithDto::validate([
                'email' => 'test@example.com',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('contactPreference'))->toBeTrue();
        });

        it('validates when multiple trigger fields are present with required field', function(): void {
            $result = ConditionalValidationTestRequiredWithDto::validate([
                'phone' => '+49123456789',
                'email' => 'test@example.com',
                'contactPreference' => 'email',
            ]);
            expect($result->isValid())->toBeTrue();
        });
    });

    describe('RequiredWithout Attribute', function(): void {
        it('validates when trigger field is present', function(): void {
            $result = ConditionalValidationTestRequiredWithoutDto::validate([
                'phone' => '+49123456789',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when trigger field is missing and required field is missing', function(): void {
            $result = ConditionalValidationTestRequiredWithoutDto::validate([]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('email'))->toBeTrue();
        });

        it('validates when trigger field is missing but required field is present', function(): void {
            $result = ConditionalValidationTestRequiredWithoutDto::validate([
                'email' => 'test@example.com',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('validates when both fields are present', function(): void {
            $result = ConditionalValidationTestRequiredWithoutDto::validate([
                'phone' => '+49123456789',
                'email' => 'test@example.com',
            ]);
            expect($result->isValid())->toBeTrue();
        });
    });

    describe('Multiple Conditional Attributes', function(): void {
        it('validates when type is free', function(): void {
            $result = ConditionalValidationTestMultipleDto::validate([
                'type' => 'free',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when type is premium but premiumCode is missing', function(): void {
            $result = ConditionalValidationTestMultipleDto::validate([
                'type' => 'premium',
                'paymentMethod' => 'card',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('premiumCode'))->toBeTrue();
        });

        it('fails when type is standard but paymentMethod is missing', function(): void {
            $result = ConditionalValidationTestMultipleDto::validate([
                'type' => 'standard',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('paymentMethod'))->toBeTrue();
        });

        it('validates when type is premium with all required fields', function(): void {
            $result = ConditionalValidationTestMultipleDto::validate([
                'type' => 'premium',
                'premiumCode' => 'PREMIUM123',
                'paymentMethod' => 'card',
            ]);
            expect($result->isValid())->toBeTrue();
        });
    });

    describe('validateInstance() with Conditional Attributes', function(): void {
        it('validates instance with RequiredIf', function(): void {
            $dto = ConditionalValidationTestRequiredIfDto::from([
                'shippingMethod' => 'delivery',
                'address' => '123 Main St',
            ]);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails instance validation when condition is met but field is missing', function(): void {
            $dto = ConditionalValidationTestRequiredIfDto::from([
                'shippingMethod' => 'delivery',
            ]);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('address'))->toBeTrue();
        });
    });

    describe('validateAndCreate() with Conditional Attributes', function(): void {
        it('creates DTO when validation passes', function(): void {
            $dto = ConditionalValidationTestRequiredIfDto::validateAndCreate([
                'shippingMethod' => 'pickup',
            ]);
            expect($dto->shippingMethod)->toBe('pickup');
            expect($dto->address)->toBeNull();
        });

        it('throws exception when validation fails', function(): void {
            expect(
                fn(): \ConditionalValidationTestRequiredIfDto => ConditionalValidationTestRequiredIfDto::validateAndCreate([
                    
                'shippingMethod' => 'delivery',
            
                
                ])
            )->toThrow(ValidationException::class);
        });
    });
});
