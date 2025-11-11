<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ConfirmedBy;

class ConfirmedByTestDto extends SimpleDto
{
    public function __construct(
        #[ConfirmedBy('passwordConfirm')]
        public readonly ?string $password = null,
        public readonly ?string $passwordConfirm = null,
    ) {}
}

describe('ConfirmedBy Attribute - Plain PHP Validation', function(): void {
    it('passes when password and custom confirmation match', function(): void {
        $result = ConfirmedByTestDto::validate([
            'password' => 'secret123',
            'passwordConfirm' => 'secret123',
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when password and custom confirmation do not match', function(): void {
        $result = ConfirmedByTestDto::validate([
            'password' => 'secret123',
            'passwordConfirm' => 'different',
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('password'))->toBeTrue();
    });

    it('fails when custom confirmation is missing', function(): void {
        $result = ConfirmedByTestDto::validate([
            'password' => 'secret123',
            'passwordConfirm' => null,
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('password'))->toBeTrue();
    });

    it('fails when both are null (confirmation field missing)', function(): void {
        $result = ConfirmedByTestDto::validate([
            'password' => null,
            'passwordConfirm' => null,
        ]);
        // isset() returns false for null, so confirmation field is considered missing
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('password'))->toBeTrue();
    });
});
