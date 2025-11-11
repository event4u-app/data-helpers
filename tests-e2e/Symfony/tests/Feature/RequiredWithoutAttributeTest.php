<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\RequiredWithout;

class SymfonyRequiredWithoutTestDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $email = null,
        #[RequiredWithout(['email'])]
        public readonly ?string $phone = null,
    ) {}
}

describe('RequiredWithout Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes when first field is provided', function(): void {
        $result = SymfonyRequiredWithoutTestDto::validate(['email' => 'test@example.com', 'phone' => null]);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when required field is provided', function(): void {
        $result = SymfonyRequiredWithoutTestDto::validate(['email' => null, 'phone' => '+1234567890']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when both are missing', function(): void {
        $result = SymfonyRequiredWithoutTestDto::validate(['email' => null, 'phone' => null]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('phone'))->toBeTrue();
    });
});

