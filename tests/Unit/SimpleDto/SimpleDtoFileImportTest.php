<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;

// Test DTO class
class TestUserDto extends SimpleDto
{
    public function __construct(
        public string $name,
        public string $email,
        public int $age,
    ) {
    }
}

describe('SimpleDto File Import', function(): void {
    describe('fromJson()', function(): void {
        it('creates DTO from JSON file', function(): void {
            $filePath = __DIR__ . '/../../Fixtures/test-user.json';
            $dto = TestUserDto::fromJson($filePath);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('creates DTO from JSON string', function(): void {
            $json = '{"name":"Jane Doe","email":"jane@example.com","age":25}';
            $dto = TestUserDto::fromJson($json);

            expect($dto->name)->toBe('Jane Doe');
            expect($dto->email)->toBe('jane@example.com');
            expect($dto->age)->toBe(25);
        });
    });

    describe('fromXml()', function(): void {
        it('creates DTO from XML file', function(): void {
            $filePath = __DIR__ . '/../../Fixtures/test-user.xml';
            // XML file has <user> root element, so we need to map from user.*
            $template = [
                'name' => '{{ user.name }}',
                'email' => '{{ user.email }}',
                'age' => '{{ user.age }}',
            ];
            $dto = TestUserDto::fromXml($filePath, $template);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('creates DTO from XML string', function(): void {
            $xml = '<root><name>Jane Doe</name><email>jane@example.com</email><age>25</age></root>';
            $dto = TestUserDto::fromXml($xml);

            expect($dto->name)->toBe('Jane Doe');
            expect($dto->email)->toBe('jane@example.com');
            expect($dto->age)->toBe(25);
        });
    });

    describe('fromYaml()', function(): void {
        it('creates DTO from YAML file', function(): void {
            $filePath = __DIR__ . '/../../Fixtures/test-user.yaml';
            $dto = TestUserDto::fromYaml($filePath);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('creates DTO from YAML string', function(): void {
            $yaml = "name: Jane Doe\nemail: jane@example.com\nage: 25";
            $dto = TestUserDto::fromYaml($yaml);

            expect($dto->name)->toBe('Jane Doe');
            expect($dto->email)->toBe('jane@example.com');
            expect($dto->age)->toBe(25);
        });
    });

    describe('fromCsv()', function(): void {
        it('creates DTO from CSV file', function(): void {
            $filePath = __DIR__ . '/../../Fixtures/test-user.csv';
            $dto = TestUserDto::fromCsv($filePath);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('creates DTO from CSV string', function(): void {
            $csv = "name,email,age\nJane Doe,jane@example.com,25";
            $dto = TestUserDto::fromCsv($csv);

            expect($dto->name)->toBe('Jane Doe');
            expect($dto->email)->toBe('jane@example.com');
            expect($dto->age)->toBe(25);
        });
    });
});
