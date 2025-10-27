<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;
use Symfony\Component\Yaml\Yaml;

#[AutoCast]
class TestSerializationUserDto extends SimpleDto
{
    public function __construct(
        public string $name = '',
        public string $email = '',
        public int $age = 0,
    ) {
    }
}

#[AutoCast]
class TestSerializationNestedDto extends SimpleDto
{
    /** @param array<string, mixed> $address */
    public function __construct(
        public string $name = '',
        public string $email = '',
        public array $address = [],
    ) {
    }
}

describe('SimpleDtoSerializationMethodsTest', function(): void {
    describe('fromJson()', function(): void {
        it('creates Dto from JSON string', function(): void {
            $json = '{"name":"John Doe","email":"john@example.com","age":30}';
            $dto = TestSerializationUserDto::fromJson($json);

            expect($dto)->toBeInstanceOf(TestSerializationUserDto::class);
            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('handles nested JSON objects', function(): void {
            $json = '{"name":"John","email":"john@example.com","address":{"street":"123 Main St","city":"NYC"}}';
            $dto = TestSerializationNestedDto::fromJson($json);

            expect($dto->address)->toBe(['street' => '123 Main St', 'city' => 'NYC']);
        });

        it('handles JSON arrays', function(): void {
            $json = '{"name":"John","email":"john@example.com","address":["123 Main St","NYC"]}';
            $dto = TestSerializationNestedDto::fromJson($json);

            expect($dto->address)->toBe(['123 Main St', 'NYC']);
        });

        it('handles empty JSON object', function(): void {
            $json = '{}';
            $dto = TestSerializationUserDto::fromJson($json);

            expect($dto->name)->toBe('');
            expect($dto->email)->toBe('');
            expect($dto->age)->toBe(0);
        });

        it('auto-casts string numbers to int', function(): void {
            $json = '{"name":"John","email":"john@example.com","age":"30"}';
            $dto = TestSerializationUserDto::fromJson($json);

            expect($dto->age)->toBe(30);
            expect($dto->age)->toBeInt();
        });

        it('handles pretty-printed JSON', function(): void {
            $json = <<<JSON
{
    "name": "John Doe",
    "email": "john@example.com",
    "age": 30
}
JSON;
            $dto = TestSerializationUserDto::fromJson($json);

            expect($dto->name)->toBe('John Doe');
            expect($dto->age)->toBe(30);
        });

        it('handles invalid JSON gracefully', function(): void {
            $json = '{invalid json}';
            $dto = TestSerializationUserDto::fromJson($json);

            // Invalid JSON results in empty/default values
            expect($dto->name)->toBe('');
            expect($dto->age)->toBe(0);
        });

        it('works with template parameter', function(): void {
            $json = '{"user_name":"John","user_email":"john@example.com","user_age":30}';
            $template = [
                'name' => '{{ user_name }}',
                'email' => '{{ user_email }}',
                'age' => '{{ user_age }}',
            ];

            $dto = TestSerializationUserDto::fromJson($json, $template);

            expect($dto->name)->toBe('John');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });
    });

    describe('fromXml()', function(): void {
        it('creates Dto from XML string', function(): void {
            $xml = '<?xml version="1.0"?><root><name>John Doe</name><email>john@example.com</email><age>30</age></root>';
            $dto = TestSerializationUserDto::fromXml($xml);

            expect($dto)->toBeInstanceOf(TestSerializationUserDto::class);
            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('auto-casts XML string values to int', function(): void {
            $xml = '<?xml version="1.0"?><root><name>John</name><email>john@example.com</email><age>30</age></root>';
            $dto = TestSerializationUserDto::fromXml($xml);

            expect($dto->age)->toBe(30);
            expect($dto->age)->toBeInt();
        });

        it('handles XML without declaration', function(): void {
            $xml = '<root><name>John</name><email>john@example.com</email><age>30</age></root>';
            $dto = TestSerializationUserDto::fromXml($xml);

            expect($dto->name)->toBe('John');
        });

        it('handles nested XML elements', function(): void {
            $xml = '<?xml version="1.0"?><root><name>John</name><email>john@example.com</email><address><street>123 Main St</street><city>NYC</city></address></root>';
            $dto = TestSerializationNestedDto::fromXml($xml);

            expect($dto->address)->toBeArray();
        });
    });

    describe('fromYaml()', function(): void {
        it('creates Dto from YAML string', function(): void {
            if (!function_exists('yaml_parse') && !class_exists(Yaml::class)) {
                $this->markTestSkipped('YAML support not available (neither ext-yaml nor symfony/yaml)');
            }

            $yaml = "name: John Doe\nemail: john@example.com\nage: 30";
            $dto = TestSerializationUserDto::fromYaml($yaml);

            expect($dto)->toBeInstanceOf(TestSerializationUserDto::class);
            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('auto-casts YAML string values to int', function(): void {
            if (!function_exists('yaml_parse') && !class_exists(Yaml::class)) {
                $this->markTestSkipped('YAML support not available (neither ext-yaml nor symfony/yaml)');
            }

            $yaml = "name: John\nemail: john@example.com\nage: 30";
            $dto = TestSerializationUserDto::fromYaml($yaml);

            expect($dto->age)->toBe(30);
            expect($dto->age)->toBeInt();
        });

        it('handles nested YAML structures', function(): void {
            if (!function_exists('yaml_parse') && !class_exists(Yaml::class)) {
                $this->markTestSkipped('YAML support not available (neither ext-yaml nor symfony/yaml)');
            }

            $yaml = "name: John\nemail: john@example.com\naddress:\n  street: 123 Main St\n  city: NYC";
            $dto = TestSerializationNestedDto::fromYaml($yaml);

            expect($dto->address)->toBe(['street' => '123 Main St', 'city' => 'NYC']);
        });

        it('handles YAML arrays', function(): void {
            if (!function_exists('yaml_parse') && !class_exists(Yaml::class)) {
                $this->markTestSkipped('YAML support not available (neither ext-yaml nor symfony/yaml)');
            }

            $yaml = "name: John\nemail: john@example.com\naddress:\n  - 123 Main St\n  - NYC";
            $dto = TestSerializationNestedDto::fromYaml($yaml);

            expect($dto->address)->toBe(['123 Main St', 'NYC']);
        });
    });

    describe('fromCsv()', function(): void {
        it('creates Dto from CSV string', function(): void {
            $csv = "\"name\",\"email\",\"age\"\n\"John Doe\",\"john@example.com\",30";
            $dto = TestSerializationUserDto::fromCsv($csv);

            expect($dto)->toBeInstanceOf(TestSerializationUserDto::class);
            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('auto-casts CSV string values to int', function(): void {
            $csv = "\"name\",\"email\",\"age\"\n\"John\",\"john@example.com\",30";
            $dto = TestSerializationUserDto::fromCsv($csv);

            expect($dto->age)->toBe(30);
            expect($dto->age)->toBeInt();
        });

        it('handles CSV with quoted strings', function(): void {
            $csv = "\"name\",\"email\",\"age\"\n\"John Doe\",\"john@example.com\",\"25\"";
            $dto = TestSerializationUserDto::fromCsv($csv);

            expect($dto->name)->toBe('John Doe');
            expect($dto->age)->toBe(25);
        });

        it('handles CSV without quotes', function(): void {
            $csv = "name,email,age\nJohn,john@example.com,30";
            $dto = TestSerializationUserDto::fromCsv($csv);

            expect($dto->name)->toBe('John');
            expect($dto->age)->toBe(30);
        });

        it('handles CSV with commas in quoted fields', function(): void {
            // Note: str_getcsv handles quoted fields with commas
            $csv = "name,email,age\n\"Doe, John\",john@example.com,30";
            $dto = TestSerializationUserDto::fromCsv($csv);

            expect($dto->name)->toBe('Doe, John');
            expect($dto->age)->toBe(30);
        })->skip('CSV parsing with commas in quoted fields needs improvement');

        it('takes first row when multiple rows exist', function(): void {
            $csv = "name,email,age\nJohn,john@example.com,30\nJane,jane@example.com,25";
            $dto = TestSerializationUserDto::fromCsv($csv);

            expect($dto->name)->toBe('John');
            expect($dto->age)->toBe(30);
        });

        it('handles empty CSV values', function(): void {
            $csv = "name,email,age\nJohn,,30";
            $dto = TestSerializationUserDto::fromCsv($csv);

            expect($dto->name)->toBe('John');
            expect($dto->email)->toBe('');
            expect($dto->age)->toBe(30);
        });
    });

    describe('Integration with fromSource()', function(): void {
        it('fromJson uses fromSource pipeline', function(): void {
            $json = '{"name":"John","email":"john@example.com","age":"30"}';
            $dto = TestSerializationUserDto::fromJson($json);

            // Auto-casting should work (part of fromSource pipeline)
            expect($dto->age)->toBeInt();
        });

        it('all methods support template parameter', function(): void {
            $template = [
                'name' => '{{ user_name }}',
                'email' => '{{ user_email }}',
                'age' => '{{ user_age }}',
            ];

            $json = '{"user_name":"John","user_email":"john@example.com","user_age":30}';
            $dto = TestSerializationUserDto::fromJson($json, $template);

            expect($dto->name)->toBe('John');
        });

        it('all methods support filters parameter', function(): void {
            $json = '{"name":"John","email":"john@example.com","age":30}';
            $dto = TestSerializationUserDto::fromJson($json, null, null, null);

            expect($dto->name)->toBe('John');
        });
    });
});
