<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Same;

class SameTestDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $email = null,
        #[Same('email')]
        public readonly ?string $emailConfirmation = null,
    ) {}
}

describe('Same Attribute - Plain PHP Validation', function(): void {
    it('passes when values are the same', function(): void {
        $result = SameTestDto::validate(['email' => 'test@example.com', 'emailConfirmation' => 'test@example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when values are different', function(): void {
        $result = SameTestDto::validate(['email' => 'test@example.com', 'emailConfirmation' => 'other@example.com']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('emailConfirmation'))->toBeTrue();
    });

    it('passes when both are null', function(): void {
        $result = SameTestDto::validate(['email' => null, 'emailConfirmation' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
