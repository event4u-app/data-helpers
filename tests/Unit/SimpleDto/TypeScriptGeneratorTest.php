<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Computed;
use event4u\DataHelpers\SimpleDto\Attributes\DataCollectionOf;
use event4u\DataHelpers\SimpleDto\Attributes\Lazy;
use event4u\DataHelpers\SimpleDto\Config\TypeScriptGeneratorOptions;
use event4u\DataHelpers\SimpleDto\DataCollection;
use event4u\DataHelpers\SimpleDto\Enums\TypeScriptExportType;
use event4u\DataHelpers\SimpleDto\TypeScriptGenerator;

// Test Dtos for collection test
class TagDtoForTest extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $color,
    ) {}
}

class PostDtoForTest extends SimpleDto
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        #[DataCollectionOf(TagDtoForTest::class)]
        public readonly DataCollection $tags,
    ) {}
}

describe('TypeScriptGenerator', function(): void {
    it('generates basic interface', function(): void {
        $dto = new class('', '', 0) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly string $email,
                public readonly int $age,
            ) {}
        };

        $generator = new TypeScriptGenerator();
        $typescript = $generator->generate([$dto::class]);

        expect($typescript)->toContain('interface');
        expect($typescript)->toContain('name: string');
        expect($typescript)->toContain('email: string');
        expect($typescript)->toContain('age: number');
    });

    it('handles nullable types', function(): void {
        $dto = new class('', null) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly ?string $phone,
            ) {}
        };

        $generator = new TypeScriptGenerator();
        $typescript = $generator->generate([$dto::class]);

        expect($typescript)->toContain('name: string');
        expect($typescript)->toContain('phone: string | null');
    });

    it('handles casts', function(): void {
        $dto = new class('', false, new DateTimeImmutable()) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly bool $isActive,
                public readonly DateTimeImmutable $createdAt,
            ) {}

            /** @return array<string, string> */
            protected function casts(): array
            {
                return [
                    'isActive' => 'boolean',
                    'createdAt' => 'datetime',
                ];
            }
        };

        $generator = new TypeScriptGenerator();
        $typescript = $generator->generate([$dto::class]);

        expect($typescript)->toContain('isActive: boolean');
        expect($typescript)->toContain('createdAt: string');
    });

    it('handles enums', function(): void {
        enum TypeScriptGeneratorTest_Status: string
        {
            case ACTIVE = 'active';
            case INACTIVE = 'inactive';
        }

        $dto = new class(TypeScriptGeneratorTest_Status::ACTIVE) extends SimpleDto {
            public function __construct(
                public readonly TypeScriptGeneratorTest_Status $status,
            ) {}

            /** @return array<string, string> */
            protected function casts(): array
            {
                return [
                    'status' => 'enum:' . TypeScriptGeneratorTest_Status::class,
                ];
            }
        };

        $generator = new TypeScriptGenerator();
        $typescript = $generator->generate([$dto::class]);

        expect($typescript)->toContain("status: 'active' | 'inactive'");
    });

    it('handles nested Dtos', function(): void {
        $addressDto = new class('', '', '') extends SimpleDto {
            public function __construct(
                public readonly string $street,
                public readonly string $city,
                public readonly string $country,
            ) {}
        };

        $userDto = new class('', $addressDto) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly object $address,
            ) {}
        };

        $generator = new TypeScriptGenerator();
        $typescript = $generator->generate([$userDto::class]);

        // Should generate both interfaces
        expect($typescript)->toContain('interface');
        expect($typescript)->toContain('name: string');
    });

    it('handles collections', function(): void {
        $generator = new TypeScriptGenerator();
        $typescript = $generator->generate([PostDtoForTest::class]);

        // Should generate array type
        expect($typescript)->toContain('tags: TagDtoForTest[]');
        expect($typescript)->toContain('interface TagDtoForTest');
        expect($typescript)->toContain('interface PostDtoForTest');
    });

    it('handles computed properties', function(): void {
        $dto = new class('John', 'Doe') extends SimpleDto {
            public function __construct(
                public readonly string $firstName,
                public readonly string $lastName,
            ) {}

            #[Computed]
            public function fullName(): string
            {
                return sprintf('%s %s', $this->firstName, $this->lastName);
            }
        };

        $generator = new TypeScriptGenerator();
        $typescript = $generator->generate([$dto::class]);

        expect($typescript)->toContain('firstName: string');
        expect($typescript)->toContain('lastName: string');
        expect($typescript)->toContain('fullName: string');
    });

    it('handles lazy computed properties', function(): void {
        $dto = new class('John', 30) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly int $age,
            ) {}

            #[Computed(lazy: true)]
            public function isAdult(): bool
            {
                return 18 <= $this->age;
            }
        };

        $generator = new TypeScriptGenerator();
        $typescript = $generator->generate([$dto::class]);

        expect($typescript)->toContain('name: string');
        expect($typescript)->toContain('age: number');
        expect($typescript)->toContain('isAdult?: boolean');
    });

    it('handles lazy properties', function(): void {
        $dto = new class('', '', '') extends SimpleDto {
            public function __construct(
                public readonly string $title,
                public readonly string $summary,
                #[Lazy]
                public readonly string $content,
            ) {}
        };

        $generator = new TypeScriptGenerator();
        $typescript = $generator->generate([$dto::class]);

        expect($typescript)->toContain('title: string');
        expect($typescript)->toContain('summary: string');
        expect($typescript)->toContain('content?: string');
    });

    it('supports different export types', function(): void {
        $dto = new class('') extends SimpleDto {
            public function __construct(
                public readonly string $name,
            ) {}
        };

        $generator = new TypeScriptGenerator();

        // Export
        $exportOptions = new TypeScriptGeneratorOptions(exportType: TypeScriptExportType::Export);
        $typescript = $generator->generate([$dto::class], $exportOptions);
        expect($typescript)->toContain('export interface');

        // Declare
        $declareOptions = new TypeScriptGeneratorOptions(exportType: TypeScriptExportType::Declare);
        $typescript = $generator->generate([$dto::class], $declareOptions);
        expect($typescript)->toContain('declare interface');

        // None
        $noneOptions = new TypeScriptGeneratorOptions(exportType: TypeScriptExportType::None);
        $typescript = $generator->generate([$dto::class], $noneOptions);
        expect($typescript)->toContain(' interface');
        expect($typescript)->not->toContain('export interface');
        expect($typescript)->not->toContain('declare interface');
    });

    it('includes header comment', function(): void {
        $dto = new class('') extends SimpleDto {
            public function __construct(
                public readonly string $name,
            ) {}
        };

        $generator = new TypeScriptGenerator();
        $options = TypeScriptGeneratorOptions::default();
        $typescript = $generator->generate([$dto::class], $options);

        expect($typescript)->toContain('Auto-generated TypeScript interfaces');
        expect($typescript)->toContain('DO NOT EDIT THIS FILE MANUALLY');
    });

    it('supports includeComments option', function(): void {
        $dto = new class('') extends SimpleDto {
            public function __construct(
                public readonly string $name,
            ) {}
        };

        $generator = new TypeScriptGenerator();

        // With comments
        $withCommentsOptions = new TypeScriptGeneratorOptions(includeComments: true);
        $typescript = $generator->generate([$dto::class], $withCommentsOptions);
        expect($typescript)->toContain('/**');
        expect($typescript)->toContain('Generated from:');

        // Without comments
        $withoutCommentsOptions = new TypeScriptGeneratorOptions(includeComments: false);
        $typescript = $generator->generate([$dto::class], $withoutCommentsOptions);
        expect($typescript)->not->toContain('Generated from:');
    });

    it('handles multiple Dtos', function(): void {
        $dto1 = new class('') extends SimpleDto {
            public function __construct(
                public readonly string $name,
            ) {}
        };

        $dto2 = new class('') extends SimpleDto {
            public function __construct(
                public readonly string $email,
            ) {}
        };

        $generator = new TypeScriptGenerator();
        $typescript = $generator->generate([$dto1::class, $dto2::class]);

        // Should contain both interfaces
        expect($typescript)->toContain('name: string');
        expect($typescript)->toContain('email: string');
    });
});
