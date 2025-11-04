<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\SimpleDtoMapperTrait;

describe('SimpleDto from*() Methods with Template/Filter/Pipeline', function(): void {
    describe('fromJson() with Template', function(): void {
        it('works with template parameter', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $json = json_encode(['user' => ['id' => 123, 'name' => 'John']]);
            $template = [
                'id' => '{{ user.id }}',
                'name' => '{{ user.name }}',
            ];

            $result = $dto::fromJson($json, $template);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });

        it('works with filters parameter', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $json = json_encode(['name' => 'JOHN']);
            $filters = ['name' => new LowercaseStrings()];

            $result = $dto::fromJson($json, null, $filters);

            expect($result->name)->toBe('john');
        });

        it('works with pipeline parameter', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $json = json_encode(['name' => '  JOHN  ']);
            $pipeline = [new TrimStrings(), new LowercaseStrings()];

            $result = $dto::fromJson($json, null, null, $pipeline);

            expect($result->name)->toBe('john');
        });

        it('works with all parameters', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $json = json_encode(['user' => ['name' => '  JOHN  ']]);
            $template = ['name' => '{{ user.name }}'];
            $filters = ['name' => new LowercaseStrings()];
            $pipeline = [new TrimStrings()];

            $result = $dto::fromJson($json, $template, $filters, $pipeline);

            expect($result->name)->toBe('john');
        });
    });

    describe('fromXml() with Template', function(): void {
        it('works with template parameter', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $xml = '<?xml version="1.0"?><root><user><id>123</id><name>John</name></user></root>';
            $template = [
                'id' => '{{ user.id }}',
                'name' => '{{ user.name }}',
            ];

            $result = $dto::fromXml($xml, $template, null, null, 'root');

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });

        it('works with filters parameter', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $xml = '<?xml version="1.0"?><root><name>JOHN</name></root>';
            $filters = ['name' => new LowercaseStrings()];

            $result = $dto::fromXml($xml, null, $filters, null, 'root');

            expect($result->name)->toBe('john');
        });

        it('works with pipeline parameter', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $xml = '<?xml version="1.0"?><root><name>  JOHN  </name></root>';
            $pipeline = [new TrimStrings(), new LowercaseStrings()];

            $result = $dto::fromXml($xml, null, null, $pipeline, 'root');

            expect($result->name)->toBe('john');
        });
    });

    describe('fromYaml() with Template', function(): void {
        it('works with template parameter', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $yaml = "user:\n  id: 123\n  name: John";
            $template = [
                'id' => '{{ user.id }}',
                'name' => '{{ user.name }}',
            ];

            $result = $dto::fromYaml($yaml, $template);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });

        it('works with filters parameter', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $yaml = "name: JOHN";
            $filters = ['name' => new LowercaseStrings()];

            $result = $dto::fromYaml($yaml, null, $filters);

            expect($result->name)->toBe('john');
        });

        it('works with pipeline parameter', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $yaml = "name: '  JOHN  '";
            $pipeline = [new TrimStrings(), new LowercaseStrings()];

            $result = $dto::fromYaml($yaml, null, null, $pipeline);

            expect($result->name)->toBe('john');
        });
    });

    describe('fromCsv() with Template', function(): void {
        it('works with template parameter', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $userId = 0,
                    public readonly string $userName = '',
                ) {}
            };

            $csv = "user_id,user_name\n123,John";
            $template = [
                'userId' => '{{ user_id }}',
                'userName' => '{{ user_name }}',
            ];

            $result = $dto::fromCsv($csv, $template, null, null, true, ',');

            expect($result->userId)->toBe(123)
                ->and($result->userName)->toBe('John');
        });

        it('works with filters parameter', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $csv = "name\nJOHN";
            $filters = ['name' => new LowercaseStrings()];

            $result = $dto::fromCsv($csv, null, $filters, null, true, ',');

            expect($result->name)->toBe('john');
        });

        it('works with pipeline parameter', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $csv = "name\n  JOHN  ";
            $pipeline = [new TrimStrings(), new LowercaseStrings()];

            $result = $dto::fromCsv($csv, null, null, $pipeline, true, ',');

            expect($result->name)->toBe('john');
        });
    });

    describe('from() with DTO Configuration', function(): void {
        it('fromJson uses DTO template', function(): void {
            $dto = new class extends SimpleDto {
                protected function mapperTemplate(): array
                {
                    return [
                        'id' => '{{ user.id }}',
                        'name' => '{{ user.name }}',
                    ];
                }

                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $json = json_encode(['user' => ['id' => 123, 'name' => 'John']]);
            $result = $dto::fromJson($json);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });

        it('fromXml uses DTO filters', function(): void {
            $dto = new class extends SimpleDto {
                protected function mapperFilters(): array
                {
                    return [
                        'name' => new LowercaseStrings(),
                    ];
                }

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $xml = '<?xml version="1.0"?><root><name>JOHN</name></root>';
            $result = $dto::fromXml($xml, null, null, null, 'root');

            expect($result->name)->toBe('john');
        });

        it('fromYaml uses DTO pipeline', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoMapperTrait;

                protected function mapperPipeline(): array
                {
                    return [
                        new TrimStrings(),
                        new LowercaseStrings(),
                    ];
                }

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $yaml = "name: '  JOHN  '";
            $result = $dto::fromYaml($yaml);

            expect($result->name)->toBe('john');
        });
    });
});
