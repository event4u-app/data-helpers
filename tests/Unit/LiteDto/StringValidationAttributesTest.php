<?php

declare(strict_types=1);

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\LiteDto\Attributes\Validation\EndsWith;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Regex;
use event4u\DataHelpers\LiteDto\Attributes\Validation\StartsWith;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Url;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Uuid;
use event4u\DataHelpers\LiteDto\LiteDto;

// Test DTOs
class StringValidationTestRegexDto extends LiteDto
{
    public function __construct(
        #[Regex('/^[A-Z]{2}\d{4}$/')]
        public readonly string $code,
    ) {}
}

class StringValidationTestStartsWithDto extends LiteDto
{
    public function __construct(
        #[StartsWith(['http://', 'https://'])]
        public readonly string $url,
    ) {}
}

class StringValidationTestEndsWithDto extends LiteDto
{
    public function __construct(
        #[EndsWith(['.com', '.org', '.net'])]
        public readonly string $domain,
    ) {}
}

class StringValidationTestUrlDto extends LiteDto
{
    public function __construct(
        #[Url]
        public readonly string $website,
    ) {}
}

class StringValidationTestUuidDto extends LiteDto
{
    public function __construct(
        #[Uuid]
        public readonly string $id,
    ) {}
}

class StringValidationTestMultipleDto extends LiteDto
{
    public function __construct(
        #[StartsWith('https://')]
        #[EndsWith('.com')]
        public readonly string $secureWebsite,
    ) {}
}

describe('LiteDto String Validation Attributes', function(): void {
    describe('Regex Attribute', function(): void {
        it('validates matching pattern', function(): void {
            $dto = StringValidationTestRegexDto::from(['code' => 'AB1234']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails for non-matching pattern', function(): void {
            $dto = StringValidationTestRegexDto::from(['code' => 'invalid']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('code'))->toBeTrue();
        });

        it('fails for wrong format', function(): void {
            $dto = StringValidationTestRegexDto::from(['code' => 'AB123']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
        });
    });

    describe('StartsWith Attribute', function(): void {
        it('validates string starting with http://', function(): void {
            $dto = StringValidationTestStartsWithDto::from(['url' => 'http://example.com']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates string starting with https://', function(): void {
            $dto = StringValidationTestStartsWithDto::from(['url' => 'https://example.com']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails when not starting with allowed prefix', function(): void {
            $dto = StringValidationTestStartsWithDto::from(['url' => 'ftp://example.com']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('url'))->toBeTrue();
        });
    });

    describe('EndsWith Attribute', function(): void {
        it('validates string ending with .com', function(): void {
            $dto = StringValidationTestEndsWithDto::from(['domain' => 'example.com']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates string ending with .org', function(): void {
            $dto = StringValidationTestEndsWithDto::from(['domain' => 'example.org']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates string ending with .net', function(): void {
            $dto = StringValidationTestEndsWithDto::from(['domain' => 'example.net']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails when not ending with allowed suffix', function(): void {
            $dto = StringValidationTestEndsWithDto::from(['domain' => 'example.de']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('domain'))->toBeTrue();
        });
    });

    describe('Url Attribute', function(): void {
        it('validates valid HTTP URL', function(): void {
            $dto = StringValidationTestUrlDto::from(['website' => 'http://example.com']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates valid HTTPS URL', function(): void {
            $dto = StringValidationTestUrlDto::from(['website' => 'https://example.com']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates URL with path', function(): void {
            $dto = StringValidationTestUrlDto::from(['website' => 'https://example.com/path/to/page']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates URL with query string', function(): void {
            $dto = StringValidationTestUrlDto::from(['website' => 'https://example.com?foo=bar']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails for invalid URL', function(): void {
            $dto = StringValidationTestUrlDto::from(['website' => 'not-a-url']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('website'))->toBeTrue();
        });

        it('fails for URL without protocol', function(): void {
            $dto = StringValidationTestUrlDto::from(['website' => 'example.com']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
        });
    });

    describe('Uuid Attribute', function(): void {
        it('validates valid UUID v4', function(): void {
            $dto = StringValidationTestUuidDto::from(['id' => '550e8400-e29b-41d4-a716-446655440000']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates valid UUID v1', function(): void {
            $dto = StringValidationTestUuidDto::from(['id' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails for invalid UUID format', function(): void {
            $dto = StringValidationTestUuidDto::from(['id' => 'not-a-uuid']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('id'))->toBeTrue();
        });

        it('fails for UUID without dashes', function(): void {
            $dto = StringValidationTestUuidDto::from(['id' => '550e8400e29b41d4a716446655440000']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
        });

        it('fails for UUID with wrong length', function(): void {
            $dto = StringValidationTestUuidDto::from(['id' => '550e8400-e29b-41d4-a716']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
        });
    });

    describe('Multiple String Validation Attributes', function(): void {
        it('validates when all conditions are met', function(): void {
            $dto = StringValidationTestMultipleDto::from(['secureWebsite' => 'https://example.com']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails when StartsWith condition is not met', function(): void {
            $dto = StringValidationTestMultipleDto::from(['secureWebsite' => 'http://example.com']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('secureWebsite'))->toBeTrue();
        });

        it('fails when EndsWith condition is not met', function(): void {
            $dto = StringValidationTestMultipleDto::from(['secureWebsite' => 'https://example.org']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('secureWebsite'))->toBeTrue();
        });

        it('fails when both conditions are not met', function(): void {
            $dto = StringValidationTestMultipleDto::from(['secureWebsite' => 'http://example.org']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('secureWebsite'))->toBeTrue();
        });
    });

    describe('validate() static method', function(): void {
        it('validates data before creating DTO', function(): void {
            $result = StringValidationTestRegexDto::validate(['code' => 'AB1234']);
            expect($result->isValid())->toBeTrue();
        });

        it('returns errors for invalid data', function(): void {
            $result = StringValidationTestRegexDto::validate(['code' => 'invalid']);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('code'))->toBeTrue();
        });
    });

    describe('validateAndCreate() static method', function(): void {
        it('creates DTO when validation passes', function(): void {
            $dto = StringValidationTestUrlDto::validateAndCreate(['website' => 'https://example.com']);
            expect($dto->website)->toBe('https://example.com');
        });

        it('throws exception when validation fails', function(): void {
            expect(
                fn(): \StringValidationTestUrlDto => StringValidationTestUrlDto::validateAndCreate([
                    'website' => 'not-a-url']
                )
            )
                ->toThrow(ValidationException::class);
        });
    });
});
