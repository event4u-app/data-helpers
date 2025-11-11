<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\In;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\RequiredUnless;

class LaravelRequiredUnlessTestDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[In(['card', 'cash', 'free'])]
        public readonly string $paymentMethod,
        #[RequiredUnless('paymentMethod', 'free')]
        public readonly ?string $paymentDetails = null,
    ) {}
}

class LaravelRequiredUnlessBooleanTestDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly bool $isGuest,
        #[RequiredUnless('isGuest', true)]
        public readonly ?string $accountId = null,
    ) {}
}

describe('RequiredUnless Attribute - Laravel E2E', function(): void {
    it('passes when field is required and provided', function(): void {
        $result = LaravelRequiredUnlessTestDto::validate([
            'paymentMethod' => 'card',
            'paymentDetails' => '1234-5678-9012-3456',
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when field is required but not provided', function(): void {
        $result = LaravelRequiredUnlessTestDto::validate([
            'paymentMethod' => 'card',
            'paymentDetails' => null,
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('paymentDetails'))->toBeTrue();
    });

    it('passes when unless condition is met', function(): void {
        $result = LaravelRequiredUnlessTestDto::validate([
            'paymentMethod' => 'free',
            'paymentDetails' => null,
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('validates boolean conditions', function(): void {
        $result = LaravelRequiredUnlessBooleanTestDto::validate([
            'isGuest' => false,
            'accountId' => 'ACC-12345',
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when boolean condition requires field', function(): void {
        $result = LaravelRequiredUnlessBooleanTestDto::validate([
            'isGuest' => false,
            'accountId' => null,
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('accountId'))->toBeTrue();
    });
});

