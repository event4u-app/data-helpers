<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Config\SerializerOptions;
use event4u\DataHelpers\SimpleDTO\Enums\SerializationFormat;

echo "================================================================================\n";
echo "SerializerOptions - Type-Safe Serialization Configuration\n";
echo "================================================================================\n\n";

// Example DTO
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

$product = new ProductDTO(
    name: 'Laptop',
    price: 999.99,
    inStock: true,
    category: 'Electronics'
);

// Example 1: Default Options
echo "Example 1: Default Options\n";
echo "-------------------------\n";
echo "💡 Use SerializerOptions::default() for standard configuration\n\n";

$options = SerializerOptions::default();
$xml = $product->toXml($options);

echo "Default XML (root='root', encoding='UTF-8'):\n";
echo substr($xml, 0, 200) . "...\n\n";

// Example 2: XML Factory Method
echo "Example 2: XML Factory Method\n";
echo "-----------------------------\n";
echo "💡 Use SerializerOptions::xml() for XML-specific configuration\n\n";

$options = SerializerOptions::xml(rootElement: 'product', encoding: 'UTF-8');
$xml = $product->toXml($options);

echo "Custom XML (root='product'):\n";
echo $xml . "\n";

// Example 3: YAML Factory Method
echo "Example 3: YAML Factory Method\n";
echo "------------------------------\n";
echo "💡 Use SerializerOptions::yaml() for YAML-specific configuration\n\n";

$options = SerializerOptions::yaml(indent: 4);
$yaml = $product->toYaml($options);

echo "YAML with 4-space indent:\n";
echo $yaml . "\n";

// Example 4: CSV Factory Method
echo "Example 4: CSV Factory Method\n";
echo "-----------------------------\n";
echo "💡 Use SerializerOptions::csv() for CSV-specific configuration\n\n";

$options = SerializerOptions::csv(includeHeaders: true, delimiter: ',');
$csv = $product->toCsv($options);

echo "CSV with headers:\n";
echo $csv . "\n";

// Example 5: CSV without Headers
echo "Example 5: CSV without Headers\n";
echo "------------------------------\n";

$options = SerializerOptions::csv(includeHeaders: false);
$csv = $product->toCsv($options);

echo "CSV without headers:\n";
echo $csv . "\n\n";

// Example 6: TSV (Tab-Separated Values)
echo "Example 6: TSV (Tab-Separated Values)\n";
echo "-------------------------------------\n";
echo "💡 Use SerializerOptions::tsv() for tab-separated values\n\n";

$options = SerializerOptions::tsv();
$tsv = $product->toCsv($options);

echo "TSV Output:\n";
echo $tsv . "\n";

// Example 7: European CSV (Semicolon)
echo "Example 7: European CSV (Semicolon)\n";
echo "-----------------------------------\n";
echo "💡 Use SerializerOptions::csvSemicolon() for European CSV format\n\n";

$options = SerializerOptions::csvSemicolon();
$csv = $product->toCsv($options);

echo "European CSV (semicolon delimiter):\n";
echo $csv . "\n";

// Example 8: JSON with Pretty Print
echo "Example 8: JSON with Pretty Print\n";
echo "---------------------------------\n";
echo "💡 Use SerializerOptions::prettyJson() for formatted JSON\n\n";

$options = SerializerOptions::prettyJson();
$json = $product->serializeTo(SerializationFormat::Json, $options);

echo "Pretty-printed JSON:\n";
echo $json . "\n";

// Example 9: JSON without Pretty Print
echo "Example 9: JSON without Pretty Print\n";
echo "------------------------------------\n";

$options = SerializerOptions::json(prettyPrint: false);
$json = $product->serializeTo(SerializationFormat::Json, $options);

echo "Compact JSON:\n";
echo $json . "\n\n";

// Example 10: Custom CSV Configuration
echo "Example 10: Custom CSV Configuration\n";
echo "------------------------------------\n";
echo "💡 Customize all CSV options\n\n";

$options = SerializerOptions::csv(
    includeHeaders: true,
    delimiter: '|',
    enclosure: "'",
    escape: '\\'
);
$csv = $product->toCsv($options);

echo "Custom CSV (pipe delimiter, single quote enclosure):\n";
echo $csv . "\n";

// Example 11: Using serializeTo() with Options
echo "Example 11: Using serializeTo() with Options\n";
echo "--------------------------------------------\n";
echo "💡 Use serializeTo() with SerializationFormat enum and options\n\n";

// XML
$xmlOptions = SerializerOptions::xml(rootElement: 'item');
$xml = $product->serializeTo(SerializationFormat::Xml, $xmlOptions);
echo "XML via serializeTo():\n";
echo substr($xml, 0, 150) . "...\n\n";

// YAML
$yamlOptions = SerializerOptions::yaml(indent: 2);
$yaml = $product->serializeTo(SerializationFormat::Yaml, $yamlOptions);
echo "YAML via serializeTo():\n";
echo $yaml . "\n";

// CSV
$csvOptions = SerializerOptions::csv(delimiter: ';');
$csv = $product->serializeTo(SerializationFormat::Csv, $csvOptions);
echo "CSV via serializeTo():\n";
echo $csv . "\n";

// Example 12: Backward Compatibility
echo "Example 12: Backward Compatibility\n";
echo "----------------------------------\n";
echo "💡 Methods still work without options (use defaults)\n\n";

$xml = $product->toXml();
echo "XML with default options:\n";
echo substr($xml, 0, 150) . "...\n\n";

$yaml = $product->toYaml();
echo "YAML with default options:\n";
echo $yaml . "\n";

$csv = $product->toCsv();
echo "CSV with default options:\n";
echo $csv . "\n";

// Example 13: Type Safety Benefits
echo "Example 13: Type Safety Benefits\n";
echo "--------------------------------\n";
echo "💡 SerializerOptions provides compile-time type safety\n\n";

echo "✅ Benefits:\n";
echo "  1. IDE Autocomplete - See all available options while typing\n";
echo "  2. Type Safety - Invalid values are caught at compile time\n";
echo "  3. No Typos - Property names are validated by PHP\n";
echo "  4. Self-Documenting - Options are clearly defined in the class\n";
echo "  5. Refactoring Safe - Rename properties with confidence\n";
echo "  6. Factory Methods - Convenient shortcuts for common configurations\n\n";

echo "✅ Factory Methods Available:\n";
echo "  - SerializerOptions::default() - Default configuration\n";
echo "  - SerializerOptions::json(prettyPrint: bool) - JSON options\n";
echo "  - SerializerOptions::prettyJson() - Pretty-printed JSON\n";
echo "  - SerializerOptions::xml(rootElement, xmlVersion, encoding) - XML options\n";
echo "  - SerializerOptions::yaml(indent) - YAML options\n";
echo "  - SerializerOptions::csv(includeHeaders, delimiter, enclosure, escape) - CSV options\n";
echo "  - SerializerOptions::tsv(includeHeaders) - Tab-separated values\n";
echo "  - SerializerOptions::csvSemicolon(includeHeaders) - European CSV\n\n";

// Example 14: Real-World Usage
echo "Example 14: Real-World Usage\n";
echo "----------------------------\n";
echo "💡 Different formats for different use cases\n\n";

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $productName,
        public readonly float $productPrice,
        public readonly int $quantity,
        public readonly float $total,
    ) {
    }
}

$order = new OrderDTO(
    orderId: 12345,
    productName: 'Laptop',
    productPrice: 999.99,
    quantity: 2,
    total: 1999.98
);

// API Response (JSON)
echo "API Response (Pretty JSON):\n";
$apiOptions = SerializerOptions::prettyJson();
$json = $order->serializeTo(SerializationFormat::Json, $apiOptions);
echo $json . "\n";

// Configuration File (YAML)
echo "Configuration File (YAML with 2-space indent):\n";
$configOptions = SerializerOptions::yaml(indent: 2);
$yaml = $order->serializeTo(SerializationFormat::Yaml, $configOptions);
echo $yaml . "\n";

// Data Export (CSV)
echo "Data Export (CSV with headers):\n";
$exportOptions = SerializerOptions::csv(includeHeaders: true);
$csv = $order->serializeTo(SerializationFormat::Csv, $exportOptions);
echo $csv . "\n";

// SOAP/XML API (XML with custom root)
echo "SOAP/XML API (Custom root element):\n";
$soapOptions = SerializerOptions::xml(rootElement: 'Order', encoding: 'UTF-8');
$xml = $order->serializeTo(SerializationFormat::Xml, $soapOptions);
echo substr($xml, 0, 200) . "...\n\n";

echo "================================================================================\n";
echo "✅  All SerializerOptions examples completed!\n";
echo "================================================================================\n";
