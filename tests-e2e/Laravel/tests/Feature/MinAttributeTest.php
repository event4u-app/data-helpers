<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Min;

class LaravelMinTestDto extends SimpleDto
{
    public function __construct(
        #[Min(3)]
        public readonly ?string $name = null,
    ) {}
}

describe('Min Attribute - Laravel E2E', function(): void {
    it('passes when string meets minimum', function(): void {
        $result = LaravelMinTestDto::validate(['name' => 'John']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when equals minimum', function(): void {
        $result = LaravelMinTestDto::validate(['name' => 'Joe']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when below minimum', function(): void {
        $result = LaravelMinTestDto::validate(['name' => 'Jo']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('name'))->toBeTrue();
    });
});

