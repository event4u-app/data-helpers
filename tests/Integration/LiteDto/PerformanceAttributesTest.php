<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\Hidden;
use event4u\DataHelpers\LiteDto\Attributes\NoAttributes;
use event4u\DataHelpers\LiteDto\Attributes\NoValidation;
use event4u\DataHelpers\LiteDto\Attributes\ValidateRequest;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Email;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Min;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Required;
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Support\LiteEngine;

// Test DTO with #[NoValidation]
#[NoValidation]
class PerformanceAttributesTest_NoValidationDto extends LiteDto
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        public readonly string $name,
    ) {}
}

// Test DTO with #[NoAttributes]
#[NoAttributes]
class PerformanceAttributesTest_NoAttributesDto extends LiteDto
{
    public function __construct(
        #[Hidden]
        public readonly string $email = '',

        #[Required]
        public readonly string $name = '',
    ) {}
}

// Test DTO with #[ValidateRequest]
#[ValidateRequest(throw: true, stopOnFirstFailure: true)]
class PerformanceAttributesTest_ValidateRequestDto extends LiteDto
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        public readonly string $name,
    ) {}
}

// Test DTO with #[ValidateRequest] with groups
#[ValidateRequest(throw: false, groups: ['create', 'update'])]
class PerformanceAttributesTest_ValidateRequestGroupsDto extends LiteDto
{
    public function __construct(
        #[Required]
        public readonly string $name,
    ) {}
}

// Test DTO without any performance attributes (for comparison)
class PerformanceAttributesTest_NormalDto extends LiteDto
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        public readonly string $name,
    ) {}
}

test('#[NoValidation] skips all validation', function(): void {
    // Invalid data (should fail validation normally)
    $data = [
        'email' => 'not-an-email',
        'name' => 'ab', // Too short
    ];

    // With NoValidation, validation is skipped
    $result = LiteEngine::validate(PerformanceAttributesTest_NoValidationDto::class, $data);

    expect($result->isValid())->toBeTrue();
    expect($result->errors())->toBe([]);
});

test('#[NoValidation] allows creating DTO with invalid data', function(): void {
    $data = [
        'email' => 'not-an-email',
        'name' => 'ab',
    ];

    $dto = PerformanceAttributesTest_NoValidationDto::from($data);

    expect($dto->email)->toBe('not-an-email');
    expect($dto->name)->toBe('ab');
});

test('#[NoValidation] validateInstance returns success', function(): void {
    $dto = new PerformanceAttributesTest_NoValidationDto(
        email: 'not-an-email',
        name: 'ab'
    );

    $result = LiteEngine::validateInstance($dto);

    expect($result->isValid())->toBeTrue();
});

test('Normal DTO validates correctly (comparison)', function(): void {
    $data = [
        'email' => 'not-an-email',
        'name' => 'ab',
    ];

    $result = LiteEngine::validate(PerformanceAttributesTest_NormalDto::class, $data);

    expect($result->isValid())->toBeFalse();
    expect($result->errors())->toHaveKey('email');
    expect($result->errors())->toHaveKey('name');
});

test('#[NoAttributes] skips all attribute processing', function(): void {
    $data = [
        'email' => 'john@example.com', // MapFrom is ignored, use property name directly
        'name' => 'John Doe',
    ];

    $dto = PerformanceAttributesTest_NoAttributesDto::from($data);

    // MapFrom is ignored, so 'email' key is used directly (not 'user_email')
    expect($dto->email)->toBe('john@example.com');
    expect($dto->name)->toBe('John Doe');
});

test('#[NoAttributes] does not hide properties', function(): void {
    $dto = new PerformanceAttributesTest_NoAttributesDto(
        email: 'john@example.com',
        name: 'John Doe'
    );

    $array = $dto->toArray();

    // Hidden attribute is ignored
    expect($array)->toHaveKey('email');
    expect($array['email'])->toBe('john@example.com');
    expect($array['name'])->toBe('John Doe');
});

test('#[NoAttributes] does not validate', function(): void {
    $data = [
        'email' => 'not-an-email',
        'name' => 'ab',
    ];

    // NoAttributes also skips validation
    $result = LiteEngine::validate(PerformanceAttributesTest_NoAttributesDto::class, $data);

    expect($result->isValid())->toBeTrue();
});

test('#[ValidateRequest] attribute can be retrieved', function(): void {
    $validateRequest = LiteEngine::getValidateRequest(PerformanceAttributesTest_ValidateRequestDto::class);

    expect($validateRequest)->toBeInstanceOf(ValidateRequest::class);
    expect($validateRequest->throw)->toBeTrue();
    expect($validateRequest->stopOnFirstFailure)->toBeTrue();
    expect($validateRequest->auto)->toBeFalse();
    expect($validateRequest->only)->toBe([]);
    expect($validateRequest->except)->toBe([]);
    expect($validateRequest->groups)->toBe([]);
});

test('#[ValidateRequest] with groups can be retrieved', function(): void {
    $validateRequest = LiteEngine::getValidateRequest(PerformanceAttributesTest_ValidateRequestGroupsDto::class);

    expect($validateRequest)->toBeInstanceOf(ValidateRequest::class);
    expect($validateRequest->throw)->toBeFalse();
    expect($validateRequest->groups)->toBe(['create', 'update']);
});

test('#[ValidateRequest] returns null for DTO without attribute', function(): void {
    $validateRequest = LiteEngine::getValidateRequest(PerformanceAttributesTest_NormalDto::class);

    expect($validateRequest)->toBeNull();
});

test('#[ValidateRequest] is cached', function(): void {
    // First call
    $validateRequest1 = LiteEngine::getValidateRequest(PerformanceAttributesTest_ValidateRequestDto::class);

    // Second call (should be cached)
    $validateRequest2 = LiteEngine::getValidateRequest(PerformanceAttributesTest_ValidateRequestDto::class);

    expect($validateRequest1)->toBe($validateRequest2);
});

test('#[NoValidation] is cached', function(): void {
    // First validation call
    $result1 = LiteEngine::validate(PerformanceAttributesTest_NoValidationDto::class, [
        'email' => 'invalid',
        'name' => 'ab',
    ]);

    // Second validation call (should use cached NoValidation check)
    $result2 = LiteEngine::validate(PerformanceAttributesTest_NoValidationDto::class, [
        'email' => 'also-invalid',
        'name' => 'x',
    ]);

    expect($result1->isValid())->toBeTrue();
    expect($result2->isValid())->toBeTrue();
});

test('#[NoAttributes] is cached in feature flags', function(): void {
    // Create first DTO (triggers feature flag scan)
    $dto1 = PerformanceAttributesTest_NoAttributesDto::from([
        'email' => 'john@example.com',
        'name' => 'John',
    ]);

    // Create second DTO (should use cached feature flags)
    $dto2 = PerformanceAttributesTest_NoAttributesDto::from([
        'email' => 'jane@example.com',
        'name' => 'Jane',
    ]);

    expect($dto1->email)->toBe('john@example.com');
    expect($dto2->email)->toBe('jane@example.com');
});

test('Performance: #[NoValidation] is faster than normal validation', function(): void {
    $data = [
        'email' => 'john@example.com',
        'name' => 'John Doe',
    ];

    // Warm up caches
    LiteEngine::validate(PerformanceAttributesTest_NoValidationDto::class, $data);
    LiteEngine::validate(PerformanceAttributesTest_NormalDto::class, $data);

    // Measure NoValidation
    $start = hrtime(true);
    for ($i = 0; 1000 > $i; $i++) {
        LiteEngine::validate(PerformanceAttributesTest_NoValidationDto::class, $data);
    }
    $noValidationTime = (hrtime(true) - $start) / 1000000; // Convert to ms

    // Measure Normal validation
    $start = hrtime(true);
    for ($i = 0; 1000 > $i; $i++) {
        LiteEngine::validate(PerformanceAttributesTest_NormalDto::class, $data);
    }
    $normalTime = (hrtime(true) - $start) / 1000000; // Convert to ms

    // NoValidation should be significantly faster
    expect($noValidationTime)->toBeLessThan($normalTime);
});
