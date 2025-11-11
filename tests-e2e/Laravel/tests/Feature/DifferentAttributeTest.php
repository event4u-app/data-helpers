<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Different;

class LaravelDifferentTestDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $password = null,
        #[Different('password')]
        public readonly ?string $username = null,
    ) {}
}

describe('Different Attribute - Laravel E2E', function(): void {
    it('passes when values are different', function(): void {
        $result = LaravelDifferentTestDto::validate(['password' => 'secret123', 'username' => 'john']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when values are the same', function(): void {
        $result = LaravelDifferentTestDto::validate(['password' => 'admin', 'username' => 'admin']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('username'))->toBeTrue();
    });
});

