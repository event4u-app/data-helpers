<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto;

use event4u\DataHelpers\SimpleDto\Support\FastPath;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithCastsMethod;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithComputedOldApi;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithFiltersMethod;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithRulesMethod;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithTemplateMethod;

/**
 * Tests for FastPath detection of method overrides.
 *
 * Phase 7: Tests for all method overrides that should disable FastPath.
 */
test('DTO with casts() method is NOT eligible for FastPath', function(): void {
    expect(FastPath::canUseFastPath(DtoWithCastsMethod::class))->toBeFalse();
});

test('DTO with template() method is NOT eligible for FastPath', function(): void {
    expect(FastPath::canUseFastPath(DtoWithTemplateMethod::class))->toBeFalse();
});

test('DTO with rules() method is NOT eligible for FastPath', function(): void {
    expect(FastPath::canUseFastPath(DtoWithRulesMethod::class))->toBeFalse();
});

test('DTO with filters() method is NOT eligible for FastPath', function(): void {
    expect(FastPath::canUseFastPath(DtoWithFiltersMethod::class))->toBeFalse();
});

test('DTO with computed() method (old API) is NOT eligible for FastPath', function(): void {
    expect(FastPath::canUseFastPath(DtoWithComputedOldApi::class))->toBeFalse();
});
