<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Sanitize;
use event4u\DataHelpers\SimpleDto\Attributes\Trim;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;

// Test DTO: applies trim to all string properties
#[Trim]
class TrimClassLevel_AppliesTrimToAllStringProperties_Dto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly int $price,
    ) {}
}

// Test DTO: property-level trim overrides class-level trim
#[Trim]
class TrimClassLevel_PropertyLevelOverridesClassLevel_Dto extends SimpleDto
{
    public function __construct(
        public readonly string $text1,
        #[Trim(characters: " \t\n\r_")]
        public readonly string $text2,
        public readonly string $text3,
    ) {}
}

// Test DTO: does not affect non-string properties
#[Trim]
class TrimClassLevel_DoesNotAffectNonStringProperties_Dto extends SimpleDto
{
    public function __construct(
        public readonly string $text,
        public readonly int $number,
        public readonly float $decimal,
        public readonly bool $flag,
        public readonly array $items,
    ) {}
}

// Test DTO: class-level attributes follow same order
#[Sanitize]
#[Trim]
#[ConvertEmptyToNull]
class TrimClassLevel_ClassLevelAttributesFollowSameOrder_Dto extends SimpleDto
{
    public function __construct(
        public readonly ?string $text1,
        public readonly ?string $text2,
    ) {}
}

// Test DTO: handles custom trim characters
class TrimClassLevel_HandlesCustomTrimCharacters_Dto extends SimpleDto
{
    public function __construct(
        #[Trim(characters: " \t\n\r_-")]
        public readonly string $text,
    ) {}
}

describe('Trim Attribute - Class Level', function (): void {
    describe('Class-Level Application', function (): void {
        it('applies trim to all string properties', function (): void {
            $dto = TrimClassLevel_AppliesTrimToAllStringProperties_Dto::from([
                'name' => '  Product Name  ',
                'description' => '  Product Description  ',
                'price' => 100,
            ]);

            expect($dto->name)->toBe('Product Name');
            expect($dto->description)->toBe('Product Description');
            expect($dto->price)->toBe(100);
        });

        it('property-level trim overrides class-level trim', function (): void {
            $dto = TrimClassLevel_PropertyLevelOverridesClassLevel_Dto::from([
                'text1' => '  Text 1  ',
                'text2' => '__Text 2__',
                'text3' => '  Text 3  ',
            ]);

            expect($dto->text1)->toBe('Text 1');
            expect($dto->text2)->toBe('Text 2');
            expect($dto->text3)->toBe('Text 3');
        });

        it('does not affect non-string properties', function (): void {
            $dto = TrimClassLevel_DoesNotAffectNonStringProperties_Dto::from([
                'text' => '  Text  ',
                'number' => 42,
                'decimal' => 3.14,
                'flag' => true,
                'items' => ['a', 'b'],
            ]);

            expect($dto->text)->toBe('Text');
            expect($dto->number)->toBe(42);
            expect($dto->decimal)->toBe(3.14);
            expect($dto->flag)->toBe(true);
            expect($dto->items)->toBe(['a', 'b']);
        });
    });

    describe('Transform Order: Sanitize -> Trim -> ConvertEmptyToNull', function (): void {
        it('applies sanitize before trim', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '  <p>Hello World</p>  ',
            ]);

            // Sanitize removes HTML, then Trim removes whitespace
            expect($dto->text)->toBe('Hello World');
        });

        it('applies trim before ConvertEmptyToNull', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Trim]
                    #[ConvertEmptyToNull]
                    public readonly ?string $text,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '   ',
            ]);

            // Trim converts '   ' to '', then ConvertEmptyToNull converts '' to null
            expect($dto->text)->toBeNull();
        });

        it('applies all three in correct order', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    #[ConvertEmptyToNull]
                    public readonly ?string $text,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '  <p>   </p>  ',
            ]);

            // Sanitize removes HTML -> '     '
            // Trim removes whitespace -> ''
            // ConvertEmptyToNull converts '' to null
            expect($dto->text)->toBeNull();
        });

        it('class-level attributes follow same order', function (): void {
            $dto = TrimClassLevel_ClassLevelAttributesFollowSameOrder_Dto::from([
                'text1' => '  <p>Hello</p>  ',
                'text2' => '  <p>   </p>  ',
            ]);

            expect($dto->text1)->toBe('Hello');
            expect($dto->text2)->toBeNull();
        });
    });

    describe('Edge Cases', function (): void {
        it('handles null values', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                #[Trim]
                public function __construct(
                    public readonly ?string $text,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => null,
            ]);

            expect($dto->text)->toBeNull();
        });

        it('handles empty strings', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                #[Trim]
                public function __construct(
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '',
            ]);

            expect($dto->text)->toBe('');
        });

        it('handles custom trim characters', function (): void {
            $dto = TrimClassLevel_HandlesCustomTrimCharacters_Dto::from([
                'text' => '__--Text--__',
            ]);

            expect($dto->text)->toBe('Text');
        });
    });
});

