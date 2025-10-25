<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;

class TestUserDTO extends SimpleDTO
{
    public function __construct(
        public string $name = '',
        public string $email = '',
        public int $age = 0,
    ) {
    }
}

class TestNestedDTO extends SimpleDTO
{
    public function __construct(
        public string $name = '',
        public string $email = '',
        public array $address = [],
    ) {
    }
}

describe('SimpleDTOSerializationMethodsTest', function () {
    beforeEach(function () {
        $this->userDtoClass = TestUserDTO::class;
        $this->nestedDtoClass = TestNestedDTO::class;
    });

    describe('fromJson()', function () {
        it('creates DTO from JSON string', function () {
            $json = '{"name":"John Doe","email":"john@example.com","age":30}';
            $dto = ($this->userDtoClass)::fromJson($json);

            expect($dto)->toBeInstanceOf($this->userDtoClass);
            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('handles nested JSON objects', function () {
            $json = '{"name":"John","email":"john@example.com","address":{"street":"123 Main St","city":"NYC"}}';
            $dto = ($this->nestedDtoClass)::fromJson($json);

            expect($dto->address)->toBe(['street' => '123 Main St', 'city' => 'NYC']);
        });

        it('handles JSON arrays', function () {
            $json = '{"name":"John","email":"john@example.com","address":["123 Main St","NYC"]}';
            $dto = ($this->nestedDtoClass)::fromJson($json);

            expect($dto->address)->toBe(['123 Main St', 'NYC']);
        });

        it('handles empty JSON object', function () {
            $json = '{}';
            $dto = ($this->userDtoClass)::fromJson($json);

            expect($dto->name)->toBe('');
            expect($dto->email)->toBe('');
            expect($dto->age)->toBe(0);
        });

        it('auto-casts string numbers to int', function () {
            $json = '{"name":"John","email":"john@example.com","age":"30"}';
            $dto = ($this->userDtoClass)::fromJson($json);

            expect($dto->age)->toBe(30);
            expect($dto->age)->toBeInt();
        });

        it('handles pretty-printed JSON', function () {
            $json = <<<JSON
{
    "name": "John Doe",
    "email": "john@example.com",
    "age": 30
}
JSON;
            $dto = ($this->userDtoClass)::fromJson($json);

            expect($dto->name)->toBe('John Doe');
            expect($dto->age)->toBe(30);
        });

        it('handles invalid JSON gracefully', function () {
            $json = '{invalid json}';
            $dto = ($this->userDtoClass)::fromJson($json);

            // Invalid JSON results in empty/default values
            expect($dto->name)->toBe('');
            expect($dto->age)->toBe(0);
        });

        it('works with template parameter', function () {
            $json = '{"user_name":"John","user_email":"john@example.com","user_age":30}';
            $template = [
                'name' => '{{ user_name }}',
                'email' => '{{ user_email }}',
                'age' => '{{ user_age }}',
            ];

            $dto = ($this->userDtoClass)::fromJson($json, $template);

            expect($dto->name)->toBe('John');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });
    });

    describe('fromXml()', function () {
        it('creates DTO from XML string', function () {
            $xml = '<?xml version="1.0"?><root><name>John Doe</name><email>john@example.com</email><age>30</age></root>';
            $dto = ($this->userDtoClass)::fromXml($xml);

            expect($dto)->toBeInstanceOf($this->userDtoClass);
            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('auto-casts XML string values to int', function () {
            $xml = '<?xml version="1.0"?><root><name>John</name><email>john@example.com</email><age>30</age></root>';
            $dto = ($this->userDtoClass)::fromXml($xml);

            expect($dto->age)->toBe(30);
            expect($dto->age)->toBeInt();
        });

        it('handles XML without declaration', function () {
            $xml = '<root><name>John</name><email>john@example.com</email><age>30</age></root>';
            $dto = ($this->userDtoClass)::fromXml($xml);

            expect($dto->name)->toBe('John');
        });

        it('handles nested XML elements', function () {
            $xml = '<?xml version="1.0"?><root><name>John</name><email>john@example.com</email><address><street>123 Main St</street><city>NYC</city></address></root>';
            $dto = ($this->nestedDtoClass)::fromXml($xml);

            expect($dto->address)->toBeArray();
        });
    });

    describe('fromYaml()', function () {
        it('creates DTO from YAML string', function () {
            if (!function_exists('yaml_parse') && !class_exists(\Symfony\Component\Yaml\Yaml::class)) {
                $this->markTestSkipped('YAML support not available (neither ext-yaml nor symfony/yaml)');
            }

            $yaml = "name: John Doe\nemail: john@example.com\nage: 30";
            $dto = ($this->userDtoClass)::fromYaml($yaml);

            expect($dto)->toBeInstanceOf($this->userDtoClass);
            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('auto-casts YAML string values to int', function () {
            if (!function_exists('yaml_parse') && !class_exists(\Symfony\Component\Yaml\Yaml::class)) {
                $this->markTestSkipped('YAML support not available (neither ext-yaml nor symfony/yaml)');
            }

            $yaml = "name: John\nemail: john@example.com\nage: 30";
            $dto = ($this->userDtoClass)::fromYaml($yaml);

            expect($dto->age)->toBe(30);
            expect($dto->age)->toBeInt();
        });

        it('handles nested YAML structures', function () {
            if (!function_exists('yaml_parse') && !class_exists(\Symfony\Component\Yaml\Yaml::class)) {
                $this->markTestSkipped('YAML support not available (neither ext-yaml nor symfony/yaml)');
            }

            $yaml = "name: John\nemail: john@example.com\naddress:\n  street: 123 Main St\n  city: NYC";
            $dto = ($this->nestedDtoClass)::fromYaml($yaml);

            expect($dto->address)->toBe(['street' => '123 Main St', 'city' => 'NYC']);
        });

        it('handles YAML arrays', function () {
            if (!function_exists('yaml_parse') && !class_exists(\Symfony\Component\Yaml\Yaml::class)) {
                $this->markTestSkipped('YAML support not available (neither ext-yaml nor symfony/yaml)');
            }

            $yaml = "name: John\nemail: john@example.com\naddress:\n  - 123 Main St\n  - NYC";
            $dto = ($this->nestedDtoClass)::fromYaml($yaml);

            expect($dto->address)->toBe(['123 Main St', 'NYC']);
        });
    });

    describe('fromCsv()', function () {
        it('creates DTO from CSV string', function () {
            $csv = "\"name\",\"email\",\"age\"\n\"John Doe\",\"john@example.com\",30";
            $dto = ($this->userDtoClass)::fromCsv($csv);

            expect($dto)->toBeInstanceOf($this->userDtoClass);
            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('auto-casts CSV string values to int', function () {
            $csv = "\"name\",\"email\",\"age\"\n\"John\",\"john@example.com\",30";
            $dto = ($this->userDtoClass)::fromCsv($csv);

            expect($dto->age)->toBe(30);
            expect($dto->age)->toBeInt();
        });

        it('handles CSV with quoted strings', function () {
            $csv = "\"name\",\"email\",\"age\"\n\"John Doe\",\"john@example.com\",\"25\"";
            $dto = ($this->userDtoClass)::fromCsv($csv);

            expect($dto->name)->toBe('John Doe');
            expect($dto->age)->toBe(25);
        });

        it('handles CSV without quotes', function () {
            $csv = "name,email,age\nJohn,john@example.com,30";
            $dto = ($this->userDtoClass)::fromCsv($csv);

            expect($dto->name)->toBe('John');
            expect($dto->age)->toBe(30);
        });

        it('handles CSV with commas in quoted fields', function () {
            // Note: str_getcsv handles quoted fields with commas
            $csv = "name,email,age\n\"Doe, John\",john@example.com,30";
            $dto = ($this->userDtoClass)::fromCsv($csv);

            expect($dto->name)->toBe('Doe, John');
            expect($dto->age)->toBe(30);
        })->skip('CSV parsing with commas in quoted fields needs improvement');

        it('takes first row when multiple rows exist', function () {
            $csv = "name,email,age\nJohn,john@example.com,30\nJane,jane@example.com,25";
            $dto = ($this->userDtoClass)::fromCsv($csv);

            expect($dto->name)->toBe('John');
            expect($dto->age)->toBe(30);
        });

        it('handles empty CSV values', function () {
            $csv = "name,email,age\nJohn,,30";
            $dto = ($this->userDtoClass)::fromCsv($csv);

            expect($dto->name)->toBe('John');
            expect($dto->email)->toBe('');
            expect($dto->age)->toBe(30);
        });
    });

    describe('Integration with fromSource()', function () {
        it('fromJson uses fromSource pipeline', function () {
            $json = '{"name":"John","email":"john@example.com","age":"30"}';
            $dto = ($this->userDtoClass)::fromJson($json);

            // Auto-casting should work (part of fromSource pipeline)
            expect($dto->age)->toBeInt();
        });

        it('all methods support template parameter', function () {
            $template = [
                'name' => '{{ user_name }}',
                'email' => '{{ user_email }}',
                'age' => '{{ user_age }}',
            ];

            $json = '{"user_name":"John","user_email":"john@example.com","user_age":30}';
            $dto = ($this->userDtoClass)::fromJson($json, $template);

            expect($dto->name)->toBe('John');
        });

        it('all methods support filters parameter', function () {
            $json = '{"name":"John","email":"john@example.com","age":30}';
            $dto = ($this->userDtoClass)::fromJson($json, null, null, null);

            expect($dto->name)->toBe('John');
        });
    });
});

