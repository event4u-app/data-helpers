<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;
use event4u\DataHelpers\SimpleDto\Attributes\Sanitize;
use event4u\DataHelpers\SimpleDto\Attributes\Trim;
use event4u\DataHelpers\SimpleDto\Attributes\UltraFast;

// UltraFast DTO: Property-Level Sanitize
#[UltraFast]
class UltraFastTransform_PropertyLevelSanitize_Dto extends SimpleDto
{
    public function __construct(
        #[Sanitize]
        public readonly string $text,
        public readonly int $id,
    ) {}
}

// UltraFast DTO: Property-Level Trim
#[UltraFast]
class UltraFastTransform_PropertyLevelTrim_Dto extends SimpleDto
{
    public function __construct(
        #[Trim]
        public readonly string $text,
        public readonly int $id,
    ) {}
}

// UltraFast DTO: Property-Level Sanitize + Trim
#[UltraFast]
class UltraFastTransform_PropertyLevelSanitizeAndTrim_Dto extends SimpleDto
{
    public function __construct(
        #[Sanitize]
        #[Trim]
        public readonly string $text,
        public readonly int $id,
    ) {}
}

// UltraFast DTO: Property-Level Sanitize + Trim + ConvertEmptyToNull
#[UltraFast]
class UltraFastTransform_PropertyLevelAllThree_Dto extends SimpleDto
{
    public function __construct(
        #[Sanitize]
        #[Trim]
        #[ConvertEmptyToNull]
        public readonly ?string $text,
        public readonly int $id,
    ) {}
}

// UltraFast DTO: Class-Level Sanitize
#[UltraFast]
#[Sanitize]
class UltraFastTransform_ClassLevelSanitize_Dto extends SimpleDto
{
    public function __construct(
        public readonly string $text1,
        public readonly string $text2,
        public readonly int $id,
    ) {}
}

// UltraFast DTO: Class-Level Trim
#[UltraFast]
#[Trim]
class UltraFastTransform_ClassLevelTrim_Dto extends SimpleDto
{
    public function __construct(
        public readonly string $text1,
        public readonly string $text2,
        public readonly int $id,
    ) {}
}

// UltraFast DTO: Class-Level Sanitize + Trim
#[UltraFast]
#[Sanitize]
#[Trim]
class UltraFastTransform_ClassLevelSanitizeAndTrim_Dto extends SimpleDto
{
    public function __construct(
        public readonly string $text1,
        public readonly string $text2,
        public readonly int $id,
    ) {}
}

// UltraFast DTO: Class-Level Sanitize + Trim + ConvertEmptyToNull
#[UltraFast]
#[Sanitize]
#[Trim]
#[ConvertEmptyToNull]
class UltraFastTransform_ClassLevelAllThree_Dto extends SimpleDto
{
    public function __construct(
        public readonly ?string $text1,
        public readonly ?string $text2,
        public readonly int $id,
    ) {}
}

// UltraFast DTO: Multiple Class-Level Transforms (Sanitize + Trim + ConvertEmptyToNull)
// This is already tested above, so we use a different combination for edge cases
#[UltraFast]
#[Sanitize(stripHtml: true, normalizeWhitespace: true)]
#[Trim]
class UltraFastTransform_MultipleClassLevelTransforms_Dto extends SimpleDto
{
    public function __construct(
        public readonly string $text1,
        public readonly string $text2,
        public readonly int $id,
    ) {}
}

describe('UltraFast Mode - Transform Order', function(): void {
    describe('Property-Level Transforms', function(): void {
        it('applies Sanitize in UltraFast mode', function(): void {
            $dto = UltraFastTransform_PropertyLevelSanitize_Dto::from([
                'text' => '  <p>Hello World</p>  ',
                'id' => 1,
            ]);

            expect($dto->text)->toBe('Hello World');
            expect($dto->id)->toBe(1);
        });

        it('applies Trim in UltraFast mode', function(): void {
            $dto = UltraFastTransform_PropertyLevelTrim_Dto::from([
                'text' => '  Hello World  ',
                'id' => 2,
            ]);

            expect($dto->text)->toBe('Hello World');
            expect($dto->id)->toBe(2);
        });

        it('applies Sanitize then Trim in UltraFast mode', function(): void {
            $dto = UltraFastTransform_PropertyLevelSanitizeAndTrim_Dto::from([
                'text' => '  <p>Hello World</p>  ',
                'id' => 3,
            ]);

            // Sanitize removes HTML: '  Hello World  '
            // Trim removes whitespace: 'Hello World'
            expect($dto->text)->toBe('Hello World');
            expect($dto->id)->toBe(3);
        });

        it('applies Sanitize -> Trim -> ConvertEmptyToNull in UltraFast mode', function(): void {
            $dto = UltraFastTransform_PropertyLevelAllThree_Dto::from([
                'text' => '  <p>   </p>  ',
                'id' => 4,
            ]);

            // Sanitize removes HTML: '     '
            // Trim removes whitespace: ''
            // ConvertEmptyToNull converts '' to null
            expect($dto->text)->toBeNull();
            expect($dto->id)->toBe(4);
        });
    });

    describe('Class-Level Transforms', function(): void {
        it('applies Class-Level Sanitize in UltraFast mode', function(): void {
            $dto = UltraFastTransform_ClassLevelSanitize_Dto::from([
                'text1' => '<p>Hello</p>',
                'text2' => '<strong>World</strong>',
                'id' => 5,
            ]);

            expect($dto->text1)->toBe('Hello');
            expect($dto->text2)->toBe('World');
            expect($dto->id)->toBe(5);
        });

        it('applies Class-Level Trim in UltraFast mode', function(): void {
            $dto = UltraFastTransform_ClassLevelTrim_Dto::from([
                'text1' => '  Hello  ',
                'text2' => '  World  ',
                'id' => 6,
            ]);

            expect($dto->text1)->toBe('Hello');
            expect($dto->text2)->toBe('World');
            expect($dto->id)->toBe(6);
        });

        it('applies Class-Level Sanitize + Trim in UltraFast mode', function(): void {
            $dto = UltraFastTransform_ClassLevelSanitizeAndTrim_Dto::from([
                'text1' => '  <p>Hello</p>  ',
                'text2' => '  <strong>World</strong>  ',
                'id' => 7,
            ]);

            expect($dto->text1)->toBe('Hello');
            expect($dto->text2)->toBe('World');
            expect($dto->id)->toBe(7);
        });

        it('applies Class-Level Sanitize -> Trim -> ConvertEmptyToNull in UltraFast mode', function(): void {
            $dto = UltraFastTransform_ClassLevelAllThree_Dto::from([
                'text1' => '  <p>Hello</p>  ',
                'text2' => '  <p>   </p>  ',
                'id' => 8,
            ]);

            expect($dto->text1)->toBe('Hello');
            expect($dto->text2)->toBeNull();
            expect($dto->id)->toBe(8);
        });
    });

    describe('Multiple Class-Level Transforms', function(): void {
        it('applies multiple transforms in correct order (Sanitize -> Trim)', function(): void {
            $dto = UltraFastTransform_MultipleClassLevelTransforms_Dto::from([
                'text1' => '  <p>Hello    World</p>  ',
                'text2' => '  <strong>Test    Text</strong>  ',
                'id' => 9,
            ]);

            // Sanitize removes HTML and normalizes whitespace: '  Hello World  ', '  Test Text  '
            // Trim removes leading/trailing whitespace: 'Hello World', 'Test Text'
            expect($dto->text1)->toBe('Hello World');
            expect($dto->text2)->toBe('Test Text');
            expect($dto->id)->toBe(9);
        });
    });

    describe('Performance', function(): void {
        it('maintains ultra-fast performance with Sanitize + Trim', function(): void {
            $iterations = 1000;
            $start = microtime(true);

            for ($i = 0; $i < $iterations; $i++) {
                UltraFastTransform_PropertyLevelSanitizeAndTrim_Dto::from([
                    'text' => '  <p>Hello World</p>  ',
                    'id' => $i,
                ]);
            }

            $duration = microtime(true) - $start;
            $avgTime = ($duration / $iterations) * 1000000; // Convert to microseconds

            // Should still be fast (< 15μs per operation with transforms)
            expect($avgTime)->toBeLessThan(15.0);
        });
    });
});
