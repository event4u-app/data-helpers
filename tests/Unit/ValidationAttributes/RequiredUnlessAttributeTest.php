<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\In;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\RequiredUnless;

// Test DTOs
class RequiredUnlessTestDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[In(['card', 'cash', 'free'])]
        public readonly string $paymentMethod,
        #[RequiredUnless('paymentMethod', 'free')]
        public readonly ?string $paymentDetails = null,
    ) {}
}

class RequiredUnlessBooleanTestDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly bool $isGuest,
        #[RequiredUnless('isGuest', true)]
        public readonly ?string $accountId = null,
    ) {}
}

class RequiredUnlessNumericTestDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly int $itemCount,
        #[RequiredUnless('itemCount', 0)]
        public readonly ?string $shippingMethod = null,
    ) {}
}

describe('RequiredUnless Attribute - Plain PHP Validation', function(): void {
    describe('Basic RequiredUnless validation', function(): void {
        it('passes when field is required and provided', function(): void {
            $result = RequiredUnlessTestDto::validate([
                'paymentMethod' => 'card',
                'paymentDetails' => '1234-5678-9012-3456',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails when field is required but not provided', function(): void {
            $result = RequiredUnlessTestDto::validate([
                'paymentMethod' => 'card',
                'paymentDetails' => null,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('paymentDetails'))->toBeTrue();
            expect($result->firstError('paymentDetails'))->toContain('required unless paymentMethod is free');
        });

        it('fails when field is required but empty string', function(): void {
            $result = RequiredUnlessTestDto::validate([
                'paymentMethod' => 'cash',
                'paymentDetails' => '',
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('paymentDetails'))->toBeTrue();
        });

        it('passes when field is not required (unless condition met)', function(): void {
            $result = RequiredUnlessTestDto::validate([
                'paymentMethod' => 'free',
                'paymentDetails' => null,
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('passes when field is not required and provided anyway', function(): void {
            $result = RequiredUnlessTestDto::validate([
                'paymentMethod' => 'free',
                'paymentDetails' => 'PROMO-CODE',
            ]);

            expect($result->isValid())->toBeTrue();
        });
    });

    describe('RequiredUnless with boolean values', function(): void {
        it('passes when boolean condition is false and field is provided', function(): void {
            $result = RequiredUnlessBooleanTestDto::validate([
                'isGuest' => false,
                'accountId' => 'ACC-12345',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails when boolean condition is false but field is missing', function(): void {
            $result = RequiredUnlessBooleanTestDto::validate([
                'isGuest' => false,
                'accountId' => null,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('accountId'))->toBeTrue();
        });

        it('passes when boolean condition is true (unless met)', function(): void {
            $result = RequiredUnlessBooleanTestDto::validate([
                'isGuest' => true,
                'accountId' => null,
            ]);

            expect($result->isValid())->toBeTrue();
        });
    });

    describe('RequiredUnless with numeric values', function(): void {
        it('passes when numeric condition does not match and field is provided', function(): void {
            $result = RequiredUnlessNumericTestDto::validate([
                'itemCount' => 5,
                'shippingMethod' => 'express',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('fails when numeric condition does not match but field is missing', function(): void {
            $result = RequiredUnlessNumericTestDto::validate([
                'itemCount' => 5,
                'shippingMethod' => null,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('shippingMethod'))->toBeTrue();
        });

        it('passes when numeric condition matches (unless met)', function(): void {
            $result = RequiredUnlessNumericTestDto::validate([
                'itemCount' => 0,
                'shippingMethod' => null,
            ]);

            expect($result->isValid())->toBeTrue();
        });
    });

    describe('Edge cases', function(): void {
        it('handles zero as a valid value', function(): void {
            $result = RequiredUnlessNumericTestDto::validate([
                'itemCount' => 0,
                'shippingMethod' => null,
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('handles empty string as different from null', function(): void {
            $result = RequiredUnlessTestDto::validate([
                'paymentMethod' => 'card',
                'paymentDetails' => '',
            ]);

            expect($result->isValid())->toBeFalse();
        });
    });

    describe('DTO creation', function(): void {
        it('creates DTO when validation passes', function(): void {
            $dto = RequiredUnlessTestDto::from([
                'paymentMethod' => 'card',
                'paymentDetails' => '1234-5678-9012-3456',
            ]);

            expect($dto->paymentMethod)->toBe('card');
            expect($dto->paymentDetails)->toBe('1234-5678-9012-3456');
        });

        it('fails validation when creating DTO with invalid data', function(): void {
            $result = RequiredUnlessTestDto::validate([
                'paymentMethod' => 'card',
                'paymentDetails' => null,
            ]);

            expect($result->isValid())->toBeFalse();
            expect($result->hasError('paymentDetails'))->toBeTrue();
        });
    });
});
