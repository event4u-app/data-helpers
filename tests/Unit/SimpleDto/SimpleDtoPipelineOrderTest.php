<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\SimpleDtoMapperTrait;

describe('SimpleDto Pipeline Order and Merging', function(): void {
    describe('Pipeline Order', function(): void {
        it('applies pipeline filters in correct order', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => '  john  '];
            $pipeline = [
                new TrimStrings(),        // First: '  john  ' -> 'john'
                new UppercaseStrings(),   // Second: 'john' -> 'JOHN'
            ];

            $result = $dto::from($data, null, null, $pipeline);

            expect($result->name)->toBe('JOHN');
        });

        it('different order produces different result', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => '  john  '];
            $pipeline = [
                new UppercaseStrings(),   // First: '  john  ' -> '  JOHN  '
                new TrimStrings(),        // Second: '  JOHN  ' -> 'JOHN'
            ];

            $result = $dto::from($data, null, null, $pipeline);

            expect($result->name)->toBe('JOHN');
        });
    });

    describe('Pipeline Merging', function(): void {
        it('DTO pipeline is applied before parameter pipeline', function(): void {
            $dto = new class extends SimpleDto {
                protected function mapperPipeline(): array
                {
                    return [
                        new TrimStrings(),  // First (from DTO)
                    ];
                }

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => '  john  '];
            $paramPipeline = [
                new UppercaseStrings(),  // Second (from parameter)
            ];

            $result = $dto::from($data, null, null, $paramPipeline);

            // Should apply TrimStrings first, then UppercaseStrings
            expect($result->name)->toBe('JOHN');
        });

        it('multiple DTO pipeline filters are preserved', function(): void {
            $dto = new class extends SimpleDto {
                protected function mapperPipeline(): array
                {
                    return [
                        new TrimStrings(),       // First
                        new LowercaseStrings(),  // Second
                    ];
                }

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => '  JOHN  '];
            $result = $dto::from($data);

            expect($result->name)->toBe('john');
        });

        it('multiple parameter pipeline filters are preserved', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => '  JOHN  '];
            $pipeline = [
                new TrimStrings(),
                new LowercaseStrings(),
            ];

            $result = $dto::from($data, null, null, $pipeline);

            expect($result->name)->toBe('john');
        });

        it('merges multiple DTO and parameter pipeline filters', function(): void {
            $dto = new class extends SimpleDto {
                protected function mapperPipeline(): array
                {
                    return [
                        new TrimStrings(),       // First (DTO)
                        new LowercaseStrings(),  // Second (DTO)
                    ];
                }

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $title = '',
                ) {}
            };

            $data = ['name' => '  JOHN  ', 'title' => '  MR  '];
            $paramPipeline = [
                new UppercaseStrings(),  // Third (parameter) - will uppercase the already lowercased string
            ];

            $result = $dto::from($data, null, null, $paramPipeline);

            // TrimStrings -> LowercaseStrings -> UppercaseStrings
            expect($result->name)->toBe('JOHN')
                ->and($result->title)->toBe('MR');
        });

        it('empty DTO pipeline does not affect parameter pipeline', function(): void {
            $dto = new class extends SimpleDto {
                protected function mapperPipeline(): array
                {
                    return [];
                }

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => '  JOHN  '];
            $paramPipeline = [
                new TrimStrings(),
                new LowercaseStrings(),
            ];

            $result = $dto::from($data, null, null, $paramPipeline);

            expect($result->name)->toBe('john');
        });

        it('null DTO pipeline does not affect parameter pipeline', function(): void {
            $dto = new class extends SimpleDto {
                protected function mapperPipeline(): ?array
                {
                    return null;
                }

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => '  JOHN  '];
            $paramPipeline = [
                new TrimStrings(),
                new LowercaseStrings(),
            ];

            $result = $dto::from($data, null, null, $paramPipeline);

            expect($result->name)->toBe('john');
        });
    });

    describe('Pipeline with Template', function(): void {
        it('pipeline is applied after template mapping', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['user' => ['name' => '  JOHN  ']];
            $template = ['name' => '{{ user.name }}'];
            $pipeline = [
                new TrimStrings(),
                new LowercaseStrings(),
            ];

            $result = $dto::from($data, $template, null, $pipeline);

            expect($result->name)->toBe('john');
        });

        it('pipeline is applied after template with DTO configuration', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoMapperTrait;

                protected function mapperTemplate(): array
                {
                    return [
                        'name' => '{{ user.name }}',
                    ];
                }

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

            $data = ['user' => ['name' => '  JOHN  ']];
            $result = $dto::from($data);

            expect($result->name)->toBe('john');
        });
    });

    describe('Pipeline with Filters', function(): void {
        it('property filters are applied before pipeline', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $firstName = '',
                    public readonly string $lastName = '',
                ) {}
            };

            $data = ['firstName' => '  JOHN  ', 'lastName' => '  DOE  '];
            $filters = [
                'firstName' => new LowercaseStrings(),  // Applied to firstName only
            ];
            $pipeline = [
                new TrimStrings(),  // Applied to all properties
            ];

            $result = $dto::from($data, null, $filters, $pipeline);

            expect($result->firstName)->toBe('john')  // Lowercased + Trimmed
                ->and($result->lastName)->toBe('DOE');  // Only Trimmed
        });
    });

    describe('Complex Pipeline Scenarios', function(): void {
        it('handles complex pipeline with template, filters, and multiple steps', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoMapperTrait;

                protected function mapperTemplate(): array
                {
                    return [
                        'name' => '{{ user.name }}',
                    ];
                }

                protected function mapperPipeline(): array
                {
                    return [
                        new TrimStrings(),
                    ];
                }

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['user' => ['name' => '  JOHN  ']];

            $paramPipeline = [
                new LowercaseStrings(),
            ];

            $result = $dto::from($data, null, null, $paramPipeline);

            // Template creates: '  JOHN  '
            // TrimStrings (DTO): 'JOHN'
            // LowercaseStrings (param): 'john'
            expect($result->name)->toBe('john');
        });
    });
});
