<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Computed;
use event4u\DataHelpers\SimpleDTO\Attributes\DataCollectionOf;
use event4u\DataHelpers\SimpleDTO\Attributes\Lazy;
use event4u\DataHelpers\SimpleDTO\DataCollection;
use event4u\DataHelpers\SimpleDTO\TypeScriptGenerator;

echo "================================================================================\n";
echo "SimpleDTO - TypeScript Generation Examples\n";
echo "================================================================================\n\n";

// Example 1: Basic DTO
echo "Example 1: Basic DTO\n";
echo "-------------------\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
        public readonly ?string $phone = null,
    ) {}
}

$generator = new TypeScriptGenerator();
$typescript = $generator->generate([UserDTO::class]);

echo $typescript;
echo "\n";

// Example 2: DTO with Casts
echo "Example 2: DTO with Casts\n";
echo "-------------------------\n";

enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly bool $inStock,
        public readonly DateTimeImmutable $createdAt,
        public readonly Status $status,
    ) {}

    protected function casts(): array
    {
        return [
            'inStock' => 'boolean',
            'createdAt' => 'datetime',
            'status' => 'enum:' . Status::class,
        ];
    }
}

$typescript = $generator->generate([ProductDTO::class]);
echo $typescript;
echo "\n";

// Example 3: Nested DTOs
echo "Example 3: Nested DTOs\n";
echo "----------------------\n";

class AddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class CompanyDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDTO $address,
    ) {}
}

$typescript = $generator->generate([CompanyDTO::class]);
echo $typescript;
echo "\n";

// Example 4: DTO with Collections
echo "Example 4: DTO with Collections\n";
echo "-------------------------------\n";

class TagDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $color,
    ) {}
}

class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        #[DataCollectionOf(TagDTO::class)]
        public readonly DataCollection $tags,
    ) {}
}

$typescript = $generator->generate([PostDTO::class]);
echo $typescript;
echo "\n";

// Example 5: DTO with Computed Properties
echo "Example 5: DTO with Computed Properties\n";
echo "---------------------------------------\n";

class PersonDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly int $age,
    ) {}

    #[Computed]
    public function fullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    #[Computed(lazy: true)]
    public function isAdult(): bool
    {
        return $this->age >= 18;
    }
}

$typescript = $generator->generate([PersonDTO::class]);
echo $typescript;
echo "\n";

// Example 6: DTO with Lazy Properties
echo "Example 6: DTO with Lazy Properties\n";
echo "-----------------------------------\n";

class DocumentDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $summary,
        #[Lazy]
        public readonly string $content,
        #[Lazy]
        public readonly array $metadata,
    ) {}
}

$typescript = $generator->generate([DocumentDTO::class]);
echo $typescript;
echo "\n";

// Example 7: Multiple DTOs at Once
echo "Example 7: Multiple DTOs at Once\n";
echo "--------------------------------\n";

class AuthorDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

class BookDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $isbn,
        public readonly AuthorDTO $author,
    ) {}
}

class LibraryDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[DataCollectionOf(BookDTO::class)]
        public readonly DataCollection $books,
    ) {}
}

$typescript = $generator->generate([LibraryDTO::class, AuthorDTO::class, BookDTO::class]);
echo $typescript;
echo "\n";

// Example 8: Generate to File
echo "Example 8: Generate to File\n";
echo "---------------------------\n";

$outputPath = __DIR__ . '/../storage/generated-types.ts';

// Ensure directory exists
$dir = dirname($outputPath);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$typescript = $generator->generate(
    [UserDTO::class, ProductDTO::class, CompanyDTO::class, PostDTO::class],
    [
        'exportType' => 'export',
        'includeComments' => true,
        'sortProperties' => true,
    ]
);

file_put_contents($outputPath, $typescript);

echo "✅  TypeScript interfaces generated to: {$outputPath}\n";
echo "File size: " . filesize($outputPath) . " bytes\n\n";

// Example 9: Different Export Types
echo "Example 9: Different Export Types\n";
echo "---------------------------------\n";

// Export (default)
$typescript = $generator->generate([UserDTO::class], ['exportType' => 'export']);
echo "Export:\n{$typescript}\n";

// Declare
$typescript = $generator->generate([UserDTO::class], ['exportType' => 'declare']);
echo "Declare:\n{$typescript}\n";

// No export
$typescript = $generator->generate([UserDTO::class], ['exportType' => '']);
echo "No export:\n{$typescript}\n";

echo "================================================================================\n";
echo "✅  All TypeScript generation examples completed!\n";
echo "================================================================================\n";

