<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Different;

class DifferentTestDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $password = null,
        #[Different('password')]
        public readonly ?string $username = null,
    ) {}
}

describe('Different Attribute - Plain PHP Validation', function(): void {
    it('passes when values are different', function(): void {
        $result = DifferentTestDto::validate(['password' => 'secret123', 'username' => 'john']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when values are the same', function(): void {
        $result = DifferentTestDto::validate(['password' => 'admin', 'username' => 'admin']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('username'))->toBeTrue();
    });

    it('passes when both are null', function(): void {
        $result = DifferentTestDto::validate(['password' => null, 'username' => null]);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when one is null', function(): void {
        $result = DifferentTestDto::validate(['password' => 'secret', 'username' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
