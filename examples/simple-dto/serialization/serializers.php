<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Enums\SerializationFormat;
use event4u\DataHelpers\SimpleDto\Serializers\SerializerInterface;

echo "================================================================================\n";
echo "SimpleDto - Custom Serializers Examples\n";
echo "================================================================================\n\n";

// Example 1: XML Serialization
echo "Example 1: XML Serialization\n";
echo "----------------------------\n";

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

$user = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

$xml = $user->toXml();
echo "XML Output:\n";
echo $xml . "\n\n";

// Example 2: XML with Custom Root Element
echo "Example 2: XML with Custom Root Element\n";
echo "----------------------------------------\n";

use event4u\DataHelpers\SimpleDto\Config\SerializerOptions;

$options = SerializerOptions::xml(rootElement: 'user');
$xml = $user->toXml($options);
echo "XML with 'user' root:\n";
echo $xml . "\n\n";

// Example 3: YAML Serialization
echo "Example 3: YAML Serialization\n";
echo "------------------------------\n";

$yaml = $user->toYaml();
echo "YAML Output:\n";
echo $yaml . "\n";

// Example 4: YAML with Custom Indent
echo "Example 4: YAML with Custom Indent\n";
echo "-----------------------------------\n";

$options = SerializerOptions::yaml(indent: 4);
$yaml = $user->toYaml($options);
echo "YAML with 4-space indent:\n";
echo $yaml . "\n";

// Example 5: CSV Serialization
echo "Example 5: CSV Serialization\n";
echo "----------------------------\n";

$csv = $user->toCsv();
echo "CSV Output:\n";
echo $csv . "\n\n";

// Example 6: CSV without Headers
echo "Example 6: CSV without Headers\n";
echo "-------------------------------\n";

$options = SerializerOptions::csv(includeHeaders: false);
$csv = $user->toCsv($options);
echo "CSV without headers:\n";
echo $csv . "\n\n";

// Example 7: CSV with Custom Delimiter
echo "Example 7: CSV with Custom Delimiter\n";
echo "-------------------------------------\n";

$options = SerializerOptions::csv(delimiter: ';');
$csv = $user->toCsv($options);
echo "CSV with semicolon delimiter:\n";
echo $csv . "\n\n";

// Example 8: Serializing Collections
echo "Example 8: Serializing Collections\n";
echo "-----------------------------------\n";

use event4u\DataHelpers\SimpleDto\DataCollection;

/** @var DataCollection<SimpleDto> $users */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$users = DataCollection::forDto(UserDto::class, [
    ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30],
    ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'age' => 25],
    ['name' => 'Bob Johnson', 'email' => 'bob@example.com', 'age' => 35],
]);

// Convert collection to array and serialize
/** @phpstan-ignore-next-line unknown */
$usersArray = $users->toArray();

use event4u\DataHelpers\SimpleDto\Serializers\CsvSerializer;

$csvSerializer = new CsvSerializer();
$csv = $csvSerializer->serialize($usersArray);

echo "CSV of multiple users:\n";
echo $csv . "\n\n";

// Example 9: Custom Serializer
echo "Example 9: Custom Serializer\n";
echo "----------------------------\n";

class JsonPrettySerializer implements SerializerInterface
{
    /** @param array<mixed> $data */
    public function serialize(array $data): string
    {
        /** @phpstan-ignore-next-line unknown */
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    public function getContentType(): string
    {
        return 'application/json';
    }
}

$custom = $user->serializeWith(new JsonPrettySerializer());
echo "Custom JSON Pretty Serializer:\n";
echo $custom . "\n\n";

// Example 10: Markdown Table Serializer
echo "Example 10: Markdown Table Serializer\n";
echo "--------------------------------------\n";

class MarkdownTableSerializer implements SerializerInterface
{
    /** @param array<mixed> $data */
    public function serialize(array $data): string
    {
        if ([] === $data) {
            return '';
        }

        // Check if it's a collection
        $isCollection = is_array(reset($data));

        if (!$isCollection) {
            $data = [$data];
        }

        $headers = array_keys($data[0]);
        $output = '| ' . implode(' | ', $headers) . " |\n";
        $output .= '| ' . implode(' | ', array_fill(0, count($headers), '---')) . " |\n";

        foreach ($data as $row) {
            $output .= '| ' . implode(' | ', array_values($row)) . " |\n";
        }

        return $output;
    }

    public function getContentType(): string
    {
        return 'text/markdown';
    }
}

$markdown = $user->serializeWith(new MarkdownTableSerializer());
echo "Markdown Table:\n";
echo $markdown . "\n";

// Example 11: Serializing with Wrapping
echo "Example 11: Serializing with Wrapping\n";
echo "--------------------------------------\n";

$wrapped = $user->wrap('user');

$xml = $wrapped->toXml();
echo "Wrapped XML:\n";
echo $xml . "\n";

$yaml = $wrapped->toYaml();
echo "Wrapped YAML:\n";
echo $yaml . "\n";

// Example 12: Nested Data Serialization
echo "Example 12: Nested Data Serialization\n";
echo "--------------------------------------\n";

use event4u\DataHelpers\SimpleDto\Serializers\XmlSerializer;
use event4u\DataHelpers\SimpleDto\Serializers\YamlSerializer;

$nestedData = [
    'user' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'address' => [
            'street' => '123 Main St',
            'city' => 'New York',
            'zip' => '10001',
        ],
    ],
];

$xmlSerializer = new XmlSerializer('data');
$xml = $xmlSerializer->serialize($nestedData);
echo "Nested XML:\n";
echo $xml . "\n";

$yamlSerializer = new YamlSerializer();
$yaml = $yamlSerializer->serialize($nestedData);
echo "Nested YAML:\n";
echo $yaml . "\n";

// Example 11: Using SerializationFormat Enum
echo "Example 11: Using SerializationFormat Enum\n";
echo "-------------------------------------------\n";
echo "💡 Tip: Use SerializationFormat enum for type-safe serialization!\n";
echo "    Available: Json, Xml, Yaml, Csv\n\n";

$user = UserDto::fromArray([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
    'age' => 25,
]);

// Serialize to different formats using enum ✨
echo "XML (using enum):\n";
echo $user->serializeTo(SerializationFormat::Xml) . "\n\n";

echo "YAML (using enum):\n";
echo $user->serializeTo(SerializationFormat::Yaml) . "\n\n";

echo "CSV (using enum):\n";
echo $user->serializeTo(SerializationFormat::Csv) . "\n\n";

echo "JSON (using enum):\n";
echo $user->serializeTo(SerializationFormat::Json) . "\n\n";

echo "✅  Enum provides IDE autocomplete and type safety!\n";
echo "✅  Backward compatibility: toXml(), toYaml(), toCsv() still work!\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";
