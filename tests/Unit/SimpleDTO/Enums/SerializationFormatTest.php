<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Config\SerializerOptions;
use event4u\DataHelpers\SimpleDTO\Enums\SerializationFormat;
use event4u\DataHelpers\SimpleDTO\Serializers\CsvSerializer;
use event4u\DataHelpers\SimpleDTO\Serializers\XmlSerializer;
use event4u\DataHelpers\SimpleDTO\Serializers\YamlSerializer;

describe('SerializationFormat Enum', function(): void {
    describe('Enum Values', function(): void {
        it('has correct string values', function(): void {
            expect(SerializationFormat::Json->value)->toBe('json')
                ->and(SerializationFormat::Xml->value)->toBe('xml')
                ->and(SerializationFormat::Yaml->value)->toBe('yaml')
                ->and(SerializationFormat::Csv->value)->toBe('csv');
        });

        it('provides all values', function(): void {
            $values = SerializationFormat::values();

            expect($values)->toBeArray()
                ->and($values)->toHaveCount(4)
                ->and($values)->toContain('json')
                ->and($values)->toContain('xml')
                ->and($values)->toContain('yaml')
                ->and($values)->toContain('csv');
        });
    });

    describe('fromString Method', function(): void {
        it('parses valid format strings', function(): void {
            expect(SerializationFormat::fromString('json'))->toBe(SerializationFormat::Json)
                ->and(SerializationFormat::fromString('xml'))->toBe(SerializationFormat::Xml)
                ->and(SerializationFormat::fromString('yaml'))->toBe(SerializationFormat::Yaml)
                ->and(SerializationFormat::fromString('csv'))->toBe(SerializationFormat::Csv);
        });

        it('returns null for invalid format strings', function(): void {
            expect(SerializationFormat::fromString('invalid'))->toBeNull()
                ->and(SerializationFormat::fromString('pdf'))->toBeNull()
                ->and(SerializationFormat::fromString(''))->toBeNull()
                ->and(SerializationFormat::fromString('txt'))->toBeNull();
        });

        it('is case-insensitive', function(): void {
            expect(SerializationFormat::fromString('JSON'))->toBe(SerializationFormat::Json)
                ->and(SerializationFormat::fromString('XML'))->toBe(SerializationFormat::Xml)
                ->and(SerializationFormat::fromString('YAML'))->toBe(SerializationFormat::Yaml)
                ->and(SerializationFormat::fromString('CSV'))->toBe(SerializationFormat::Csv)
                ->and(SerializationFormat::fromString('Json'))->toBe(SerializationFormat::Json);
        });
    });

    describe('fromExtension Method', function(): void {
        it('parses valid file extensions', function(): void {
            expect(SerializationFormat::fromExtension('json'))->toBe(SerializationFormat::Json)
                ->and(SerializationFormat::fromExtension('xml'))->toBe(SerializationFormat::Xml)
                ->and(SerializationFormat::fromExtension('yaml'))->toBe(SerializationFormat::Yaml)
                ->and(SerializationFormat::fromExtension('yml'))->toBe(SerializationFormat::Yaml)
                ->and(SerializationFormat::fromExtension('csv'))->toBe(SerializationFormat::Csv);
        });

        it('handles extensions with dots', function(): void {
            expect(SerializationFormat::fromExtension('.json'))->toBe(SerializationFormat::Json)
                ->and(SerializationFormat::fromExtension('.xml'))->toBe(SerializationFormat::Xml)
                ->and(SerializationFormat::fromExtension('.yaml'))->toBe(SerializationFormat::Yaml)
                ->and(SerializationFormat::fromExtension('.csv'))->toBe(SerializationFormat::Csv);
        });

        it('returns null for invalid extensions', function(): void {
            expect(SerializationFormat::fromExtension('txt'))->toBeNull()
                ->and(SerializationFormat::fromExtension('pdf'))->toBeNull()
                ->and(SerializationFormat::fromExtension(''))->toBeNull();
        });
    });

    describe('isValid Method', function(): void {
        it('validates correct format strings', function(): void {
            expect(SerializationFormat::isValid('json'))->toBeTrue()
                ->and(SerializationFormat::isValid('xml'))->toBeTrue()
                ->and(SerializationFormat::isValid('yaml'))->toBeTrue()
                ->and(SerializationFormat::isValid('csv'))->toBeTrue();
        });

        it('rejects invalid format strings', function(): void {
            expect(SerializationFormat::isValid('invalid'))->toBeFalse()
                ->and(SerializationFormat::isValid('pdf'))->toBeFalse()
                ->and(SerializationFormat::isValid(''))->toBeFalse()
                ->and(SerializationFormat::isValid('txt'))->toBeFalse();
        });

        it('is case-insensitive for validation', function(): void {
            expect(SerializationFormat::isValid('JSON'))->toBeTrue()
                ->and(SerializationFormat::isValid('XML'))->toBeTrue()
                ->and(SerializationFormat::isValid('YAML'))->toBeTrue()
                ->and(SerializationFormat::isValid('CSV'))->toBeTrue();
        });
    });

    describe('getFileExtension Method', function(): void {
        it('returns correct file extensions without dot', function(): void {
            expect(SerializationFormat::Json->getFileExtension())->toBe('json')
                ->and(SerializationFormat::Xml->getFileExtension())->toBe('xml')
                ->and(SerializationFormat::Yaml->getFileExtension())->toBe('yaml')
                ->and(SerializationFormat::Csv->getFileExtension())->toBe('csv');
        });
    });

    describe('getMimeType Method', function(): void {
        it('returns correct MIME types', function(): void {
            expect(SerializationFormat::Json->getMimeType())->toBe('application/json')
                ->and(SerializationFormat::Xml->getMimeType())->toBe('application/xml')
                ->and(SerializationFormat::Yaml->getMimeType())->toBe('application/x-yaml')
                ->and(SerializationFormat::Csv->getMimeType())->toBe('text/csv');
        });
    });

    describe('getSerializer Method', function(): void {
        it('returns correct serializer instances', function(): void {
            expect(SerializationFormat::Xml->getSerializer())->toBeInstanceOf(XmlSerializer::class)
                ->and(SerializationFormat::Yaml->getSerializer())->toBeInstanceOf(YamlSerializer::class)
                ->and(SerializationFormat::Csv->getSerializer())->toBeInstanceOf(CsvSerializer::class);
        });

        it('passes options to serializers', function(): void {
            $xmlSerializer = SerializationFormat::Xml->getSerializer(SerializerOptions::xml(rootElement: 'custom'));
            expect($xmlSerializer)->toBeInstanceOf(XmlSerializer::class);

            $yamlSerializer = SerializationFormat::Yaml->getSerializer(SerializerOptions::yaml(indent: 4));
            expect($yamlSerializer)->toBeInstanceOf(YamlSerializer::class);

            $csvSerializer = SerializationFormat::Csv->getSerializer(SerializerOptions::csv(delimiter: ';'));
            expect($csvSerializer)->toBeInstanceOf(CsvSerializer::class);
        });
    });

    describe('serializeTo Method Integration', function(): void {
        it('serializes DTO to JSON', function(): void {
            $dto = new class('John Doe', 30) extends SimpleDTO {
                public function __construct(
                    public readonly string $name,
                    public readonly int $age,
                ) {}
            };

            $json = $dto->serializeTo(SerializationFormat::Json);

            expect($json)->toBeString()
                ->and($json)->toContain('"name":"John Doe"')
                ->and($json)->toContain('"age":30');

            // Verify it's valid JSON
            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray()
                ->and($decoded['name'])->toBe('John Doe')
                ->and($decoded['age'])->toBe(30);
        });

        it('serializes DTO to XML', function(): void {
            $dto = new class('Jane Doe', 25) extends SimpleDTO {
                public function __construct(
                    public readonly string $name,
                    public readonly int $age,
                ) {}
            };

            $xml = $dto->serializeTo(SerializationFormat::Xml);

            expect($xml)->toBeString()
                ->and($xml)->toContain('<?xml version="1.0"')
                ->and($xml)->toContain('<name>Jane Doe</name>')
                ->and($xml)->toContain('<age>25</age>');
        });

        it('serializes DTO to YAML', function(): void {
            $dto = new class('Bob Smith', 35) extends SimpleDTO {
                public function __construct(
                    public readonly string $name,
                    public readonly int $age,
                ) {}
            };

            $yaml = $dto->serializeTo(SerializationFormat::Yaml);

            expect($yaml)->toBeString()
                ->and($yaml)->toContain('name:')
                ->and($yaml)->toContain('Bob Smith')
                ->and($yaml)->toContain('age: 35');
        });

        it('serializes DTO to CSV', function(): void {
            $dto = new class('Alice Johnson', 28) extends SimpleDTO {
                public function __construct(
                    public readonly string $name,
                    public readonly int $age,
                ) {}
            };

            $csv = $dto->serializeTo(SerializationFormat::Csv);

            expect($csv)->toBeString()
                ->and($csv)->toContain('name,age')
                ->and($csv)->toContain('Alice Johnson')
                ->and($csv)->toContain('28');
        });
    });

    describe('Edge Cases', function(): void {
        it('handles empty DTO', function(): void {
            $dto = new class extends SimpleDTO
            {
            };

            $json = $dto->serializeTo(SerializationFormat::Json);
            expect($json)->toBe('[]'); // Empty DTO returns empty array

            $xml = $dto->serializeTo(SerializationFormat::Xml);
            expect($xml)->toContain('<root');

            $yaml = $dto->serializeTo(SerializationFormat::Yaml);
            expect($yaml)->toBeString();

            $csv = $dto->serializeTo(SerializationFormat::Csv);
            expect($csv)->toBeString();
        });

        it('handles special characters in JSON', function(): void {
            $dto = new class('John "The Boss" Doe', 'test@example.com') extends SimpleDTO {
                public function __construct(
                    public readonly string $name,
                    public readonly string $email,
                ) {}
            };

            $json = $dto->serializeTo(SerializationFormat::Json);
            $decoded = json_decode($json, true);

            expect($decoded['name'])->toBe('John "The Boss" Doe')
                ->and($decoded['email'])->toBe('test@example.com');
        });

        it('handles special characters in XML', function(): void {
            $dto = new class('John & Jane', '<script>alert("xss")</script>') extends SimpleDTO {
                public function __construct(
                    public readonly string $name,
                    public readonly string $content,
                ) {}
            };

            $xml = $dto->serializeTo(SerializationFormat::Xml);

            expect($xml)->toContain('John &amp; Jane')
                ->and($xml)->toContain('&lt;script&gt;');
        });

        it('handles null values', function(): void {
            $dto = new class('John Doe', null) extends SimpleDTO {
                public function __construct(
                    public readonly string $name,
                    public readonly ?string $email,
                ) {}
            };

            $json = $dto->serializeTo(SerializationFormat::Json);
            expect($json)->toContain('"email":null');

            $xml = $dto->serializeTo(SerializationFormat::Xml);
            expect($xml)->toBeString();

            $yaml = $dto->serializeTo(SerializationFormat::Yaml);
            expect($yaml)->toContain('email: null');

            $csv = $dto->serializeTo(SerializationFormat::Csv);
            expect($csv)->toBeString();
        });

        it('handles nested arrays', function(): void {
            $dto = new class('John Doe', ['street' => '123 Main St', 'city' => 'NYC']) extends SimpleDTO {
                /** @param array<string, string> $address */
                public function __construct(
                    public readonly string $name,
                    public readonly array $address,
                ) {}
            };

            $json = $dto->serializeTo(SerializationFormat::Json);
            $decoded = json_decode($json, true);
            expect($decoded['address'])->toBeArray()
                ->and($decoded['address']['street'])->toBe('123 Main St');

            $xml = $dto->serializeTo(SerializationFormat::Xml);
            expect($xml)->toContain('<address>');

            $yaml = $dto->serializeTo(SerializationFormat::Yaml);
            expect($yaml)->toContain('address:');
        });
    });
});
