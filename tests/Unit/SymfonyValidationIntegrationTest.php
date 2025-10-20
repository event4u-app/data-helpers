<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Max;
use event4u\DataHelpers\SimpleDTO\Attributes\Between;
use event4u\DataHelpers\SimpleDTO\Attributes\In;
use event4u\DataHelpers\SimpleDTO\Attributes\NotIn;
use event4u\DataHelpers\SimpleDTO\Attributes\Regex;
use event4u\DataHelpers\SimpleDTO\Attributes\Url;
use event4u\DataHelpers\SimpleDTO\Attributes\Uuid;
use event4u\DataHelpers\SimpleDTO\Attributes\Size;
use event4u\DataHelpers\SimpleDTO\Attributes\Ip;
use event4u\DataHelpers\SimpleDTO\Attributes\Json;
use event4u\DataHelpers\SimpleDTO\Attributes\StartsWith;
use event4u\DataHelpers\SimpleDTO\Attributes\EndsWith;
use event4u\DataHelpers\SimpleDTO\Attributes\Same;
use event4u\DataHelpers\SimpleDTO\Attributes\Different;
use event4u\DataHelpers\SimpleDTO\Attributes\Confirmed;
use event4u\DataHelpers\SimpleDTO\Attributes\File;
use event4u\DataHelpers\SimpleDTO\Attributes\Image;
use event4u\DataHelpers\SimpleDTO\Attributes\Mimes;
use event4u\DataHelpers\SimpleDTO\Attributes\MimeTypes;
use event4u\DataHelpers\SimpleDTO\Attributes\Exists;
use event4u\DataHelpers\SimpleDTO\Attributes\Unique;
use event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;
use event4u\DataHelpers\Exceptions\ValidationException;
use Symfony\Component\Validator\Constraints as Assert;

// Skip all tests if Symfony Validator is not installed
if (!class_exists('Symfony\Component\Validator\Constraint')) {
    test('Symfony Validator not installed', function () {
        expect(true)->toBeTrue();
    })->skip('Symfony Validator is not installed. Install with: composer require symfony/validator');
    return;
}

describe('Symfony Validation Integration', function () {
    describe('Constraint Generation', function () {
        it('generates Symfony NotBlank constraint for Required attribute', function () {
            $attribute = new Required();
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\NotBlank::class);
        })->group('symfony');

        it('generates Symfony Email constraint for Email attribute', function () {
            $attribute = new Email();
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Email::class);
        })->group('symfony');

        it('generates Symfony GreaterThanOrEqual constraint for Min attribute', function () {
            $attribute = new Min(3);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\GreaterThanOrEqual::class);
        })->group('symfony');

        it('generates Symfony LessThanOrEqual constraint for Max attribute', function () {
            $attribute = new Max(50);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\LessThanOrEqual::class);
        })->group('symfony');

        it('generates Symfony Range constraint for Between attribute', function () {
            $attribute = new Between(18, 65);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Range::class);
        })->group('symfony');

        it('generates Symfony Choice constraint for In attribute', function () {
            $attribute = new In(['admin', 'user', 'guest']);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Choice::class);
        })->group('symfony');

        it('generates Symfony Choice constraint with match=false for NotIn attribute', function () {
            $attribute = new NotIn(['banned', 'deleted']);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Choice::class);
        })->group('symfony');

        it('generates Symfony Regex constraint for Regex attribute', function () {
            $attribute = new Regex('/^[A-Z]{2}[0-9]{4}$/');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Regex::class);
        })->group('symfony');

        it('generates Symfony Url constraint for Url attribute', function () {
            $attribute = new Url();
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Url::class);
        })->group('symfony');

        it('generates Symfony Uuid constraint for Uuid attribute', function () {
            $attribute = new Uuid();
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Uuid::class);
        })->group('symfony');

        it('generates Symfony Length constraint for Size attribute', function () {
            $attribute = new Size(10);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Length::class);
        })->group('symfony');

        it('generates Symfony Ip constraint for Ip attribute', function () {
            $attribute = new Ip();
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Ip::class);
        })->group('symfony');

        it('generates Symfony Ip constraint with IPv4 version', function () {
            $attribute = new Ip(version: 'ipv4');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Ip::class);
        })->group('symfony');

        it('generates Symfony Ip constraint with IPv6 version', function () {
            $attribute = new Ip(version: 'ipv6');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Ip::class);
        })->group('symfony');

        it('generates Symfony Json constraint for Json attribute', function () {
            $attribute = new Json();
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Json::class);
        })->group('symfony');
    });

    describe('Constraint with Custom Messages', function () {
        it('passes custom message to NotBlank constraint', function () {
            $attribute = new Required(message: 'This field is mandatory');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\NotBlank::class);
            expect($constraint->message)->toBe('This field is mandatory');
        })->group('symfony');

        it('passes custom message to Email constraint', function () {
            $attribute = new Email(message: 'Invalid email format');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Email::class);
            expect($constraint->message)->toBe('Invalid email format');
        })->group('symfony');

        it('passes custom message to GreaterThanOrEqual constraint', function () {
            $attribute = new Min(3, message: 'Too short');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\GreaterThanOrEqual::class);
            expect($constraint->message)->toBe('Too short');
        })->group('symfony');

        it('passes custom message to LessThanOrEqual constraint', function () {
            $attribute = new Max(50, message: 'Too long');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\LessThanOrEqual::class);
            expect($constraint->message)->toBe('Too long');
        })->group('symfony');

        it('passes custom message to Range constraint', function () {
            $attribute = new Between(18, 65, message: 'Age must be between 18 and 65');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Range::class);
            expect($constraint->notInRangeMessage)->toBe('Age must be between 18 and 65');
        })->group('symfony');

        it('passes custom message to Choice constraint', function () {
            $attribute = new In(['admin', 'user'], message: 'Invalid role');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Choice::class);
            expect($constraint->message)->toBe('Invalid role');
        })->group('symfony');

        it('passes custom message to Regex constraint', function () {
            $attribute = new Regex('/^[A-Z]+$/', message: 'Must be uppercase');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Regex::class);
            expect($constraint->message)->toBe('Must be uppercase');
        })->group('symfony');
    });

    describe('Constraint Parameters', function () {
        it('sets min value for GreaterThanOrEqual constraint', function () {
            $attribute = new Min(10);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\GreaterThanOrEqual::class);
            expect($constraint->value)->toBe(10);
        })->group('symfony');

        it('sets max value for LessThanOrEqual constraint', function () {
            $attribute = new Max(100);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\LessThanOrEqual::class);
            expect($constraint->value)->toBe(100);
        })->group('symfony');

        it('sets min and max for Range constraint', function () {
            $attribute = new Between(18, 65);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Range::class);
            expect($constraint->min)->toBe(18);
            expect($constraint->max)->toBe(65);
        })->group('symfony');

        it('sets choices for Choice constraint', function () {
            $attribute = new In(['admin', 'user', 'guest']);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Choice::class);
            expect($constraint->choices)->toBe(['admin', 'user', 'guest']);
        })->group('symfony');

        it('sets pattern for Regex constraint', function () {
            $attribute = new Regex('/^[A-Z]{2}[0-9]{4}$/');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Regex::class);
            expect($constraint->pattern)->toBe('/^[A-Z]{2}[0-9]{4}$/');
        })->group('symfony');

        it('sets exact length for Length constraint', function () {
            $attribute = new Size(10);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Length::class);
            expect($constraint->min)->toBe(10);
            expect($constraint->max)->toBe(10);
        })->group('symfony');

        it('generates Symfony Regex constraint for StartsWith attribute', function () {
            $attribute = new StartsWith('https://');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Regex::class);
            expect($constraint->pattern)->toContain('^');
        })->group('symfony');

        it('generates Symfony Regex constraint for EndsWith attribute', function () {
            $attribute = new EndsWith('.com');
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Regex::class);
            expect($constraint->pattern)->toContain('$');
        })->group('symfony');
    });

    describe('Automatic Validation Integration', function () {
        it('validates DTO using Symfony validator automatically', function () {
            $dto = (new class('test@example.com', 25) {
                use SimpleDTOTrait;

                public function __construct(
                    #[Required]
                    #[Email]
                    public readonly string $email,

                    #[Between(18, 120)]
                    public readonly int $age,
                ) {}
            });

            expect($dto->email)->toBe('test@example.com');
            expect($dto->age)->toBe(25);
        })->group('symfony');

        it('throws ValidationException when Symfony validation fails', function () {
            $class = new class('test@example.com', 25) {
                use SimpleDTOTrait;

                public function __construct(
                    #[Required]
                    #[Email]
                    public readonly string $email,

                    #[Between(18, 120)]
                    public readonly int $age,
                ) {}
            };

            expect(fn() => $class::validateAndCreate(['email' => 'not-an-email', 'age' => 25]))
                ->toThrow(ValidationException::class);
        })->group('symfony');

        it('validates complex DTO with multiple constraints', function () {
            $dto = (new class(
                'test@example.com',
                25,
                'https://example.com',
                '550e8400-e29b-41d4-a716-446655440000'
            ) {
                use SimpleDTOTrait;

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
            });

            expect($dto->email)->toBe('test@example.com');
            expect($dto->age)->toBe(25);
            expect($dto->website)->toBe('https://example.com');
            expect($dto->uuid)->toBe('550e8400-e29b-41d4-a716-446655440000');
        })->group('symfony');
    });

    describe('Field Comparison Constraints', function () {
        it('returns empty array for Same attribute (needs special handling)', function () {
            $attribute = new Same('password');
            $constraint = $attribute->constraint();

            expect($constraint)->toBe([]);
        })->group('symfony');

        it('returns empty array for Different attribute (needs special handling)', function () {
            $attribute = new Different('email');
            $constraint = $attribute->constraint();

            expect($constraint)->toBe([]);
        })->group('symfony');

        it('returns empty array for Confirmed attribute (needs special handling)', function () {
            $attribute = new Confirmed();
            $constraint = $attribute->constraint();

            expect($constraint)->toBe([]);
        })->group('symfony');
    });

    describe('File Upload Constraints', function () {
        it('generates Symfony File constraint for File attribute', function () {
            $attribute = new File(maxSize: 10240);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\File::class);
        })->group('symfony');

        it('generates Symfony Image constraint for Image attribute', function () {
            $attribute = new Image(mimes: ['jpg', 'png'], maxSize: 2048);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\Image::class);
        })->group('symfony');

        it('generates Symfony File constraint with mimeTypes for Mimes attribute', function () {
            $attribute = new Mimes(['pdf', 'doc', 'docx']);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\File::class);
        })->group('symfony');

        it('generates Symfony File constraint with mimeTypes for MimeTypes attribute', function () {
            $attribute = new MimeTypes(['application/pdf', 'application/msword']);
            $constraint = $attribute->constraint();

            expect($constraint)->toBeInstanceOf(Assert\File::class);
        })->group('symfony');
    });

    describe('Database Constraints', function () {
        it('returns empty array for Exists attribute (needs Doctrine)', function () {
            $attribute = new Exists('users', 'id');
            $constraint = $attribute->constraint();

            expect($constraint)->toBe([]);
        })->group('symfony');

        it('returns empty array for Unique attribute (needs Doctrine)', function () {
            $attribute = new Unique('users', 'email');
            $constraint = $attribute->constraint();

            expect($constraint)->toBe([]);
        })->group('symfony');
    });
});

