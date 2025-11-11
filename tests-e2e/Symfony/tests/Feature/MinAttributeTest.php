<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Min;

class SymfonyMinTestDto extends SimpleDto
{
    public function __construct(
        #[Min(3)]
        public readonly ?string $name = null,
    ) {}
}

describe('Min Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes when string meets minimum', function(): void {
        $result = SymfonyMinTestDto::validate(['name' => 'John']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when equals minimum', function(): void {
        $result = SymfonyMinTestDto::validate(['name' => 'Joe']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when below minimum', function(): void {
        $result = SymfonyMinTestDto::validate(['name' => 'Jo']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('name'))->toBeTrue();
    });
});

