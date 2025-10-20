<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\SimpleDTO;

describe('SimpleDTO Mapper Integration', function(): void {
    describe('mapperTemplate() Method', function(): void {
        it('uses template from DTO definition', function(): void {
            $dto = new class extends SimpleDTO {
                protected function mapperTemplate(): array
                {
                    return [
                        'id' => '{{ user.id }}',
                        'name' => '{{ user.full_name }}',
                    ];
                }

                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'user' => [
                    'id' => 123,
                    'full_name' => 'John Doe',
                ],
            ];

            $result = $dto::fromSource($data);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John Doe');
        });

        it('works without template definition', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'id' => 123,
                'name' => 'John Doe',
            ];

            $result = $dto::fromSource($data);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John Doe');
        });
    });

    describe('mapperFilters() Method', function(): void {
        it('uses property filters with template', function(): void {
            $dto = new class extends SimpleDTO {
                protected function mapperTemplate(): array
                {
                    return [
                        'name' => '{{ name }}',
                    ];
                }

                protected function mapperFilters(): array
                {
                    return [
                        'name' => new UppercaseStrings(),
                    ];
                }

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => 'john'];

            $result = $dto::fromSource($data);

            expect($result->name)->toBe('JOHN');
        });

        it('works without filter definition', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => 'John'];

            $result = $dto::fromSource($data);

            expect($result->name)->toBe('John');
        });
    });

    describe('mapperPipeline() Method', function(): void {
        it('uses pipeline filters with template', function(): void {
            $dto = new class extends SimpleDTO {
                protected function mapperTemplate(): array
                {
                    return [
                        'name' => '{{ name }}',
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

            $data = ['name' => '  John  '];

            $result = $dto::fromSource($data);

            expect($result->name)->toBe('John');
        });

        it('works without pipeline definition', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => '  John  '];

            $result = $dto::fromSource($data);

            expect($result->name)->toBe('  John  ');
        });
    });

    describe('Template Override', function(): void {
        it('overrides template with parameter', function(): void {
            $dto = new class extends SimpleDTO {
                protected function mapperTemplate(): array
                {
                    return [
                        'name' => '{{ user.name }}',
                    ];
                }

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'user' => [
                    'name' => 'John',
                    'custom_name' => 'Jane',
                ],
            ];

            // With override
            $result = $dto::fromSource($data, [
                'name' => '{{ user.custom_name }}',
            ]);

            expect($result->name)->toBe('Jane');
        });
    });

    describe('Property Filters Override', function(): void {
        it('overrides property filters with parameter', function(): void {
            $dto = new class extends SimpleDTO {
                protected function mapperTemplate(): array
                {
                    return [
                        'name' => '{{ name }}',
                    ];
                }

                protected function mapperFilters(): array
                {
                    return [
                        'name' => new UppercaseStrings(),
                    ];
                }

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => 'JOHN'];

            // With override (lowercase filter)
            $result = $dto::fromSource($data, ['name' => '{{ name }}'], [
                'name' => new LowercaseStrings(),
            ]);

            expect($result->name)->toBe('john');
        });
    });

    describe('Pipeline Filters Override', function(): void {
        it('merges pipeline filters from DTO and parameter', function(): void {
            $dto = new class extends SimpleDTO {
                protected function mapperTemplate(): array
                {
                    return [
                        'name' => '{{ name }}',
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

            $data = ['name' => '  JOHN  '];

            // With additional pipeline filter
            $result = $dto::fromSource($data, ['name' => '{{ name }}'], null, [
                new LowercaseStrings(),
            ]);

            expect($result->name)->toBe('john');
        });
    });
});

