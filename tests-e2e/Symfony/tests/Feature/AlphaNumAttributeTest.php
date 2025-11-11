<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AlphaNum;

class SymfonyAlphaNumTestDto extends SimpleDto
{
    public function __construct(
        #[AlphaNum]
        public readonly ?string $username = null,
    ) {}
}

describe('AlphaNum Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes with letters and numbers', function(): void {
        $result = SymfonyAlphaNumTestDto::validate(['username' => 'User123']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with special characters', function(): void {
        $result = SymfonyAlphaNumTestDto::validate(['username' => 'User_123']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('username'))->toBeTrue();
    });
});

