<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\MapInputName;
use event4u\DataHelpers\SimpleDto\Enums\NamingConvention;

describe('SimpleDto Template Priority', function(): void {
    describe('Template vs MapFrom', function(): void {
        it('template overrides MapFrom attribute', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    #[MapFrom('wrong_id')]
                    public readonly int $id = 0,
                    #[MapFrom('wrong_name')]
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'wrong_id' => 999,
                'wrong_name' => 'Wrong',
                'correct' => [
                    'id' => 123,
                    'name' => 'John',
                ],
            ];

            $template = [
                'id' => '{{ correct.id }}',
                'name' => '{{ correct.name }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });

        it('without template, MapFrom is used', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    #[MapFrom('user_id')]
                    public readonly int $id = 0,
                    #[MapFrom('user_name')]
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'user_id' => 123,
                'user_name' => 'John',
            ];

            $result = $dto::from($data);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });

        it('template overrides MapFrom with nested properties', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    #[MapFrom('product.id')]
                    public readonly int $id = 0,
                    #[MapFrom('product.name')]
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'product' => [
                    'id' => 999,
                    'name' => 'Wrong Product',
                ],
                'user' => [
                    'id' => 123,
                    'name' => 'John',
                ],
            ];

            $template = [
                'id' => '{{ user.id }}',
                'name' => '{{ user.name }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });
    });

    describe('Template vs MapInputName', function(): void {
        it('template overrides MapInputName convention', function(): void {
            $dto = new class extends SimpleDto {
                #[MapInputName(NamingConvention::SNAKE_CASE)]
                public function __construct(
                    public readonly int $userId = 0,
                    public readonly string $userName = '',
                ) {}
            };

            $data = [
                'user_id' => 999,
                'user_name' => 'Wrong',
                'correct' => [
                    'id' => 123,
                    'name' => 'John',
                ],
            ];

            $template = [
                'userId' => '{{ correct.id }}',
                'userName' => '{{ correct.name }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->userId)->toBe(123)
                ->and($result->userName)->toBe('John');
        });
    });

    describe('Template vs Auto-mapping', function(): void {
        it('template overrides auto-mapping', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'id' => 999,
                'name' => 'Wrong',
                'user' => [
                    'id' => 123,
                    'name' => 'John',
                ],
            ];

            $template = [
                'id' => '{{ user.id }}',
                'name' => '{{ user.name }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });

        it('without template, auto-mapping is used', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'id' => 123,
                'name' => 'John',
            ];

            $result = $dto::from($data);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });
    });

    describe('DTO Template vs Parameter Template', function(): void {
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
                'wrong' => ['id' => 999, 'name' => 'Wrong'],
                'correct' => ['id' => 123, 'name' => 'John'],
            ];

            $paramTemplate = [
                'id' => '{{ correct.id }}',
                'name' => '{{ correct.name }}',
            ];

            $result = $dto::from($data, $paramTemplate);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });

        it('DTO template is used when no parameter template', function(): void {
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
            $result = $dto::from($data);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });
    });

    describe('Priority Chain', function(): void {
        it('follows priority: Parameter Template > DTO Template > MapFrom > MapInputName > Auto-mapping', function(): void {
            $dto = new class extends SimpleDto {
                #[MapInputName(NamingConvention::SNAKE_CASE)]
                public function __construct(
                    #[MapFrom('map_from_id')]
                    public readonly int $userId = 0,
                    #[MapFrom('map_from_name')]
                    public readonly string $userName = '',
                ) {}
            };

            $data = [
                'userId' => 1,           // Auto-mapping (lowest priority)
                'userName' => 'Auto',
                'user_id' => 2,          // MapInputName
                'user_name' => 'MapInputName',
                'map_from_id' => 3,      // MapFrom
                'map_from_name' => 'MapFrom',
                'dto_template' => [      // DTO Template
                    'id' => 4,
                    'name' => 'DtoTemplate',
                ],
                'param_template' => [    // Parameter Template (highest priority)
                    'id' => 5,
                    'name' => 'ParamTemplate',
                ],
            ];

            // Test with parameter template (highest priority)
            $paramTemplate = [
                'userId' => '{{ param_template.id }}',
                'userName' => '{{ param_template.name }}',
            ];

            $result = $dto::from($data, $paramTemplate);

            expect($result->userId)->toBe(5)
                ->and($result->userName)->toBe('ParamTemplate');
        });

        it('uses MapFrom when no template provided', function(): void {
            $dto = new class extends SimpleDto {
                #[MapInputName(NamingConvention::SNAKE_CASE)]
                public function __construct(
                    #[MapFrom('map_from_id')]
                    public readonly int $userId = 0,
                    #[MapFrom('map_from_name')]
                    public readonly string $userName = '',
                ) {}
            };

            $data = [
                'userId' => 1,
                'userName' => 'Auto',
                'user_id' => 2,
                'user_name' => 'MapInputName',
                'map_from_id' => 3,
                'map_from_name' => 'MapFrom',
            ];

            $result = $dto::from($data);

            expect($result->userId)->toBe(3)
                ->and($result->userName)->toBe('MapFrom');
        });
    });
});
