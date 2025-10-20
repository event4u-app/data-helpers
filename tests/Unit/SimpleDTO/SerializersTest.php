<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO\Serializers\CsvSerializer;
use event4u\DataHelpers\SimpleDTO\Serializers\SerializerInterface;
use event4u\DataHelpers\SimpleDTO\Serializers\XmlSerializer;
use event4u\DataHelpers\SimpleDTO\Serializers\YamlSerializer;
use Tests\Unit\SimpleDTO\Fixtures\UserDTO;

describe('Serializers', function(): void {
    describe('XML Serializer', function(): void {
        it('serializes DTO to XML', function(): void {
            $user = UserDTO::fromArray(['name' => 'John Doe', 'age' => 30]);
            $xml = $user->toXml();

            expect($xml)->toContain('<?xml version="1.0"')
                ->and($xml)->toContain('<root>')
                ->and($xml)->toContain('<name>John Doe</name>')
                ->and($xml)->toContain('<age>30</age>')
                ->and($xml)->toContain('</root>');
        });

        it('serializes with custom root element', function(): void {
            $user = UserDTO::fromArray(['name' => 'Jane', 'age' => 25]);
            $xml = $user->toXml('user');

            expect($xml)->toContain('<user>')
                ->and($xml)->toContain('</user>');
        });

        it('handles special characters in XML', function(): void {
            $user = UserDTO::fromArray(['name' => 'John & Jane', 'age' => 30]);
            $xml = $user->toXml();

            expect($xml)->toContain('John &amp; Jane');
        });

        it('serializes nested arrays', function(): void {
            $serializer = new XmlSerializer();
            $data = [
                'user' => [
                    'name' => 'John',
                    'age' => 30,
                ],
            ];

            $xml = $serializer->serialize($data);

            expect($xml)->toContain('<user>')
                ->and($xml)->toContain('<name>John</name>')
                ->and($xml)->toContain('</user>');
        });

        it('handles empty values', function(): void {
            $user = UserDTO::fromArray(['name' => '', 'age' => 0]);
            $xml = $user->toXml();

            expect($xml)->toContain('<name')
                ->and($xml)->toContain('<age>0</age>');
        });

        it('returns correct content type', function(): void {
            $serializer = new XmlSerializer();

            expect($serializer->getContentType())->toBe('application/xml');
        });
    });

    describe('YAML Serializer', function(): void {
        it('serializes DTO to YAML', function(): void {
            $user = UserDTO::fromArray(['name' => 'John Doe', 'age' => 30]);
            $yaml = $user->toYaml();

            expect($yaml)->toContain('name: John Doe')
                ->and($yaml)->toContain('age: 30');
        });

        it('serializes with custom indent', function(): void {
            $user = UserDTO::fromArray(['name' => 'Jane', 'age' => 25]);
            $yaml = $user->toYaml(4);

            expect($yaml)->toContain('name: Jane');
        });

        it('handles nested arrays', function(): void {
            $serializer = new YamlSerializer();
            $data = [
                'user' => [
                    'name' => 'John',
                    'age' => 30,
                ],
            ];

            $yaml = $serializer->serialize($data);

            expect($yaml)->toContain('user:')
                ->and($yaml)->toContain('  name: John')
                ->and($yaml)->toContain('  age: 30');
        });

        it('handles sequential arrays', function(): void {
            $serializer = new YamlSerializer();
            $data = [
                'users' => [
                    ['name' => 'John', 'age' => 30],
                    ['name' => 'Jane', 'age' => 25],
                ],
            ];

            $yaml = $serializer->serialize($data);

            expect($yaml)->toContain('users:')
                ->and($yaml)->toContain('  -')
                ->and($yaml)->toContain('    name: John');
        });

        it('handles boolean values', function(): void {
            $serializer = new YamlSerializer();
            $data = ['active' => true, 'deleted' => false];

            $yaml = $serializer->serialize($data);

            expect($yaml)->toContain('active: true')
                ->and($yaml)->toContain('deleted: false');
        });

        it('handles null values', function(): void {
            $serializer = new YamlSerializer();
            $data = ['name' => null];

            $yaml = $serializer->serialize($data);

            expect($yaml)->toContain('name: null');
        });

        it('returns correct content type', function(): void {
            $serializer = new YamlSerializer();

            expect($serializer->getContentType())->toBe('application/x-yaml');
        });
    });

    describe('CSV Serializer', function(): void {
        it('serializes DTO to CSV', function(): void {
            $user = UserDTO::fromArray(['name' => 'John Doe', 'age' => 30]);
            $csv = $user->toCsv();

            expect($csv)->toContain('name,age')
                ->and($csv)->toContain('John Doe,30');
        });

        it('serializes without headers', function(): void {
            $user = UserDTO::fromArray(['name' => 'Jane', 'age' => 25]);
            $csv = $user->toCsv(false);

            expect($csv)->not->toContain('name,age')
                ->and($csv)->toContain('Jane,25');
        });

        it('serializes with custom delimiter', function(): void {
            $user = UserDTO::fromArray(['name' => 'John', 'age' => 30]);
            $csv = $user->toCsv(true, ';');

            expect($csv)->toContain('name;age')
                ->and($csv)->toContain('John;30');
        });

        it('handles values with commas', function(): void {
            $user = UserDTO::fromArray(['name' => 'Doe, John', 'age' => 30]);
            $csv = $user->toCsv();

            expect($csv)->toContain('"Doe, John"');
        });

        it('serializes collection', function(): void {
            $serializer = new CsvSerializer();
            $data = [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ];

/** @phpstan-ignore-next-line argument.type (Serializer test) */
            $csv = $serializer->serialize($data);

            expect($csv)->toContain('name,age')
                ->and($csv)->toContain('John,30')
                ->and($csv)->toContain('Jane,25');
        });

        it('handles boolean values', function(): void {
            $serializer = new CsvSerializer();
            $data = ['active' => true, 'deleted' => false];

            $csv = $serializer->serialize($data);

            expect($csv)->toContain('true')
                ->and($csv)->toContain('false');
        });

        it('handles null values', function(): void {
            $serializer = new CsvSerializer();
            $data = ['name' => null, 'age' => 30];

            $csv = $serializer->serialize($data);

            expect($csv)->toContain(',30');
        });

        it('returns correct content type', function(): void {
            $serializer = new CsvSerializer();

            expect($serializer->getContentType())->toBe('text/csv');
        });
    });

    describe('Custom Serializer', function(): void {
        it('uses custom serializer', function(): void {
            $user = UserDTO::fromArray(['name' => 'John', 'age' => 30]);

            $customSerializer = new class implements SerializerInterface {
                public function serialize(array $data): string
                {
                    return 'CUSTOM:' . json_encode($data);
                }

                public function getContentType(): string
                {
                    return 'application/custom';
                }
            };

            $result = $user->serializeWith($customSerializer);

            expect($result)->toStartWith('CUSTOM:')
                ->and($result)->toContain('John')
                ->and($result)->toContain('30');
        });
    });

    describe('Edge Cases', function(): void {
        it('handles empty DTO', function(): void {
            $user = UserDTO::fromArray(['name' => '', 'age' => 0]);

            $xml = $user->toXml();
            $yaml = $user->toYaml();
            $csv = $user->toCsv();

            expect($xml)->toContain('<name')
                ->and($yaml)->toContain('name:')
                ->and($csv)->toContain(',0');
        });

        it('serializes with wrapping', function(): void {
            $user = UserDTO::fromArray(['name' => 'John', 'age' => 30]);
            $wrapped = $user->wrap('user');

            $xml = $wrapped->toXml();
            $yaml = $wrapped->toYaml();

            expect($xml)->toContain('<user>')
                ->and($yaml)->toContain('user:');
        });
    });
});

