<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\SimpleDtoMapperTrait;

describe('SimpleDto fromArray() Edge Cases', function(): void {
    describe('fromArray() with DTO Configuration', function(): void {
        it('uses DTO template when no parameters provided', function(): void {
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

            $data = ['user' => ['id' => 123, 'name' => 'John']];
            $result = $dto::fromArray($data);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });

        it('uses DTO filters when no parameters provided', function(): void {
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

            $data = ['name' => 'JOHN'];
            $result = $dto::fromArray($data);

            expect($result->name)->toBe('john');
        });

        it('uses DTO pipeline when no parameters provided', function(): void {
            $dto = new class extends SimpleDto {
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

            $data = ['name' => '  JOHN  '];
            $result = $dto::fromArray($data);

            expect($result->name)->toBe('john');
        });

        it('uses all DTO configurations together', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoMapperTrait;

                protected function mapperTemplate(): array
                {
                    return [
                        'name' => '{{ user.name }}',
                    ];
                }

                protected function mapperFilters(): array
                {
                    return [
                        'name' => new LowercaseStrings(),
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
            $result = $dto::fromArray($data);

            expect($result->name)->toBe('john');
        });
    });

    describe('fromArray() Parameter Override', function(): void {
        it('parameter template overrides DTO template', function(): void {
            $dto = new class extends SimpleDto {
                protected function mapperTemplate(): array
                {
                    return [
                        'name' => '{{ wrong.name }}',
                    ];
                }

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'correct' => ['name' => 'John'],
                'wrong' => ['name' => 'Wrong'],
            ];

            $result = $dto::fromArray($data, ['name' => '{{ correct.name }}']);

            expect($result->name)->toBe('John');
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
            $result = $dto::fromArray($data, null, ['name' => new LowercaseStrings()]);

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
            $result = $dto::fromArray($data, null, null, [new LowercaseStrings()]);

            // Should apply TrimStrings first (DTO), then LowercaseStrings (param)
            expect($result->name)->toBe('john');
        });
    });

    describe('fromArray() without DTO Configuration', function(): void {
        it('works without any DTO configuration', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $data = ['id' => 123, 'name' => 'John'];
            $result = $dto::fromArray($data);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });

        it('works with only parameter template', function(): void {
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

            $result = $dto::fromArray($data, $template);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });

        it('works with only parameter filters', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => 'JOHN'];
            $filters = ['name' => new LowercaseStrings()];

            $result = $dto::fromArray($data, null, $filters);

            expect($result->name)->toBe('john');
        });

        it('works with only parameter pipeline', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => '  JOHN  '];
            $pipeline = [new TrimStrings(), new LowercaseStrings()];

            $result = $dto::fromArray($data, null, null, $pipeline);

            expect($result->name)->toBe('john');
        });
    });

    describe('fromArray() Edge Cases', function(): void {
        it('handles null parameters with DTO configuration', function(): void {
            $dto = new class extends SimpleDto {
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

            $data = ['user' => ['name' => 'John']];
            $result = $dto::fromArray($data, null, null, null);

            expect($result->name)->toBe('John');
        });

        it('handles empty arrays with DTO configuration', function(): void {
            $dto = new class extends SimpleDto {
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

            $data = ['user' => ['name' => 'John']];
            $result = $dto::fromArray($data, [], [], []);

            // Empty template should override DTO template
            expect($result->name)->toBe('');
        });
    });
});
