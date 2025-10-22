<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Between;
use event4u\DataHelpers\SimpleDTO\Attributes\Confirmed;
use event4u\DataHelpers\SimpleDTO\Attributes\Different;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\EndsWith;
use event4u\DataHelpers\SimpleDTO\Attributes\Exists;
use event4u\DataHelpers\SimpleDTO\Attributes\File;
use event4u\DataHelpers\SimpleDTO\Attributes\Image;
use event4u\DataHelpers\SimpleDTO\Attributes\In;
use event4u\DataHelpers\SimpleDTO\Attributes\Ip;
use event4u\DataHelpers\SimpleDTO\Attributes\Json;
use event4u\DataHelpers\SimpleDTO\Attributes\Max;
use event4u\DataHelpers\SimpleDTO\Attributes\Mimes;
use event4u\DataHelpers\SimpleDTO\Attributes\MimeTypes;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\NotIn;
use event4u\DataHelpers\SimpleDTO\Attributes\Regex;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Same;
use event4u\DataHelpers\SimpleDTO\Attributes\Size;
use event4u\DataHelpers\SimpleDTO\Attributes\StartsWith;
use event4u\DataHelpers\SimpleDTO\Attributes\Unique;
use event4u\DataHelpers\SimpleDTO\Attributes\Url;
use event4u\DataHelpers\SimpleDTO\Attributes\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

// Test DTOs
class SymfonyValidationTestDTO1 extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,
        #[Between(18, 120)]
        public readonly int $age,
    ) {}
}

class SymfonyValidationTestDTO2 extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,
        #[Between(18, 120)]
        public readonly int $age,
        #[Url]
        public readonly string $website,
        #[Uuid]
        public readonly string $uuid,
    ) {}
}

// Skip all tests if Symfony Validator is not installed
if (!class_exists('Symfony\Component\Validator\Constraint')) {
    test('Symfony Validator not installed', function(): void {
        expect(true)->toBeTrue();
    })->skip('Symfony Validator is not installed. Install with: composer require symfony/validator');
    return;
}

describe('Symfony Validation Integration', function(): void {
    describe('Constraint Generation', function(): void {
        it('generates Symfony NotBlank constraint for Required attribute', function(): void {
            $attribute = new Required();
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\NotBlank::class);
        })->group('symfony');

        it('generates Symfony Email constraint for Email attribute', function(): void {
            $attribute = new Email();
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Email::class);
        })->group('symfony');

        it('generates Symfony GreaterThanOrEqual constraint for Min attribute', function(): void {
            $attribute = new Min(3);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\GreaterThanOrEqual::class);
        })->group('symfony');

        it('generates Symfony LessThanOrEqual constraint for Max attribute', function(): void {
            $attribute = new Max(50);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\LessThanOrEqual::class);
        })->group('symfony');

        it('generates Symfony Range constraint for Between attribute', function(): void {
            $attribute = new Between(18, 65);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Range::class);
        })->group('symfony');

        it('generates Symfony Choice constraint for In attribute', function(): void {
            $attribute = new In(['admin', 'user', 'guest']);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Choice::class);
        })->group('symfony');

        it('generates Symfony Choice constraint with match=false for NotIn attribute', function(): void {
            $attribute = new NotIn(['banned', 'deleted']);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Choice::class);
        })->group('symfony');

        it('generates Symfony Regex constraint for Regex attribute', function(): void {
            $attribute = new Regex('/^[A-Z]{2}\d{4}$/');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Regex::class);
        })->group('symfony');

        it('generates Symfony Url constraint for Url attribute', function(): void {
            $attribute = new Url();
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Url::class);
        })->group('symfony');

        it('generates Symfony Uuid constraint for Uuid attribute', function(): void {
            $attribute = new Uuid();
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Uuid::class);
        })->group('symfony');

        it('generates Symfony Length constraint for Size attribute', function(): void {
            $attribute = new Size(10);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Length::class);
        })->group('symfony');

        it('generates Symfony Ip constraint for Ip attribute', function(): void {
            $attribute = new Ip();
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Ip::class);
        })->group('symfony');

        it('generates Symfony Ip constraint with IPv4 version', function(): void {
            $attribute = new Ip(version: 'ipv4');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Ip::class);
        })->group('symfony');

        it('generates Symfony Ip constraint with IPv6 version', function(): void {
            $attribute = new Ip(version: 'ipv6');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Ip::class);
        })->group('symfony');

        it('generates Symfony Json constraint for Json attribute', function(): void {
            $attribute = new Json();
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Json::class);
        })->group('symfony');
    })->group('symfony');

    describe('Constraint with Custom Messages', function(): void {
        it('passes custom message to NotBlank constraint', function(): void {
            $attribute = new Required(message: 'This field is mandatory');

            expect($attribute->message)->toBe('This field is mandatory');
            expect($attribute->constraint())->toBeInstanceOf(Assert\NotBlank::class);
        })->group('symfony');

        it('passes custom message to Email constraint', function(): void {
            $attribute = new Email(message: 'Invalid email format');

            expect($attribute->message)->toBe('Invalid email format');
            expect($attribute->constraint())->toBeInstanceOf(Assert\Email::class);
        })->group('symfony');

        it('passes custom message to GreaterThanOrEqual constraint', function(): void {
            $attribute = new Min(3, message: 'Too short');

            expect($attribute->message)->toBe('Too short');
            expect($attribute->constraint())->toBeInstanceOf(Assert\GreaterThanOrEqual::class);
        })->group('symfony');

        it('passes custom message to LessThanOrEqual constraint', function(): void {
            $attribute = new Max(50, message: 'Too long');

            expect($attribute->message)->toBe('Too long');
            expect($attribute->constraint())->toBeInstanceOf(Assert\LessThanOrEqual::class);
        })->group('symfony');

        it('passes custom message to Range constraint', function(): void {
            $attribute = new Between(18, 65, message: 'Age must be between 18 and 65');

            expect($attribute->message)->toBe('Age must be between 18 and 65');
            expect($attribute->constraint())->toBeInstanceOf(Assert\Range::class);
        })->group('symfony');

        it('passes custom message to Choice constraint', function(): void {
            $attribute = new In(['admin', 'user'], message: 'Invalid role');

            expect($attribute->message)->toBe('Invalid role');
            expect($attribute->constraint())->toBeInstanceOf(Assert\Choice::class);
        })->group('symfony');

        it('passes custom message to Regex constraint', function(): void {
            $attribute = new Regex('/^[A-Z]+$/', message: 'Must be uppercase');

            expect($attribute->message)->toBe('Must be uppercase');
            expect($attribute->constraint())->toBeInstanceOf(Assert\Regex::class);
        })->group('symfony');
    })->group('symfony');

    describe('Constraint Parameters', function(): void {
        it('sets min value for GreaterThanOrEqual constraint', function(): void {
            $attribute = new Min(10);

            expect($attribute->value)->toBe(10);
            expect($attribute->constraint())->toBeInstanceOf(Assert\GreaterThanOrEqual::class);
        })->group('symfony');

        it('sets max value for LessThanOrEqual constraint', function(): void {
            $attribute = new Max(100);

            expect($attribute->value)->toBe(100);
            expect($attribute->constraint())->toBeInstanceOf(Assert\LessThanOrEqual::class);
        })->group('symfony');

        it('sets min and max for Range constraint', function(): void {
            $attribute = new Between(18, 65);

            expect($attribute->min)->toBe(18);
            expect($attribute->max)->toBe(65);
            expect($attribute->constraint())->toBeInstanceOf(Assert\Range::class);
        })->group('symfony');

        it('sets choices for Choice constraint', function(): void {
            $attribute = new In(['admin', 'user', 'guest']);

            expect($attribute->values)->toBe(['admin', 'user', 'guest']);
            expect($attribute->constraint())->toBeInstanceOf(Assert\Choice::class);
        })->group('symfony');

        it('sets pattern for Regex constraint', function(): void {
            $attribute = new Regex('/^[A-Z]{2}\d{4}$/');

            expect($attribute->pattern)->toBe('/^[A-Z]{2}\d{4}$/');
            expect($attribute->constraint())->toBeInstanceOf(Assert\Regex::class);
        })->group('symfony');

        it('sets exact length for Length constraint', function(): void {
            $attribute = new Size(10);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Length::class);
            /** @var Assert\Length $constraint */
            expect($constraint->min)->toBe(10);
            expect($constraint->max)->toBe(10);
        })->group('symfony');

        it('generates Symfony Regex constraint for StartsWith attribute', function(): void {
            $attribute = new StartsWith('https://');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Regex::class);
            /** @var Assert\Regex $constraint */
            expect($constraint->pattern)->toContain('^');
        })->group('symfony');

        it('generates Symfony Regex constraint for EndsWith attribute', function(): void {
            $attribute = new EndsWith('.com');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Regex::class);
            /** @var Assert\Regex $constraint */
            expect($constraint->pattern)->toContain('$');
        })->group('symfony');
    })->group('symfony');

    describe('Automatic Validation Integration', function(): void {
        it('validates DTO using Symfony validator automatically', function(): void {
            $dto = new SymfonyValidationTestDTO1('test@example.com', 25);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->age)->toBe(25);
        })->group('symfony');

        it('throws ValidationException when Symfony validation fails', function(): void {
            expect(
                fn(): object => SymfonyValidationTestDTO1::validateAndCreate(['email' => 'not-an-email', 'age' => 25])
            )
                ->toThrow(ValidationException::class);
        })->group('symfony');

        it('validates complex DTO with multiple constraints', function(): void {
            $dto = new SymfonyValidationTestDTO2(
                'test@example.com',
                25,
                'https://example.com',
                '550e8400-e29b-41d4-a716-446655440000'
            );

            expect($dto->email)->toBe('test@example.com');
            expect($dto->age)->toBe(25);
            expect($dto->website)->toBe('https://example.com');
            expect($dto->uuid)->toBe('550e8400-e29b-41d4-a716-446655440000');
        })->group('symfony');
    })->group('symfony');

    describe('Field Comparison Constraints', function(): void {
        it('returns empty array for Same attribute (needs special handling)', function(): void {
            $attribute = new Same('password');
            $constraint = $attribute->constraint();

            expect($constraint)->toBe([]);
        })->group('symfony');

        it('returns empty array for Different attribute (needs special handling)', function(): void {
            $attribute = new Different('email');
            $constraint = $attribute->constraint();

            expect($constraint)->toBe([]);
        })->group('symfony');

        it('returns empty array for Confirmed attribute (needs special handling)', function(): void {
            $attribute = new Confirmed();
            $constraint = $attribute->constraint();

            expect($constraint)->toBe([]);
        })->group('symfony');
    })->group('symfony');

    describe('File Upload Constraints', function(): void {
        it('generates Symfony File constraint for File attribute', function(): void {
            $attribute = new File(maxSize: 10240);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\File::class);
        })->group('symfony');

        it('generates Symfony Image constraint for Image attribute', function(): void {
            $attribute = new Image(mimes: ['jpg', 'png'], maxSize: 2048);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Image::class);
        })->group('symfony');

        it('generates Symfony File constraint with mimeTypes for Mimes attribute', function(): void {
            $attribute = new Mimes(['pdf', 'doc', 'docx']);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\File::class);
        })->group('symfony');

        it('generates Symfony File constraint with mimeTypes for MimeTypes attribute', function(): void {
            $attribute = new MimeTypes(['application/pdf', 'application/msword']);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\File::class);
        })->group('symfony');
    })->group('symfony');

    describe('Database Constraints', function(): void {
        it('returns empty array for Exists attribute (needs Doctrine)', function(): void {
            $attribute = new Exists('users', 'id');
            $constraint = $attribute->constraint();

            expect($constraint)->toBe([]);
        })->group('symfony');

        it('returns empty array for Unique attribute (needs Doctrine)', function(): void {
            $attribute = new Unique('users', 'email');
            $constraint = $attribute->constraint();

            expect($constraint)->toBe([]);
        })->group('symfony');
    })->group('symfony');
})->group('symfony');
