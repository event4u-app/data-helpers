<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AlphaNum;

class LaravelAlphaNumTestDto extends SimpleDto
{
    public function __construct(
        #[AlphaNum]
        public readonly ?string $username = null,
    ) {}
}

describe('AlphaNum Attribute - Laravel E2E', function(): void {
    it('passes with letters and numbers', function(): void {
        $result = LaravelAlphaNumTestDto::validate(['username' => 'User123']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with special characters', function(): void {
        $result = LaravelAlphaNumTestDto::validate(['username' => 'User_123']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('username'))->toBeTrue();
    });
});

