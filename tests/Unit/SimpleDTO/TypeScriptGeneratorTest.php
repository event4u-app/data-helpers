<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Computed;
use event4u\DataHelpers\SimpleDTO\Attributes\DataCollectionOf;
use event4u\DataHelpers\SimpleDTO\Attributes\Lazy;
use event4u\DataHelpers\SimpleDTO\DataCollection;
use event4u\DataHelpers\SimpleDTO\Enums\TypeScriptExportType;
use event4u\DataHelpers\SimpleDTO\TypeScriptGenerator;

// Test DTOs for collection test
class TagDTOForTest extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $color,
    ) {}
}

class PostDTOForTest extends SimpleDTO
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        #[DataCollectionOf(TagDTOForTest::class)]
        public readonly DataCollection $tags,
    ) {}
}

describe('TypeScriptGenerator', function(): void {
    it('generates basic interface', function(): void {
        $dto = new class('', '', 0) extends SimpleDTO {
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
        $dto = new class('', null) extends SimpleDTO {
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
        $dto = new class('', false, new DateTimeImmutable()) extends SimpleDTO {
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
        enum Status: string
        {
            case ACTIVE = 'active';
            case INACTIVE = 'inactive';
        }

        $dto = new class(Status::ACTIVE) extends SimpleDTO {
            public function __construct(
                public readonly Status $status,
            ) {}

            /** @return array<string, string> */
            protected function casts(): array
            {
                return [
                    'status' => 'enum:' . Status::class,
                ];
            }
        };

        $generator = new TypeScriptGenerator();
        $typescript = $generator->generate([$dto::class]);

        expect($typescript)->toContain("status: 'active' | 'inactive'");
    });

    it('handles nested DTOs', function(): void {
        $addressDto = new class('', '', '') extends SimpleDTO {
            public function __construct(
                public readonly string $street,
                public readonly string $city,
                public readonly string $country,
            ) {}
        };

        $userDto = new class('', $addressDto) extends SimpleDTO {
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
        $typescript = $generator->generate([PostDTOForTest::class]);

        // Should generate array type
        expect($typescript)->toContain('tags: TagDTOForTest[]');
        expect($typescript)->toContain('interface TagDTOForTest');
        expect($typescript)->toContain('interface PostDTOForTest');
    });

    it('handles computed properties', function(): void {
        $dto = new class('John', 'Doe') extends SimpleDTO {
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
        $dto = new class('John', 30) extends SimpleDTO {
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
        $dto = new class('', '', '') extends SimpleDTO {
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
        $dto = new class('') extends SimpleDTO {
            public function __construct(
                public readonly string $name,
            ) {}
        };

        $generator = new TypeScriptGenerator();

        // Export with enum
        $typescript = $generator->generate([$dto::class], ['exportType' => TypeScriptExportType::Export]);
        expect($typescript)->toContain('export interface');

        // Declare with enum
        $typescript = $generator->generate([$dto::class], ['exportType' => TypeScriptExportType::Declare]);
        expect($typescript)->toContain('declare interface');

        // None with enum
        $typescript = $generator->generate([$dto::class], ['exportType' => TypeScriptExportType::None]);
        expect($typescript)->toContain(' interface');
        expect($typescript)->not->toContain('export interface');
        expect($typescript)->not->toContain('declare interface');

        // Export with string (BC)
        $typescript = $generator->generate([$dto::class], ['exportType' => 'export']);
        expect($typescript)->toContain('export interface');

        // Declare with string (BC)
        $typescript = $generator->generate([$dto::class], ['exportType' => 'declare']);
        expect($typescript)->toContain('declare interface');

        // No export with empty string (BC)
        $typescript = $generator->generate([$dto::class], ['exportType' => '']);
        expect($typescript)->toContain(' interface');
        expect($typescript)->not->toContain('export interface');
        expect($typescript)->not->toContain('declare interface');
    });

    it('includes header comment', function(): void {
        $dto = new class('') extends SimpleDTO {
            public function __construct(
                public readonly string $name,
            ) {}
        };

        $generator = new TypeScriptGenerator();
        $typescript = $generator->generate([$dto::class]);

        expect($typescript)->toContain('Auto-generated TypeScript interfaces');
        expect($typescript)->toContain('DO NOT EDIT THIS FILE MANUALLY');
    });

    it('supports includeComments option', function(): void {
        $dto = new class('') extends SimpleDTO {
            public function __construct(
                public readonly string $name,
            ) {}
        };

        $generator = new TypeScriptGenerator();

        // With comments
        $typescript = $generator->generate([$dto::class], ['includeComments' => true]);
        expect($typescript)->toContain('/**');
        expect($typescript)->toContain('Generated from:');

        // Without comments
        $typescript = $generator->generate([$dto::class], ['includeComments' => false]);
        expect($typescript)->not->toContain('Generated from:');
    });

    it('handles multiple DTOs', function(): void {
        $dto1 = new class('') extends SimpleDTO {
            public function __construct(
                public readonly string $name,
            ) {}
        };

        $dto2 = new class('') extends SimpleDTO {
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
