<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Config\SerializerOptions;
use Symfony\Component\Yaml\Yaml;

class TestSerializationDTO extends SimpleDTO
{
    public function __construct(
        public string $name = '',
        public string $email = '',
        public int $age = 0,
    ) {
    }
}

class TestNestedSerializationDTO extends SimpleDTO
{
    /** @param array<string, mixed> $address */
    public function __construct(
        public string $name = '',
        public array $address = [],
    ) {
    }
}

describe('SimpleDTOSerializationOutputTest', function(): void {
    beforeEach(function(): void {
        $this->dtoClass = TestSerializationDTO::class;
        $this->nestedDtoClass = TestNestedSerializationDTO::class;
    });

    describe('toJson()', function(): void {
        it('converts DTO to JSON string', function(): void {
            $dto = $this->dtoClass::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            $json = $dto->toJson();

            expect($json)->toBeString();
            expect($json)->toContain('"name":"John Doe"');
            expect($json)->toContain('"email":"john@example.com"');
            expect($json)->toContain('"age":30');
        });

        it('handles empty DTO', function(): void {
            $dto = $this->dtoClass::fromArray([]);

            $json = $dto->toJson();

            expect($json)->toBeString();
            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
        });

        it('supports JSON_PRETTY_PRINT flag', function(): void {
            $dto = $this->dtoClass::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            $json = $dto->toJson(JSON_PRETTY_PRINT);

            expect($json)->toContain("\n");
            expect($json)->toContain('    ');
        });

        it('handles nested arrays', function(): void {
            $dto = $this->nestedDtoClass::fromArray([
                'name' => 'John Doe',
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'New York',
                ],
            ]);

            $json = $dto->toJson();

            expect($json)->toContain('"address"');
            expect($json)->toContain('"street":"123 Main St"');
            expect($json)->toContain('"city":"New York"');
        });

        it('handles special characters', function(): void {
            $dto = $this->dtoClass::fromArray([
                'name' => 'John "The Boss" Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            $json = $dto->toJson();

            expect($json)->toContain('John \\"The Boss\\" Doe');
        });

        it('supports JSON_UNESCAPED_UNICODE flag', function(): void {
            $dto = $this->dtoClass::fromArray([
                'name' => 'Müller',
                'email' => 'mueller@example.com',
                'age' => 30,
            ]);

            $json = $dto->toJson(JSON_UNESCAPED_UNICODE);

            expect($json)->toContain('Müller');
        });
    });

    describe('toXml()', function(): void {
        it('converts DTO to XML string', function(): void {
            $dto = $this->dtoClass::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            $xml = $dto->toXml();

            expect($xml)->toBeString();
            expect($xml)->toContain('<?xml version="1.0"');
            expect($xml)->toContain('<name>John Doe</name>');
            expect($xml)->toContain('<email>john@example.com</email>');
            expect($xml)->toContain('<age>30</age>');
        });

        it('uses class name as default root element', function(): void {
            $dto = $this->dtoClass::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            $xml = $dto->toXml();

            expect($xml)->toContain('<root>');
            expect($xml)->toContain('</root>');
        });

        it('supports custom root element', function(): void {
            $dto = $this->dtoClass::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            $options = SerializerOptions::xml('customer');
            $xml = $dto->toXml($options);

            expect($xml)->toContain('<customer>');
            expect($xml)->toContain('</customer>');
        });

        it('handles nested arrays', function(): void {
            $dto = $this->nestedDtoClass::fromArray([
                'name' => 'John Doe',
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'New York',
                ],
            ]);

            $xml = $dto->toXml();

            expect($xml)->toContain('<address>');
            expect($xml)->toContain('<street>123 Main St</street>');
            expect($xml)->toContain('<city>New York</city>');
            expect($xml)->toContain('</address>');
        });

        it('escapes special XML characters', function(): void {
            $dto = $this->dtoClass::fromArray([
                'name' => 'John & Jane <Doe>',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            $xml = $dto->toXml();

            expect($xml)->toContain('&amp;');
            expect($xml)->toContain('&lt;');
            expect($xml)->toContain('&gt;');
        });

        it('handles empty values', function(): void {
            $dto = $this->dtoClass::fromArray([
                'name' => '',
                'email' => '',
                'age' => 0,
            ]);

            $xml = $dto->toXml();

            // Empty values are rendered as self-closing tags by SimpleXMLElement
            expect($xml)->toMatch('/<name(\/>|><\/name>)/');
            expect($xml)->toMatch('/<email(\/>|><\/email>)/');
            expect($xml)->toContain('<age>0</age>');
        });
    });

    describe('toYaml()', function(): void {
        it('converts DTO to YAML string', function(): void {
            if (!function_exists('yaml_emit') && !class_exists(Yaml::class)) {
                $this->markTestSkipped('YAML support not available (neither ext-yaml nor symfony/yaml)');
            }

            $dto = $this->dtoClass::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            $yaml = $dto->toYaml();

            expect($yaml)->toBeString();
            expect($yaml)->toContain('name');
            expect($yaml)->toContain('John Doe');
            expect($yaml)->toContain('email');
            expect($yaml)->toContain('john@example.com');
            expect($yaml)->toContain('age');
        });

        it('throws exception when YAML support not available', function(): void {
            if (function_exists('yaml_emit') || class_exists(Yaml::class)) {
                $this->markTestSkipped('YAML support is available');
            }

            $dto = $this->dtoClass::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            expect($dto->toYaml(...))
                ->toThrow(RuntimeException::class, 'YAML support is not available');
        });

        it('handles nested arrays', function(): void {
            if (!function_exists('yaml_emit') && !class_exists(Yaml::class)) {
                $this->markTestSkipped('YAML support not available');
            }

            $dto = $this->nestedDtoClass::fromArray([
                'name' => 'John Doe',
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'New York',
                ],
            ]);

            $yaml = $dto->toYaml();

            expect($yaml)->toContain('address');
            expect($yaml)->toContain('street');
            expect($yaml)->toContain('city');
        });
    });

    describe('toCsv()', function(): void {
        it('converts DTO to CSV string with headers', function(): void {
            $dto = $this->dtoClass::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            $csv = $dto->toCsv();

            expect($csv)->toBeString();
            $lines = explode("\n", $csv);
            expect($lines)->toHaveCount(2);
            expect($lines[0])->toContain('name');
            expect($lines[0])->toContain('email');
            expect($lines[0])->toContain('age');
            expect($lines[1])->toContain('John Doe');
            expect($lines[1])->toContain('john@example.com');
            expect($lines[1])->toContain('30');
        });

        it('handles empty values', function(): void {
            $dto = $this->dtoClass::fromArray([
                'name' => '',
                'email' => '',
                'age' => 0,
            ]);

            $csv = $dto->toCsv();

            $lines = explode("\n", $csv);
            expect($lines)->toHaveCount(2);
        });

        it('quotes values with commas', function(): void {
            $dto = $this->dtoClass::fromArray([
                'name' => 'Doe, John',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            $csv = $dto->toCsv();

            expect($csv)->toContain('"Doe, John"');
        });

        it('quotes values with quotes', function(): void {
            $dto = $this->dtoClass::fromArray([
                'name' => 'John "The Boss" Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            $csv = $dto->toCsv();

            expect($csv)->toContain('"John \"The Boss\" Doe"');
        });
    });
});
