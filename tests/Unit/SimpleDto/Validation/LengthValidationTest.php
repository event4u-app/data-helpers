<?php

declare(strict_types=1);

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Length;
use event4u\DataHelpers\SimpleDto\Attributes\Required;

describe('Length Validation', function(): void {
    test('validates maximum string length', function(): void {
        $dto = new class('') extends SimpleDto {
            public function __construct(
                #[Length(10)]
                public readonly string $phone,
            ) {}
        };

        // Valid: 0-10 characters
        $result = $dto::validateAndCreate(['phone' => '']);
        expect($result->phone)->toBe('');

        $result = $dto::validateAndCreate(['phone' => '123']);
        expect($result->phone)->toBe('123');

        $result = $dto::validateAndCreate(['phone' => '1234567890']);
        expect($result->phone)->toBe('1234567890');

        // Invalid: too long
        expect(fn(): object => $dto::validateAndCreate(['phone' => '12345678901']))
            ->toThrow(ValidationException::class);
    });

    test('validates maximum number of digits', function(): void {
        $dto = new class(0) extends SimpleDto {
            public function __construct(
                #[Required]
                #[Length(3)]
                public readonly int $code,
            ) {}
        };

        // Valid: 0-3 digits
        $result = $dto::validateAndCreate(['code' => 0]);
        expect($result->code)->toBe(0);

        $result = $dto::validateAndCreate(['code' => 1]);
        expect($result->code)->toBe(1);

        $result = $dto::validateAndCreate(['code' => 123]);
        expect($result->code)->toBe(123);

        $result = $dto::validateAndCreate(['code' => -999]);
        expect($result->code)->toBe(-999);

        // Invalid: too many digits
        expect(fn(): object => $dto::validateAndCreate(['code' => 1234]))
            ->toThrow(ValidationException::class);
    });

    test('validates tinyint(1) use case', function(): void {
        $dto = new class(0) extends SimpleDto {
            public function __construct(
                #[Required]
                #[Length(1)]
                public readonly int $status,
            ) {}
        };

        // Valid: 0-1 digit
        $result = $dto::validateAndCreate(['status' => 0]);
        expect($result->status)->toBe(0);

        $result = $dto::validateAndCreate(['status' => 1]);
        expect($result->status)->toBe(1);

        $result = $dto::validateAndCreate(['status' => 9]);
        expect($result->status)->toBe(9);

        // Invalid: multiple digits
        expect(fn(): object => $dto::validateAndCreate(['status' => 10]))
            ->toThrow(ValidationException::class);
    });

    test('validates maximum array length', function(): void {
        $dto = new class([]) extends SimpleDto {
            /** @param array<int, string> $items */
            public function __construct(
                #[Length(3)]
                public readonly array $items,
            ) {}
        };

        // Valid: 0-3 items
        $result = $dto::validateAndCreate(['items' => []]);
        expect($result->items)->toBe([]);

        $result = $dto::validateAndCreate(['items' => ['a']]);
        expect($result->items)->toBe(['a']);

        $result = $dto::validateAndCreate(['items' => ['a', 'b', 'c']]);
        expect($result->items)->toBe(['a', 'b', 'c']);

        // Invalid: too many items
        expect(fn(): object => $dto::validateAndCreate(['items' => ['a', 'b', 'c', 'd']]))
            ->toThrow(ValidationException::class);
    });

    test('validates length range with min and max', function(): void {
        $dto = new class('') extends SimpleDto {
            public function __construct(
                #[Required]
                #[Length(3, 10)]
                public readonly string $username,
            ) {}
        };

        // Valid: 3-10 characters
        $result = $dto::validateAndCreate(['username' => 'abc']);
        expect($result->username)->toBe('abc');

        $result = $dto::validateAndCreate(['username' => 'abcdefghij']);
        expect($result->username)->toBe('abcdefghij');

        // Invalid: too short
        expect(fn(): object => $dto::validateAndCreate(['username' => 'ab']))
            ->toThrow(ValidationException::class);

        // Invalid: too long
        expect(fn(): object => $dto::validateAndCreate(['username' => 'abcdefghijk']))
            ->toThrow(ValidationException::class);
    });

    test('allows null values when not required', function(): void {
        $dto = new class(null) extends SimpleDto {
            public function __construct(
                #[Length(10)]
                public readonly ?string $phone = null,
            ) {}
        };

        // Valid: null
        $result = $dto::validateAndCreate(['phone' => null]);
        expect($result->phone)->toBeNull();

        // Valid: within length
        $result = $dto::validateAndCreate(['phone' => '1234567890']);
        expect($result->phone)->toBe('1234567890');
    });

    test('works with custom error message', function(): void {
        $dto = new class('') extends SimpleDto {
            public function __construct(
                #[Required]
                #[Length(10, message: 'Phone must be at most 10 digits')]
                public readonly string $phone,
            ) {}
        };

        try {
            $dto::validateAndCreate(['phone' => '12345678901']);
            expect(true)->toBeFalse('Should have thrown ValidationException');
        } catch (ValidationException $validationException) {
            expect($validationException->errors()['phone'][0])->toBe('Phone must be at most 10 digits');
        }
    });

    test('validates multiple properties with different lengths', function(): void {
        $dto = new class(0, 0, '') extends SimpleDto {
            public function __construct(
                #[Required]
                #[Length(1)]
                public readonly int $status,

                #[Required]
                #[Length(1, 3)]
                public readonly int $code,

                #[Required]
                #[Length(10)]
                public readonly string $phone,
            ) {}
        };

        // Valid
        $result = $dto::validateAndCreate([
            'status' => 1,
            'code' => 123,
            'phone' => '1234567890',
        ]);

        expect($result->status)->toBe(1);
        expect($result->code)->toBe(123);
        expect($result->phone)->toBe('1234567890');
    });

    test('generates correct Laravel validation rule for max', function(): void {
        $attribute = new Length(10);
        expect($attribute->rule())->toBe('max:10');
    });

    test('generates correct Laravel validation rule for range', function(): void {
        $attribute = new Length(3, 10);
        expect($attribute->rule())->toBe('between:3,10');
    });
});
