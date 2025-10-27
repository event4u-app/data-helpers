<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Computed;
use event4u\DataHelpers\SimpleDto\Attributes\DataCollectionOf;
use event4u\DataHelpers\SimpleDto\Attributes\Lazy;
use event4u\DataHelpers\SimpleDto\Config\TypeScriptGeneratorOptions;
use event4u\DataHelpers\SimpleDto\DataCollection;
use event4u\DataHelpers\SimpleDto\TypeScriptGenerator;

echo "================================================================================\n";
echo "SimpleDto - TypeScript Generation Examples\n";
echo "================================================================================\n\n";

// Example 1: Basic Dto
echo "Example 1: Basic Dto\n";
echo "-------------------\n";

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
        public readonly ?string $phone = null,
    ) {}
}

$generator = new TypeScriptGenerator();
$options = TypeScriptGeneratorOptions::default();
$typescript = $generator->generate([UserDto::class], $options);

echo $typescript;
echo "\n";

// Example 2: Dto with Casts
echo "Example 2: Dto with Casts\n";
echo "-------------------------\n";

enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

class ProductDto extends SimpleDto
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

$typescript = $generator->generate([ProductDto::class], $options);
echo $typescript;
echo "\n";

// Example 3: Nested Dtos
echo "Example 3: Nested Dtos\n";
echo "----------------------\n";

class AddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class CompanyDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDto $address,
    ) {}
}

$typescript = $generator->generate([CompanyDto::class], $options);
echo $typescript;
echo "\n";

// Example 4: Dto with Collections
echo "Example 4: Dto with Collections\n";
echo "-------------------------------\n";

class TagDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $color,
    ) {}
}

class PostDto extends SimpleDto
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        #[DataCollectionOf(TagDto::class)]
        public readonly DataCollection $tags,
    ) {}
}

$typescript = $generator->generate([PostDto::class], $options);
echo $typescript;
echo "\n";

// Example 5: Dto with Computed Properties
echo "Example 5: Dto with Computed Properties\n";
echo "---------------------------------------\n";

class PersonDto extends SimpleDto
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly int $age,
    ) {}

    #[Computed]
    public function fullName(): string
    {
        return sprintf('%s %s', $this->firstName, $this->lastName);
    }

    #[Computed(lazy: true)]
    public function isAdult(): bool
    {
        return 18 <= $this->age;
    }
}

$typescript = $generator->generate([PersonDto::class], $options);
echo $typescript;
echo "\n";

// Example 6: Dto with Lazy Properties
echo "Example 6: Dto with Lazy Properties\n";
echo "-----------------------------------\n";

class DocumentDto extends SimpleDto
{
    /** @param array<mixed> $metadata */
    public function __construct(
        public readonly string $title,
        public readonly string $summary,
        #[Lazy]
        public readonly string $content,
        #[Lazy]
        public readonly array $metadata,
    ) {}
}

$typescript = $generator->generate([DocumentDto::class], $options);
echo $typescript;
echo "\n";

// Example 7: Multiple Dtos at Once
echo "Example 7: Multiple Dtos at Once\n";
echo "--------------------------------\n";

class AuthorDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

class BookDto extends SimpleDto
{
    public function __construct(
        public readonly string $title,
        public readonly string $isbn,
        public readonly AuthorDto $author,
    ) {}
}

class LibraryDto extends SimpleDto
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $name,
        #[DataCollectionOf(BookDto::class)]
        public readonly DataCollection $books,
    ) {}
}

$typescript = $generator->generate([LibraryDto::class, AuthorDto::class, BookDto::class], $options);
echo $typescript;
echo "\n";

// Example 8: Generate to File with Custom Options
echo "Example 8: Generate to File with Custom Options\n";
echo "-----------------------------------------------\n";

$outputPath = __DIR__ . '/../storage/generated-types.ts';

// Ensure directory exists
$dir = dirname($outputPath);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// Use sorted() factory method for alphabetically sorted properties
$sortedOptions = TypeScriptGeneratorOptions::sorted();
$typescript = $generator->generate(
    [UserDto::class, ProductDto::class, CompanyDto::class, PostDto::class],
    $sortedOptions
);

file_put_contents($outputPath, $typescript);

echo sprintf('✅  TypeScript interfaces generated to: %s%s', $outputPath, PHP_EOL);
echo "File size: " . filesize($outputPath) . " bytes\n\n";

unlink($outputPath);

// Example 9: Different Export Types
echo "Example 9: Different Export Types\n";
echo "---------------------------------\n";
echo "💡 Use TypeScriptGeneratorOptions factory methods for type-safe configuration!\n\n";

// Export (default)
$exportOptions = TypeScriptGeneratorOptions::export();
$typescript = $generator->generate([UserDto::class], $exportOptions);
echo "Export:\n{$typescript}\n";

// Declare
$declareOptions = TypeScriptGeneratorOptions::declare();
$typescript = $generator->generate([UserDto::class], $declareOptions);
echo "Declare:\n{$typescript}\n";

// Plain (no export/declare)
$plainOptions = TypeScriptGeneratorOptions::plain();
$typescript = $generator->generate([UserDto::class], $plainOptions);
echo "Plain:\n{$typescript}\n";

// Without comments
$noCommentsOptions = TypeScriptGeneratorOptions::withoutComments();
$typescript = $generator->generate([UserDto::class], $noCommentsOptions);
echo "Without Comments:\n{$typescript}\n";

echo "================================================================================\n";
echo "✅  All TypeScript generation examples completed!\n";
echo "================================================================================\n";
