<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\SimpleDTODoctrineTrait;
use event4u\DataHelpers\SimpleDTO\SimpleDTOEloquentTrait;

describe('Framework Independence', function(): void {
    describe('SimpleDTOTrait (Core)', function(): void {
        it('works without any framework dependencies', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John Doe',
                    public readonly string $email = 'john@example.com',
                ) {}
            };

            $instance = $dto::fromArray([]);

            expect($instance->name)->toBe('John Doe');
            expect($instance->email)->toBe('john@example.com');
            expect($instance->toArray())->toBe([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);
        });

        it('does not have fromModel method without SimpleDTOEloquentTrait', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'fromModel'))->toBeFalse();
        });

        it('does not have toModel method without SimpleDTOEloquentTrait', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'toModel'))->toBeFalse();
        });
    });

    describe('SimpleDTOEloquentTrait (Laravel/Eloquent)', function(): void {
        it('requires Illuminate\Database\Eloquent\Model to be available', function(): void {
            if (!class_exists('Illuminate\Database\Eloquent\Model')) {
                $this->markTestSkipped('Laravel Eloquent not available');
            }
            expect(class_exists('Illuminate\Database\Eloquent\Model'))->toBeTrue();
        });

        it('has fromModel method when using SimpleDTOEloquentTrait', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'fromModel'))->toBeTrue();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($dto, 'fromModel'))->toBeTrue();
        });

        it('has toModel method when using SimpleDTOEloquentTrait', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'toModel'))->toBeTrue();
        });

        it('fromModel is a static method', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $reflection = new ReflectionMethod($dto, 'fromModel');

            expect($reflection->isStatic())->toBeTrue();
        });

        it('toModel is an instance method', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $reflection = new ReflectionMethod($dto, 'toModel');

            expect($reflection->isStatic())->toBeFalse();
        });

        it('fromModel requires Illuminate\Database\Eloquent\Model parameter', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $reflection = new ReflectionMethod($dto, 'fromModel');
            $parameters = $reflection->getParameters();

            $paramType = $parameters[0]->getType();
            assert($paramType instanceof ReflectionNamedType);

            expect($parameters)->toHaveCount(1);
            expect($parameters[0]->getName())->toBe('model');
            expect($paramType->getName())->toBe('Illuminate\Database\Eloquent\Model');
        });

        it('toModel returns Illuminate\Database\Eloquent\Model', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $reflection = new ReflectionMethod($dto, 'toModel');
            $returnType = $reflection->getReturnType();
            assert($returnType instanceof ReflectionNamedType);

            expect($returnType->getName())->toBe('Illuminate\Database\Eloquent\Model');
        });
    });

    describe('SimpleDTOEloquentCast (Laravel/Eloquent)', function(): void {
        it('requires Illuminate\Contracts\Database\Eloquent\CastsAttributes', function(): void {
            if (!interface_exists('Illuminate\Contracts\Database\Eloquent\CastsAttributes')) {
                $this->markTestSkipped('Laravel Eloquent not available');
            }
            expect(interface_exists('Illuminate\Contracts\Database\Eloquent\CastsAttributes'))->toBeTrue();
        });

        it('SimpleDTOEloquentCast class exists', function(): void {
            if (!interface_exists('Illuminate\Contracts\Database\Eloquent\CastsAttributes')) {
                $this->markTestSkipped('Laravel Eloquent not available');
            }
            expect(class_exists('event4u\DataHelpers\SimpleDTO\SimpleDTOEloquentCast'))->toBeTrue();
        });

        it('has get, set, and serialize methods', function(): void {
            if (!interface_exists('Illuminate\Contracts\Database\Eloquent\CastsAttributes')) {
                $this->markTestSkipped('Laravel Eloquent not available');
            }
            $reflection = new ReflectionClass('event4u\DataHelpers\SimpleDTO\SimpleDTOEloquentCast');

            expect($reflection->hasMethod('get'))->toBeTrue();
            expect($reflection->hasMethod('set'))->toBeTrue();
            expect($reflection->hasMethod('serialize'))->toBeTrue();
        });
    });

    describe('Trait Composition', function(): void {
        it('can use SimpleDTOTrait without SimpleDTOEloquentTrait', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                ) {}
            };

            $instance = $dto::fromArray([]);

            // Core functionality works
            expect($instance->toArray())->toBe(['name' => 'Test']);
            expect(json_encode($instance))->toBeJson();

            // Eloquent methods not available
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'fromModel'))->toBeFalse();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'toModel'))->toBeFalse();
        });

        it('can use SimpleDTOTrait with SimpleDTOEloquentTrait', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = 'Test',
                ) {}
            };

            $instance = $dto::fromArray([]);

            // Core functionality works
            expect($instance->toArray())->toBe(['name' => 'Test']);
            expect(json_encode($instance))->toBeJson();

            // Eloquent methods available
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'fromModel'))->toBeTrue();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'toModel'))->toBeTrue();
        });

        it('SimpleDTOEloquentTrait does not interfere with core functionality', function(): void {
            $dtoWithoutEloquent = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                    public readonly int $age = 30,
                ) {}
            };

            $dtoWithEloquent = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = 'Test',
                    public readonly int $age = 30,
                ) {}
            };

            $instance1 = $dtoWithoutEloquent::fromArray([]);
            $instance2 = $dtoWithEloquent::fromArray([]);

            // Both should produce same output
            expect($instance1->toArray())->toBe($instance2->toArray());
            expect(json_encode($instance1))->toBe(json_encode($instance2));
        });
    });

    describe('Error Handling', function(): void {
        it('SimpleDTOEloquentTrait cannot be used without Eloquent Model', function(): void {
            // This test verifies that the trait requires Eloquent Model
            // If Eloquent is not available, the trait will cause a fatal error
            // In our test environment, Eloquent IS available, so we just verify the type hints

            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = 'Test',
                ) {}
            };

            $reflection = new ReflectionMethod($dto, 'fromModel');
            $parameters = $reflection->getParameters();
            $paramType = $parameters[0]->getType();
            assert($paramType instanceof ReflectionNamedType);

            // Verify that the parameter type is Eloquent Model
            expect($paramType->getName())->toBe('Illuminate\Database\Eloquent\Model');
            expect($paramType->allowsNull())->toBeFalse();
        });
    });

    describe('SimpleDTODoctrineTrait (Doctrine/Symfony)', function(): void {
        it('has fromEntity method when using SimpleDTODoctrineTrait', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'fromEntity'))->toBeTrue();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($dto, 'fromEntity'))->toBeTrue();
        });

        it('has toEntity method when using SimpleDTODoctrineTrait', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'toEntity'))->toBeTrue();
        });

        it('fromEntity is a static method', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $reflection = new ReflectionMethod($dto, 'fromEntity');

            expect($reflection->isStatic())->toBeTrue();
        });

        it('toEntity is an instance method', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $reflection = new ReflectionMethod($dto, 'toEntity');

            expect($reflection->isStatic())->toBeFalse();
        });

        it('fromEntity requires object parameter', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $reflection = new ReflectionMethod($dto, 'fromEntity');
            $parameters = $reflection->getParameters();

            expect($parameters)->toHaveCount(1);
            expect($parameters[0]->getName())->toBe('entity');

            $paramType = $parameters[0]->getType();
            assert($paramType instanceof ReflectionNamedType);
            expect($paramType->getName())->toBe('object');
        });

        it('toEntity returns object', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $reflection = new ReflectionMethod($dto, 'toEntity');
            $returnType = $reflection->getReturnType();

            assert($returnType instanceof ReflectionNamedType);
            expect($returnType->getName())->toBe('object');
        });

        it('does not have fromEntity without SimpleDTODoctrineTrait', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'fromEntity'))->toBeFalse();
        });

        it('does not have toEntity without SimpleDTODoctrineTrait', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'toEntity'))->toBeFalse();
        });
    });

    describe('SimpleDTODoctrineType (Doctrine)', function(): void {
        it('SimpleDTODoctrineType class exists', function(): void {
            expect(class_exists('event4u\DataHelpers\SimpleDTO\SimpleDTODoctrineType'))->toBeTrue();
        });

        it('extends Doctrine DBAL Type', function(): void {
            $reflection = new ReflectionClass('event4u\DataHelpers\SimpleDTO\SimpleDTODoctrineType');
            $parentClass = $reflection->getParentClass();

            expect($parentClass)->not->toBeFalse();
            assert($parentClass instanceof ReflectionClass);
            // Parent class can be either the real Doctrine Type or the stub Type
            expect($parentClass->getName())->toBeIn(['Doctrine\DBAL\Types\Type', 'event4u\DataHelpers\SimpleDTO\Type']);
        });

        it('has required methods', function(): void {
            $reflection = new ReflectionClass('event4u\DataHelpers\SimpleDTO\SimpleDTODoctrineType');

            expect($reflection->hasMethod('getSQLDeclaration'))->toBeTrue();
            expect($reflection->hasMethod('convertToPHPValue'))->toBeTrue();
            expect($reflection->hasMethod('convertToDatabaseValue'))->toBeTrue();
            expect($reflection->hasMethod('getName'))->toBeTrue();
        });
    });

    describe('Multiple Framework Traits', function(): void {
        it('can use both SimpleDTOEloquentTrait and SimpleDTODoctrineTrait', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = 'Test',
                ) {}
            };

            $instance = $dto::fromArray([]);

            // Both Eloquent and Doctrine methods available
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'fromModel'))->toBeTrue();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'toModel'))->toBeTrue();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'fromEntity'))->toBeTrue();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'toEntity'))->toBeTrue();

            // Core functionality still works
            expect($instance->toArray())->toBe(['name' => 'Test']);
        });
    });
});

