<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;
use event4u\DataHelpers\SimpleDto;

describe('SimpleDto Template/Filter/Pipeline Edge Cases', function(): void {
    describe('Empty Parameters', function(): void {
        it('handles all parameters as null', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $data = ['id' => 123, 'name' => 'John'];
            $result = $dto::from($data, null, null, null);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });

        it('handles empty template array', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $data = ['id' => 123, 'name' => 'John'];
            $result = $dto::from($data, [], null, null);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });

        it('handles empty filters array', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => '  John  '];
            $result = $dto::from($data, null, [], null);

            expect($result->name)->toBe('  John  ');
        });

        it('handles empty pipeline array', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => '  JOHN  '];
            $result = $dto::from($data, null, null, []);

            expect($result->name)->toBe('  JOHN  ');
        });
    });

    describe('Template Parameter Override', function(): void {
        it('parameter template overrides DTO template', function(): void {
            $dto = new class extends SimpleDto {
                protected function mapperTemplate(): array
                {
                    return [
                        'id' => '{{ wrong.id }}',
                        'name' => '{{ wrong.name }}',
                    ];
                }

                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'correct' => ['id' => 123, 'name' => 'John'],
                'wrong' => ['id' => 999, 'name' => 'Wrong'],
            ];

            $paramTemplate = [
                'id' => '{{ correct.id }}',
                'name' => '{{ correct.name }}',
            ];

            $result = $dto::from($data, $paramTemplate);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });

        it('parameter filters override DTO filters', function(): void {
            $dto = new class extends SimpleDto {
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

            $paramFilters = [
                'name' => new LowercaseStrings(),
            ];

            $result = $dto::from($data, null, $paramFilters);

            expect($result->name)->toBe('john');
        });

        it('parameter pipeline is merged with DTO pipeline', function(): void {
            $dto = new class extends SimpleDto {
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

            $paramPipeline = [
                new LowercaseStrings(),
            ];

            $result = $dto::from($data, null, null, $paramPipeline);

            // Should apply TrimStrings first (DTO), then LowercaseStrings (param)
            expect($result->name)->toBe('john');
        });
    });

    describe('Single Parameter Combinations', function(): void {
        it('works with only template', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $data = ['user' => ['id' => 123, 'name' => 'John']];
            $template = [
                'id' => '{{ user.id }}',
                'name' => '{{ user.name }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });

        it('works with only filters', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => 'JOHN'];
            $filters = [
                'name' => new LowercaseStrings(),
            ];

            $result = $dto::from($data, null, $filters);

            expect($result->name)->toBe('john');
        });

        it('works with only pipeline', function(): void {
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
    });

    describe('Two Parameter Combinations', function(): void {
        it('works with template + filters', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['user' => ['name' => 'JOHN']];
            $template = ['name' => '{{ user.name }}'];
            $filters = ['name' => new LowercaseStrings()];

            $result = $dto::from($data, $template, $filters);

            expect($result->name)->toBe('john');
        });

        it('works with template + pipeline', function(): void {
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

        it('works with filters + pipeline', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $firstName = '',
                    public readonly string $lastName = '',
                ) {}
            };

            $data = ['firstName' => '  JOHN  ', 'lastName' => '  DOE  '];
            $filters = ['firstName' => new LowercaseStrings()];
            $pipeline = [new TrimStrings()];

            $result = $dto::from($data, null, $filters, $pipeline);

            expect($result->firstName)->toBe('john')
                ->and($result->lastName)->toBe('DOE');
        });
    });

    describe('Three Parameter Combination', function(): void {
        it('works with template + filters + pipeline', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['user' => ['name' => '  JOHN  ']];
            $template = ['name' => '{{ user.name }}'];
            $filters = ['name' => new LowercaseStrings()];
            $pipeline = [new TrimStrings()];

            $result = $dto::from($data, $template, $filters, $pipeline);

            expect($result->name)->toBe('john');
        });
    });
});
