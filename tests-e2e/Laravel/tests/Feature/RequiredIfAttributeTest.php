<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\In;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\RequiredIf;
use Illuminate\Support\Facades\Validator;

// Test DTOs
class LaravelRequiredIfTestDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[In(['pickup', 'delivery'])]
        public readonly string $shippingMethod,
        #[RequiredIf('shippingMethod', 'delivery')]
        public readonly ?string $address = null,
    ) {}
}

class LaravelRequiredIfBooleanTestDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly bool $needsShipping,
        #[RequiredIf('needsShipping', true)]
        public readonly ?string $shippingAddress = null,
    ) {}
}

class LaravelRequiredIfNumericTestDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly int $quantity,
        #[RequiredIf('quantity', 10)]
        public readonly ?string $bulkDiscountCode = null,
    ) {}
}

describe('RequiredIf Attribute - Laravel E2E', function(): void {

    describe('SimpleDto validate() method with Laravel', function(): void {
        it('passes when field is required and provided', function(): void {
            $result = LaravelRequiredIfTestDto::validate([
                'shippingMethod' => 'delivery',
                'address' => '123 Main St',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails when field is required but not provided', function(): void {
            $result = LaravelRequiredIfTestDto::validate([
                'shippingMethod' => 'delivery',
                'address' => null,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('address'))->toBeTrue();
        });

        it('passes when field is not required', function(): void {
            $result = LaravelRequiredIfTestDto::validate([
                'shippingMethod' => 'pickup',
                'address' => null,
            ]);

            expect($result->isValid())->toBeTrue();
        });
    });

    describe('Boolean conditions with Laravel', function(): void {
        it('validates boolean true condition', function(): void {
            $result = LaravelRequiredIfBooleanTestDto::validate([
                'needsShipping' => true,
                'shippingAddress' => '456 Oak Ave',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails when boolean condition is true but field is missing', function(): void {
            $result = LaravelRequiredIfBooleanTestDto::validate([
                'needsShipping' => true,
                'shippingAddress' => null,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('shippingAddress'))->toBeTrue();
        });

        it('passes when boolean condition is false', function(): void {
            $result = LaravelRequiredIfBooleanTestDto::validate([
                'needsShipping' => false,
                'shippingAddress' => null,
            ]);

            expect($result->isValid())->toBeTrue();
        });

    });

    describe('Numeric conditions with Laravel', function(): void {
        it('validates numeric condition', function(): void {
            $result = LaravelRequiredIfNumericTestDto::validate([
                'quantity' => 10,
                'bulkDiscountCode' => 'BULK10',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails when numeric condition matches but field is missing', function(): void {
            $result = LaravelRequiredIfNumericTestDto::validate([
                'quantity' => 10,
                'bulkDiscountCode' => null,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('bulkDiscountCode'))->toBeTrue();
        });

        it('passes when numeric condition does not match', function(): void {
            $result = LaravelRequiredIfNumericTestDto::validate([
                'quantity' => 5,
                'bulkDiscountCode' => null,
            ]);

            expect($result->isValid())->toBeTrue();
        });

    });



    describe('DTO creation with Laravel', function(): void {
        it('creates DTO when validation passes', function(): void {
            $dto = LaravelRequiredIfTestDto::from([
                'shippingMethod' => 'delivery',
                'address' => '123 Main St',
            ]);

            expect($dto->shippingMethod)->toBe('delivery');
            expect($dto->address)->toBe('123 Main St');
        });

        it('fails validation when creating DTO with invalid data', function(): void {
            $result = LaravelRequiredIfTestDto::validate([
                'shippingMethod' => 'delivery',
                'address' => null,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('address'))->toBeTrue();
        });
    });
});

