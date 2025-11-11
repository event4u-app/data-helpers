<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\In;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\RequiredUnless;

class SymfonyRequiredUnlessTestDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[In(['card', 'cash', 'free'])]
        public readonly string $paymentMethod,
        #[RequiredUnless('paymentMethod', 'free')]
        public readonly ?string $paymentDetails = null,
    ) {}
}

class SymfonyRequiredUnlessBooleanTestDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly bool $isGuest,
        #[RequiredUnless('isGuest', true)]
        public readonly ?string $accountId = null,
    ) {}
}

describe('RequiredUnless Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes when field is required and provided', function(): void {
        $result = SymfonyRequiredUnlessTestDto::validate([
            'paymentMethod' => 'card',
            'paymentDetails' => '1234-5678-9012-3456',
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when field is required but not provided', function(): void {
        $result = SymfonyRequiredUnlessTestDto::validate([
            'paymentMethod' => 'card',
            'paymentDetails' => null,
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('paymentDetails'))->toBeTrue();
    });

    it('passes when unless condition is met', function(): void {
        $result = SymfonyRequiredUnlessTestDto::validate([
            'paymentMethod' => 'free',
            'paymentDetails' => null,
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('validates boolean conditions', function(): void {
        $result = SymfonyRequiredUnlessBooleanTestDto::validate([
            'isGuest' => false,
            'accountId' => 'ACC-12345',
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when boolean condition requires field', function(): void {
        $result = SymfonyRequiredUnlessBooleanTestDto::validate([
            'isGuest' => false,
            'accountId' => null,
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('accountId'))->toBeTrue();
    });
});

