<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\ConverterMode;
use event4u\DataHelpers\Support\FileLoader;

// Test DTO class
#[ConverterMode]
class TestLiteUserDto extends LiteDto
{
    public function __construct(
        public string $name,
        public string $email,
        public int $age,
    ) {
    }
}

describe('LiteDto File Import', function(): void {
    describe('from() with JSON file', function(): void {
        it('creates DTO from JSON file', function(): void {
            $filePath = __DIR__ . '/../../Fixtures/test-user.json';
            $dto = TestLiteUserDto::from($filePath);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('creates DTO from JSON string', function(): void {
            $json = '{"name":"Jane Doe","email":"jane@example.com","age":25}';
            $dto = TestLiteUserDto::from($json);

            expect($dto->name)->toBe('Jane Doe');
            expect($dto->email)->toBe('jane@example.com');
            expect($dto->age)->toBe(25);
        });
    });

    describe('from() with XML file', function(): void {
        it('creates DTO from XML file', function(): void {
            $filePath = __DIR__ . '/../../Fixtures/test-user.xml';
            // XML file has <user> root element, need to extract it first
            $data = FileLoader::loadAsArray($filePath);
            $dto = TestLiteUserDto::from($data['user']);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('creates DTO from XML string', function(): void {
            $xml = '<root><name>Jane Doe</name><email>jane@example.com</email><age>25</age></root>';
            $dto = TestLiteUserDto::from($xml);

            expect($dto->name)->toBe('Jane Doe');
            expect($dto->email)->toBe('jane@example.com');
            expect($dto->age)->toBe(25);
        });
    });

    describe('from() with YAML file', function(): void {
        it('creates DTO from YAML file', function(): void {
            $filePath = __DIR__ . '/../../Fixtures/test-user.yaml';
            $dto = TestLiteUserDto::from($filePath);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('creates DTO from YAML string', function(): void {
            $yaml = "name: Jane Doe\nemail: jane@example.com\nage: 25";
            $dto = TestLiteUserDto::from($yaml);

            expect($dto->name)->toBe('Jane Doe');
            expect($dto->email)->toBe('jane@example.com');
            expect($dto->age)->toBe(25);
        });
    });
});
