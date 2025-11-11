<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\RequiredWithout;

class RequiredWithoutTestDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $email = null,
        #[RequiredWithout(['email'])]
        public readonly ?string $phone = null,
    ) {}
}

describe('RequiredWithout Attribute - Plain PHP Validation', function(): void {
    it('passes when first field is provided', function(): void {
        $result = RequiredWithoutTestDto::validate([
            'email' => 'test@example.com',
            'phone' => null,
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when required field is provided', function(): void {
        $result = RequiredWithoutTestDto::validate([
            'email' => null,
            'phone' => '+1234567890',
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when both fields are provided', function(): void {
        $result = RequiredWithoutTestDto::validate([
            'email' => 'test@example.com',
            'phone' => '+1234567890',
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when first field is missing and required field is also missing', function(): void {
        $result = RequiredWithoutTestDto::validate([
            'email' => null,
            'phone' => null,
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('phone'))->toBeTrue();
    });
});
