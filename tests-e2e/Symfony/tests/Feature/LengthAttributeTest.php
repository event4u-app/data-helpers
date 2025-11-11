<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Length;

class SymfonyLengthTestDto extends SimpleDto
{
    public function __construct(
        #[Length(3, 10)]
        public readonly ?string $username = null,
    ) {}
}

describe('Length Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes when string is within range', function(): void {
        $result = SymfonyLengthTestDto::validate(['username' => 'john']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when string is below min', function(): void {
        $result = SymfonyLengthTestDto::validate(['username' => 'jo']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('username'))->toBeTrue();
    });

    it('fails when string exceeds max', function(): void {
        $result = SymfonyLengthTestDto::validate(['username' => str_repeat('a', 11)]);
        expect($result->isValid())->toBeFalse();
    });
});

