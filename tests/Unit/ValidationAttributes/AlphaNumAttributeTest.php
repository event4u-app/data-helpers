<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AlphaNum;

class AlphaNumTestDto extends SimpleDto
{
    public function __construct(
        #[AlphaNum]
        public readonly ?string $username = null,
    ) {}
}

describe('AlphaNum Attribute - Plain PHP Validation', function(): void {
    it('passes with letters and numbers', function(): void {
        $result = AlphaNumTestDto::validate(['username' => 'User123']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with only letters', function(): void {
        $result = AlphaNumTestDto::validate(['username' => 'Username']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with special characters', function(): void {
        $result = AlphaNumTestDto::validate(['username' => 'User_123']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('username'))->toBeTrue();
    });

    it('passes with null', function(): void {
        $result = AlphaNumTestDto::validate(['username' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
