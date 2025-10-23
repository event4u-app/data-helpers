<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Config\TypeScriptGeneratorOptions;
use event4u\DataHelpers\SimpleDTO\Enums\TypeScriptExportType;
use event4u\DataHelpers\SimpleDTO\TypeScriptGenerator;

echo "================================================================================\n";
echo "TypeScriptGeneratorOptions - Type-Safe Configuration\n";
echo "================================================================================\n\n";

// Example DTO for all examples
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {
    }
}

$generator = new TypeScriptGenerator();

// Example 1: Default Options
echo "Example 1: Default Options\n";
echo "-------------------------\n";
echo "💡 Use TypeScriptGeneratorOptions::default() for standard configuration\n\n";

$options = TypeScriptGeneratorOptions::default();
$typescript = $generator->generate([UserDTO::class], $options);

echo "Configuration:\n";
echo "  - exportType: Export\n";
echo "  - includeComments: true\n";
echo "  - sortProperties: false\n\n";
echo "Generated TypeScript:\n";
echo $typescript;
echo "\n";

// Example 2: Export Factory Method
echo "Example 2: Export Factory Method\n";
echo "--------------------------------\n";
echo "💡 Use TypeScriptGeneratorOptions::export() for exported interfaces\n\n";

$options = TypeScriptGeneratorOptions::export();
$typescript = $generator->generate([UserDTO::class], $options);

echo "Generates: export interface UserDTO { ... }\n";
$pos = strpos($typescript, 'export interface');
echo substr($typescript, false !== $pos ? $pos : 0, 100) . "...\n\n";

// Example 3: Declare Factory Method
echo "Example 3: Declare Factory Method\n";
echo "---------------------------------\n";
echo "💡 Use TypeScriptGeneratorOptions::declare() for declared interfaces\n\n";

$options = TypeScriptGeneratorOptions::declare();
$typescript = $generator->generate([UserDTO::class], $options);

echo "Generates: declare interface UserDTO { ... }\n";
$pos = strpos($typescript, 'declare interface');
echo substr($typescript, false !== $pos ? $pos : 0, 100) . "...\n\n";

// Example 4: Plain Factory Method
echo "Example 4: Plain Factory Method\n";
echo "-------------------------------\n";
echo "💡 Use TypeScriptGeneratorOptions::plain() for plain interfaces (no export/declare)\n\n";

$options = TypeScriptGeneratorOptions::plain();
$typescript = $generator->generate([UserDTO::class], $options);

echo "Generates: interface UserDTO { ... }\n";
$pos = strpos($typescript, ' interface');
echo substr($typescript, false !== $pos ? $pos : 0, 100) . "...\n\n";

// Example 5: Without Comments
echo "Example 5: Without Comments\n";
echo "--------------------------\n";
echo "💡 Use TypeScriptGeneratorOptions::withoutComments() for minimal output\n\n";

$options = TypeScriptGeneratorOptions::withoutComments();
$typescript = $generator->generate([UserDTO::class], $options);

echo "Generated TypeScript (no JSDoc comments):\n";
echo $typescript;
echo "\n";

// Example 6: Sorted Properties
echo "Example 6: Sorted Properties\n";
echo "---------------------------\n";
echo "💡 Use TypeScriptGeneratorOptions::sorted() for alphabetically sorted properties\n\n";

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly bool $inStock,
        public readonly string $category,
    ) {
    }
}

$options = TypeScriptGeneratorOptions::sorted();
$typescript = $generator->generate([ProductDTO::class], $options);

echo "Properties are sorted alphabetically:\n";
echo $typescript;
echo "\n";

// Example 7: Custom Configuration
echo "Example 7: Custom Configuration\n";
echo "-------------------------------\n";
echo "💡 Create custom configuration with constructor\n\n";

$options = new TypeScriptGeneratorOptions(
    exportType: TypeScriptExportType::Declare,
    includeComments: false,
    sortProperties: true,
);

$typescript = $generator->generate([ProductDTO::class], $options);

echo "Configuration:\n";
echo "  - exportType: Declare\n";
echo "  - includeComments: false\n";
echo "  - sortProperties: true\n\n";
echo "Generated TypeScript:\n";
echo $typescript;
echo "\n";

// Example 8: Factory Methods with Parameters
echo "Example 8: Factory Methods with Parameters\n";
echo "------------------------------------------\n";
echo "💡 Factory methods accept optional parameters\n\n";

// Export without comments
$options = TypeScriptGeneratorOptions::export(includeComments: false);
$typescript = $generator->generate([UserDTO::class], $options);

echo "Export without comments:\n";
echo substr($typescript, 0, 200) . "...\n\n";

// Declare with sorted properties
$options = TypeScriptGeneratorOptions::declare(sortProperties: true);
$typescript = $generator->generate([ProductDTO::class], $options);

echo "Declare with sorted properties:\n";
$pos = strpos($typescript, 'declare interface');
echo substr($typescript, false !== $pos ? $pos : 0, 300) . "...\n\n";

// Example 9: Type Safety Benefits
echo "Example 9: Type Safety Benefits\n";
echo "-------------------------------\n";
echo "💡 TypeScriptGeneratorOptions provides compile-time type safety\n\n";

echo "✅ Benefits:\n";
echo "  1. IDE Autocomplete - See all available options while typing\n";
echo "  2. Type Safety - Invalid values are caught at compile time\n";
echo "  3. No Typos - Property names are validated by PHP\n";
echo "  4. Self-Documenting - Options are clearly defined in the class\n";
echo "  5. Refactoring Safe - Rename properties with confidence\n\n";

echo "❌ Old way (arrays - NO LONGER SUPPORTED):\n";
echo "  \$options = ['exportType' => 'export', 'includeComments' => true];\n";
echo "  - No autocomplete\n";
echo "  - Typos not caught: 'exporttype' vs 'exportType'\n";
echo "  - Invalid values only caught at runtime\n\n";

echo "✅ New way (DTOs - REQUIRED):\n";
echo "  \$options = new TypeScriptGeneratorOptions(\n";
echo "      exportType: TypeScriptExportType::Export,\n";
echo "      includeComments: true,\n";
echo "  );\n";
echo "  - Full autocomplete\n";
echo "  - Typos caught at compile time\n";
echo "  - Invalid values caught at compile time\n\n";

// Example 10: Real-World Usage
echo "Example 10: Real-World Usage\n";
echo "----------------------------\n";
echo "💡 Generate TypeScript for multiple DTOs with custom options\n\n";

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly UserDTO $customer,
        public readonly ProductDTO $product,
        public readonly float $total,
    ) {
    }
}

// Generate for production: export, no comments, sorted
$productionOptions = new TypeScriptGeneratorOptions(
    exportType: TypeScriptExportType::Export,
    includeComments: false,
    sortProperties: true,
);

$typescript = $generator->generate(
    [UserDTO::class, ProductDTO::class, OrderDTO::class],
    $productionOptions
);

echo "Production configuration (export, no comments, sorted):\n";
echo substr($typescript, 0, 500) . "...\n\n";

// Generate for development: export, with comments, not sorted
$devOptions = TypeScriptGeneratorOptions::export(
    includeComments: true,
    sortProperties: false
);

$typescript = $generator->generate(
    [UserDTO::class, ProductDTO::class, OrderDTO::class],
    $devOptions
);

echo "Development configuration (export, with comments, not sorted):\n";
echo substr($typescript, 0, 500) . "...\n\n";

echo "================================================================================\n";
echo "✅  All TypeScriptGeneratorOptions examples completed!\n";
echo "================================================================================\n";
