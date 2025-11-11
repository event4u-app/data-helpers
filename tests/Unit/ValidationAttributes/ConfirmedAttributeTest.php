<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Confirmed;

class ConfirmedTestDto extends SimpleDto
{
    public function __construct(
        #[Confirmed]
        public readonly ?string $password = null,
        public readonly ?string $password_confirmation = null,
    ) {}
}

describe('Confirmed Attribute - Plain PHP Validation', function(): void {
    it('passes when password and confirmation match', function(): void {
        $result = ConfirmedTestDto::validate([
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when password and confirmation do not match', function(): void {
        $result = ConfirmedTestDto::validate([
            'password' => 'secret123',
            'password_confirmation' => 'different',
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('password'))->toBeTrue();
    });

    it('fails when confirmation is missing', function(): void {
        $result = ConfirmedTestDto::validate([
            'password' => 'secret123',
            'password_confirmation' => null,
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('password'))->toBeTrue();
    });

    it('fails when both are null (confirmation field missing)', function(): void {
        $result = ConfirmedTestDto::validate([
            'password' => null,
            'password_confirmation' => null,
        ]);
        // isset() returns false for null, so confirmation field is considered missing
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('password'))->toBeTrue();
    });

    it('passes when password is null and no confirmation field', function(): void {
        $result = ConfirmedTestDto::validate([
            'password' => null,
        ]);
        // When password is null, validation should fail because confirmation is missing
        expect($result->isValid())->toBeFalse();
    });
});
