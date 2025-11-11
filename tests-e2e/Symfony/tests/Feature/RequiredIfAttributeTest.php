<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\In;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\RequiredIf;

// Test DTOs
class SymfonyRequiredIfTestDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[In(['pickup', 'delivery'])]
        public readonly string $shippingMethod,
        #[RequiredIf('shippingMethod', 'delivery')]
        public readonly ?string $address = null,
    ) {}
}

class SymfonyRequiredIfBooleanTestDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly bool $needsShipping,
        #[RequiredIf('needsShipping', true)]
        public readonly ?string $shippingAddress = null,
    ) {}
}

class SymfonyRequiredIfNumericTestDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly int $quantity,
        #[RequiredIf('quantity', 10)]
        public readonly ?string $bulkDiscountCode = null,
    ) {}
}

describe('RequiredIf Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        // Restore error handler to prevent risky test warnings
        restore_error_handler();
        restore_exception_handler();
    });

    describe('SimpleDto validate() method with Symfony', function(): void {
        it('passes when field is required and provided', function(): void {
            $result = SymfonyRequiredIfTestDto::validate([
                'shippingMethod' => 'delivery',
                'address' => '123 Main St',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails when field is required but not provided', function(): void {
            $result = SymfonyRequiredIfTestDto::validate([
                'shippingMethod' => 'delivery',
                'address' => null,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('address'))->toBeTrue();
        });

        it('fails when field is required but empty string', function(): void {
            $result = SymfonyRequiredIfTestDto::validate([
                'shippingMethod' => 'delivery',
                'address' => '',
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('address'))->toBeTrue();
        });

        it('passes when field is not required', function(): void {
            $result = SymfonyRequiredIfTestDto::validate([
                'shippingMethod' => 'pickup',
                'address' => null,
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('passes when field is not required and provided anyway', function(): void {
            $result = SymfonyRequiredIfTestDto::validate([
                'shippingMethod' => 'pickup',
                'address' => '123 Main St',
            ]);

            expect($result->isValid())->toBeTrue();
        });
    });

    describe('Boolean conditions with Symfony', function(): void {
        it('passes when boolean condition is true and field is provided', function(): void {
            $result = SymfonyRequiredIfBooleanTestDto::validate([
                'needsShipping' => true,
                'shippingAddress' => '456 Oak Ave',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails when boolean condition is true but field is missing', function(): void {
            $result = SymfonyRequiredIfBooleanTestDto::validate([
                'needsShipping' => true,
                'shippingAddress' => null,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('shippingAddress'))->toBeTrue();
        });

        it('passes when boolean condition is false', function(): void {
            $result = SymfonyRequiredIfBooleanTestDto::validate([
                'needsShipping' => false,
                'shippingAddress' => null,
            ]);

            expect($result->isValid())->toBeTrue();
        });
    });

    describe('Numeric conditions with Symfony', function(): void {
        it('passes when numeric condition matches and field is provided', function(): void {
            $result = SymfonyRequiredIfNumericTestDto::validate([
                'quantity' => 10,
                'bulkDiscountCode' => 'BULK10',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails when numeric condition matches but field is missing', function(): void {
            $result = SymfonyRequiredIfNumericTestDto::validate([
                'quantity' => 10,
                'bulkDiscountCode' => null,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('bulkDiscountCode'))->toBeTrue();
        });

        it('passes when numeric condition does not match', function(): void {
            $result = SymfonyRequiredIfNumericTestDto::validate([
                'quantity' => 5,
                'bulkDiscountCode' => null,
            ]);

            expect($result->isValid())->toBeTrue();
        });
    });

    describe('Edge cases with Symfony', function(): void {
        it('handles zero as a valid value', function(): void {
            $result = SymfonyRequiredIfNumericTestDto::validate([
                'quantity' => 0,
                'bulkDiscountCode' => null,
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('handles empty string as different from null', function(): void {
            $result = SymfonyRequiredIfTestDto::validate([
                'shippingMethod' => 'delivery',
                'address' => '',
            ]);

            expect($result->isValid())->toBeFalse();
        });
    });

    describe('DTO creation with Symfony', function(): void {
        it('creates DTO when validation passes', function(): void {
            $dto = SymfonyRequiredIfTestDto::from([
                'shippingMethod' => 'delivery',
                'address' => '123 Main St',
            ]);

            expect($dto->shippingMethod)->toBe('delivery');
            expect($dto->address)->toBe('123 Main St');
        });

        it('fails validation when creating DTO with invalid data', function(): void {
            $result = SymfonyRequiredIfTestDto::validate([
                'shippingMethod' => 'delivery',
                'address' => null,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('address'))->toBeTrue();
        });
    });
});

