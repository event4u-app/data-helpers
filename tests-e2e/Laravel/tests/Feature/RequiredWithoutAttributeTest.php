<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\RequiredWithout;

class LaravelRequiredWithoutTestDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $email = null,
        #[RequiredWithout(['email'])]
        public readonly ?string $phone = null,
    ) {}
}

describe('RequiredWithout Attribute - Laravel E2E', function(): void {
    it('passes when first field is provided', function(): void {
        $result = LaravelRequiredWithoutTestDto::validate(['email' => 'test@example.com', 'phone' => null]);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when required field is provided', function(): void {
        $result = LaravelRequiredWithoutTestDto::validate(['email' => null, 'phone' => '+1234567890']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when both are missing', function(): void {
        $result = LaravelRequiredWithoutTestDto::validate(['email' => null, 'phone' => null]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('phone'))->toBeTrue();
    });
});

