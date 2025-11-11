<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Same;

class LaravelSameTestDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $email = null,
        #[Same('email')]
        public readonly ?string $emailConfirmation = null,
    ) {}
}

describe('Same Attribute - Laravel E2E', function(): void {
    it('passes when values are the same', function(): void {
        $result = LaravelSameTestDto::validate(['email' => 'test@example.com', 'emailConfirmation' => 'test@example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when values are different', function(): void {
        $result = LaravelSameTestDto::validate(['email' => 'test@example.com', 'emailConfirmation' => 'other@example.com']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('emailConfirmation'))->toBeTrue();
    });
});

