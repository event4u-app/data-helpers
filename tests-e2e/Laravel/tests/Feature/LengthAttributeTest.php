<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Length;

class LaravelLengthTestDto extends SimpleDto
{
    public function __construct(
        #[Length(3, 10)]
        public readonly ?string $username = null,
    ) {}
}

describe('Length Attribute - Laravel E2E', function(): void {
    it('passes when string is within range', function(): void {
        $result = LaravelLengthTestDto::validate(['username' => 'john']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when string is below min', function(): void {
        $result = LaravelLengthTestDto::validate(['username' => 'jo']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('username'))->toBeTrue();
    });

    it('fails when string exceeds max', function(): void {
        $result = LaravelLengthTestDto::validate(['username' => str_repeat('a', 11)]);
        expect($result->isValid())->toBeFalse();
    });
});

