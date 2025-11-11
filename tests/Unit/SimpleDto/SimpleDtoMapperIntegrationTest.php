<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;
use event4u\DataHelpers\SimpleDto;

describe('SimpleDto Mapper Integration', function(): void {
    describe('getMapperTemplate() Method', function(): void {
        it('uses template from Dto property definition', function(): void {
            $dto = new class extends SimpleDto {
                protected ?array $mapperTemplate = [
                    'id' => '{{ user.id }}',
                    'name' => '{{ user.full_name }}',
                ];

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

            $result = $dto::from($data);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John Doe');
        });

        it('uses template from overridden getMapperTemplate() method', function(): void {
            $dto = new class extends SimpleDto {
                public function getMapperTemplate(): array
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

            $result = $dto::from($data);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John Doe');
        });

        it('works without template definition', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'id' => 123,
                'name' => 'John Doe',
            ];

            $result = $dto::from($data);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John Doe');
        });
    });

    describe('setMapperTemplate() Method', function(): void {
        it('sets template on instance', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $instance = new $dto(id: 1, name: 'Test');

            // Set template on instance
            $instance->setMapperTemplate([
                'id' => '{{ user.id }}',
                'name' => '{{ user.full_name }}',
            ]);

            expect($instance->getMapperTemplate())->toBe([
                'id' => '{{ user.id }}',
                'name' => '{{ user.full_name }}',
            ]);
        });

        it('clears template when set to null', function(): void {
            $dto = new class extends SimpleDto {
                protected ?array $mapperTemplate = [
                    'id' => '{{ user.id }}',
                    'name' => '{{ user.name }}',
                ];

                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $instance = new $dto(id: 1, name: 'Test');

            expect($instance->getMapperTemplate())->toBe([
                'id' => '{{ user.id }}',
                'name' => '{{ user.name }}',
            ]);

            // Clear template
            $instance->setMapperTemplate(null);

            expect($instance->getMapperTemplate())->toBeNull();
        });

        it('changes template and remaps with new template', function(): void {
            $dto = new class extends SimpleDto {
                protected ?array $mapperTemplate = [
                    'id' => '{{ user.id }}',
                    'name' => '{{ user.name }}',
                ];

                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            // First mapping with initial template
            $data1 = [
                'user' => [
                    'id' => 100,
                    'name' => 'Initial Name',
                ],
            ];

            $result1 = $dto::from($data1);

            expect($result1->id)->toBe(100)
                ->and($result1->name)->toBe('Initial Name');

            // Change template on the class by creating new instance with modified template
            $instance = new $dto(id: 0, name: '');
            $instance->setMapperTemplate([
                'id' => '{{ customer.customer_id }}',
                'name' => '{{ customer.full_name }}',
            ]);

            // Second mapping with new template
            $data2 = [
                'customer' => [
                    'customer_id' => 200,
                    'full_name' => 'New Customer Name',
                ],
            ];

            $result2 = $dto::from($data2, $instance->getMapperTemplate());

            expect($result2->id)->toBe(200)
                ->and($result2->name)->toBe('New Customer Name');
        });
    });

    describe('mapperFilters() Method', function(): void {
        it('uses property filters with template', function(): void {
            $dto = new class extends SimpleDto {
                public function getMapperTemplate(): array
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

            $result = $dto::from($data);

            expect($result->name)->toBe('JOHN');
        });

        it('works without filter definition', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => 'John'];

            $result = $dto::from($data);

            expect($result->name)->toBe('John');
        });
    });

    describe('mapperPipeline() Method', function(): void {
        it('uses pipeline filters with template', function(): void {
            $dto = new class extends SimpleDto {
                public function getMapperTemplate(): array
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

            $result = $dto::from($data);

            expect($result->name)->toBe('John');
        });

        it('works without pipeline definition', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => '  John  '];

            $result = $dto::from($data);

            expect($result->name)->toBe('  John  ');
        });
    });

    describe('Template Override', function(): void {
        it('overrides template with parameter', function(): void {
            $dto = new class extends SimpleDto {
                public function getMapperTemplate(): array
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
            $result = $dto::from($data, [
                'name' => '{{ user.custom_name }}',
            ]);

            expect($result->name)->toBe('Jane');
        });
    });

    describe('Property Filters Override', function(): void {
        it('overrides property filters with parameter', function(): void {
            $dto = new class extends SimpleDto {
                public function getMapperTemplate(): array
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
            $result = $dto::from($data, ['name' => '{{ name }}'], [
                'name' => new LowercaseStrings(),
            ]);

            expect($result->name)->toBe('john');
        });
    });

    describe('Pipeline Filters Override', function(): void {
        it('merges pipeline filters from Dto and parameter', function(): void {
            $dto = new class extends SimpleDto {
                public function getMapperTemplate(): array
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
            $result = $dto::from($data, ['name' => '{{ name }}'], null, [
                new LowercaseStrings(),
            ]);

            expect($result->name)->toBe('john');
        });
    });
});
