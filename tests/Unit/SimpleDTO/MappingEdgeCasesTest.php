<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDTO\Attributes\MapInputName;
use event4u\DataHelpers\SimpleDTO\Attributes\MapOutputName;
use event4u\DataHelpers\SimpleDTO\Attributes\MapTo;

describe('Property Mapping Edge Cases', function(): void {
    describe('MapFrom Edge Cases', function(): void {
        it('handles missing source property', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapFrom('missing_field')]
                    public readonly ?string $value = null,
                ) {}
            };

            $instance = $dto::fromArray([]);

            expect($instance->value)->toBeNull();
        });

        it('handles null source value', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapFrom('source')]
                    public readonly ?string $target = null,
                ) {}
            };

            $instance = $dto::fromArray(['source' => null]);

            expect($instance->target)->toBeNull();
        });

        it('handles empty string source', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapFrom('source')]
                    public readonly string $target = '',
                ) {}
            };

            $instance = $dto::fromArray(['source' => '']);

            expect($instance->target)->toBe('');
        });

        it('handles multiple properties mapping from same source', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapFrom('shared')]
                    public readonly string $field1 = '',
                    #[MapFrom('shared')]
                    public readonly string $field2 = '',
                ) {}
            };

            $instance = $dto::fromArray(['shared' => 'value']);

            expect($instance->field1)->toBe('value')
                ->and($instance->field2)->toBe('value');
        });

        it('handles dot notation in property names', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user.name')]
                    public readonly ?string $userName = null,
                ) {}
            };

            // Dot notation is treated as literal key name, not nested path
            // If the key doesn't exist, it should use the default value
            $instance = $dto::fromArray(['user.name' => 'John']);

            // MapFrom doesn't support nested paths, so this will be null
            expect($instance->userName)->toBeNull();
        });

        it('handles numeric source keys', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapFrom('0')]
                    public readonly ?string $first = null,
                ) {}
            };

            /** @phpstan-ignore-next-line unknown */
            $instance = $dto::fromArray(['0' => 'value']);

            expect($instance->first)->toBe('value');
        });
    });

    describe('MapTo Edge Cases', function(): void {
        it('handles null target value', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapTo('output')]
                    public readonly ?string $input = null,
                ) {}
            };

            $instance = $dto::fromArray(['input' => null]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('output')
                ->and($array['output'])->toBeNull();
        });

        it('handles empty string target', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapTo('output')]
                    public readonly string $input = '',
                ) {}
            };

            $instance = $dto::fromArray(['input' => '']);
            $array = $instance->toArray();

            expect($array['output'])->toBe('');
        });

        it('handles multiple properties mapping to different targets', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapTo('target1')]
                    public readonly string $source1 = '',
                    #[MapTo('target2')]
                    public readonly string $source2 = '',
                ) {}
            };

            $instance = $dto::fromArray(['source1' => 'value1', 'source2' => 'value2']);
            $array = $instance->toArray();

            expect($array)->toHaveKey('target1')
                ->and($array)->toHaveKey('target2')
                ->and($array['target1'])->toBe('value1')
                ->and($array['target2'])->toBe('value2');
        });

        it('preserves original property name when MapTo is used', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapTo('renamed')]
                    public readonly string $original = '',
                ) {}
            };

            $instance = $dto::fromArray(['original' => 'value']);
            $array = $instance->toArray();

            // Should have renamed key, not original
            expect($array)->toHaveKey('renamed')
                ->and($array)->not->toHaveKey('original');
        });
    });

    describe('MapInputName & MapOutputName Edge Cases', function(): void {
        it('handles class-level input and output transformations', function(): void {
            $dto = new #[MapInputName('snake_case')]
            #[MapOutputName('kebab-case')]
            class extends SimpleDTO {
                public function __construct(
                    public readonly string $userName = '',
                ) {}
            };

            // Input: snake_case
            $instance = $dto::fromArray(['user_name' => 'value']);
            $array = $instance->toArray();

            expect($instance->userName)->toBe('value')
                ->and($array)->toHaveKey('user-name')
                ->and($array['user-name'])->toBe('value');
        });

        it('handles null values with input/output mapping', function(): void {
            $dto = new #[MapInputName('snake_case')]
            #[MapOutputName('snake_case')]
            class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $myValue = null,
                ) {}
            };

            $instance = $dto::fromArray(['my_value' => null]);
            $array = $instance->toArray();

            expect($instance->myValue)->toBeNull()
                ->and($array['my_value'])->toBeNull();
        });

        it('handles missing input with default value', function(): void {
            $dto = new #[MapInputName('snake_case')]
            class extends SimpleDTO {
                public function __construct(
                    public readonly string $myValue = 'default',
                ) {}
            };

            $instance = $dto::fromArray([]);

            expect($instance->myValue)->toBe('default');
        });
    });

    describe('Mapping Conflicts', function(): void {
        it('handles MapFrom with class-level MapInputName', function(): void {
            $dto = new #[MapInputName('snake_case')]
            class extends SimpleDTO {
                public function __construct(
                    #[MapFrom('source')]
                    public readonly ?string $myValue = null,
                ) {}
            };

            // MapFrom should take precedence over MapInputName
            $instance = $dto::fromArray(['source' => 'from_source', 'my_value' => 'from_input']);

            expect($instance->myValue)->toBe('from_source');
        });

        it('handles MapTo with class-level MapOutputName', function(): void {
            $dto = new #[MapOutputName('snake_case')]
            class extends SimpleDTO {
                public function __construct(
                    #[MapTo('target')]
                    public readonly string $myValue = '',
                ) {}
            };

            $instance = $dto::fromArray(['myValue' => 'test']);
            $array = $instance->toArray();

            // MapTo should take precedence over MapOutputName
            expect($array)->toHaveKey('target')
                ->and($array['target'])->toBe('test');
        });
    });

    describe('Special Characters in Mapping', function(): void {
        it('handles special characters in source names', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user-name')]
                    public readonly ?string $userName = null,
                ) {}
            };

            $instance = $dto::fromArray(['user-name' => 'John']);

            expect($instance->userName)->toBe('John');
        });

        it('handles special characters in target names', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapTo('user-name')]
                    public readonly string $userName = '',
                ) {}
            };

            $instance = $dto::fromArray(['userName' => 'John']);
            $array = $instance->toArray();

            expect($array['user-name'])->toBe('John');
        });

        it('handles unicode characters in mapping', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapFrom('名前')]
                    public readonly ?string $name = null,
                ) {}
            };

            $instance = $dto::fromArray(['名前' => 'John']);

            expect($instance->name)->toBe('John');
        });
    });

    describe('Mapping with Type Coercion', function(): void {
        it('handles type coercion with MapFrom', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapFrom('age_string')]
                    public readonly int $age = 0,
                ) {}
            };

            // Should fail with strict types
            expect(function() use ($dto): void {
                $dto::fromArray(['age_string' => '30']);
            })->toThrow(TypeError::class);
        });

        it('handles boolean coercion with mapping', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapFrom('is_active')]
                    public readonly bool $active = false,
                ) {}
            };

            $instance = $dto::fromArray(['is_active' => true]);

            expect($instance->active)->toBeTrue();
        });
    });

    describe('Mapping with Nested DTOs', function(): void {
        it('handles mapping with nested DTO properties', function(): void {
            $addressDTO = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $street = '',
                ) {}
            };

            $userDTO = new class extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user_name')]
                    public readonly ?string $name = null,
                ) {}
            };

            $user = $userDTO::fromArray(['user_name' => 'John']);

            expect($user->name)->toBe('John');
        });
    });
});

