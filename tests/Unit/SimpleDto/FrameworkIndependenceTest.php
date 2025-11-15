<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\SimpleDtoDoctrineTrait;
use event4u\DataHelpers\SimpleDto\SimpleDtoEloquentTrait;

describe('Framework Independence', function(): void {
    describe('SimpleDtoTrait (Core)', function(): void {
        it('works without any framework dependencies', function(): void {
            $dto = new class extends SimpleDto {
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

        it('does not have fromModel method without Laravel/Eloquent', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            // Method does not exist when Laravel is not installed
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'fromModel'))->toBeFalse();
        });

        it('does not have toModel method without Laravel/Eloquent', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            // Method does not exist when Laravel is not installed
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'toModel'))->toBeFalse();
        });
    });

    describe('SimpleDtoEloquentTrait (Laravel/Eloquent)', function(): void {
        it('requires Illuminate\Database\Eloquent\Model to be available', function(): void {
            if (!class_exists('Illuminate\Database\Eloquent\Model')) {
                $this->markTestSkipped('Laravel Eloquent not available');
            }
            expect(class_exists('Illuminate\Database\Eloquent\Model'))->toBeTrue();
        });

        it('fromModel does NOT exist when Laravel is not installed', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'fromModel'))->toBeFalse();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($dto, 'fromModel'))->toBeFalse();
        });

        it('toModel method does NOT exist when Laravel is not installed', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'toModel'))->toBeFalse();
        });

        it('fromModel does NOT exist when Laravel is not installed', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($dto, 'fromModel'))->toBeFalse();
        });

        it('SimpleDtoEloquentTrait is empty when Laravel is not installed', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            // Trait should be empty - no methods should exist
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($dto, 'fromModel'))->toBeFalse();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($dto, 'toModel'))->toBeFalse();
        });
    });

    describe('SimpleDtoEloquentCast (Laravel/Eloquent)', function(): void {
        it('requires Illuminate\Contracts\Database\Eloquent\CastsAttributes', function(): void {
            if (!interface_exists('Illuminate\Contracts\Database\Eloquent\CastsAttributes')) {
                $this->markTestSkipped('Laravel Eloquent not available');
            }
            expect(interface_exists('Illuminate\Contracts\Database\Eloquent\CastsAttributes'))->toBeTrue();
        });

        it('SimpleDtoEloquentCast class exists', function(): void {
            if (!interface_exists('Illuminate\Contracts\Database\Eloquent\CastsAttributes')) {
                $this->markTestSkipped('Laravel Eloquent not available');
            }
            expect(class_exists('event4u\DataHelpers\SimpleDto\SimpleDtoEloquentCast'))->toBeTrue();
        });

        it('has get, set and serialize methods', function(): void {
            if (!interface_exists('Illuminate\Contracts\Database\Eloquent\CastsAttributes')) {
                $this->markTestSkipped('Laravel Eloquent not available');
            }
            $reflection = new ReflectionClass('event4u\DataHelpers\SimpleDto\SimpleDtoEloquentCast');

            expect($reflection->hasMethod('get'))->toBeTrue();
            expect($reflection->hasMethod('set'))->toBeTrue();
            expect($reflection->hasMethod('serialize'))->toBeTrue();
        });
    });

    describe('Trait Composition', function(): void {
        it('can use SimpleDtoTrait without SimpleDtoEloquentTrait', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'Test',
                ) {}
            };

            $instance = $dto::fromArray([]);

            // Core functionality works
            expect($instance->toArray())->toBe(['name' => 'Test']);
            expect(json_encode($instance))->toBeJson();

            // Eloquent methods do not exist when Laravel is not installed
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'fromModel'))->toBeFalse();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'toModel'))->toBeFalse();
        });

        it('can use SimpleDtoTrait with SimpleDtoEloquentTrait', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = 'Test',
                ) {}
            };

            $instance = $dto::fromArray([]);

            // Core functionality works
            expect($instance->toArray())->toBe(['name' => 'Test']);
            expect(json_encode($instance))->toBeJson();

            // Eloquent methods do NOT exist when Laravel is not installed
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'fromModel'))->toBeFalse();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'toModel'))->toBeFalse();
        });

        it('SimpleDtoEloquentTrait does not interfere with core functionality', function(): void {
            $dtoWithoutEloquent = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'Test',
                    public readonly int $age = 30,
                ) {}
            };

            $dtoWithEloquent = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

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
        it('SimpleDtoEloquentTrait is empty when Laravel is not installed', function(): void {
            // When Laravel is not installed, the trait is empty and has no methods

            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = 'Test',
                ) {}
            };

            // Verify that no Eloquent methods exist
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($dto, 'fromModel'))->toBeFalse();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($dto, 'toModel'))->toBeFalse();
        });
    });

    describe('SimpleDtoDoctrineTrait (Doctrine/Symfony)', function(): void {
        it('fromEntity does NOT exist when Doctrine is not installed', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoDoctrineTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'fromEntity'))->toBeFalse();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($dto, 'fromEntity'))->toBeFalse();
        });

        it('toEntity does NOT exist when Doctrine is not installed', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoDoctrineTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'toEntity'))->toBeFalse();
        });

        it('SimpleDtoDoctrineTrait is empty when Doctrine is not installed', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoDoctrineTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            // Trait should be empty - no methods should exist
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($dto, 'fromEntity'))->toBeFalse();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($dto, 'toEntity'))->toBeFalse();
        });

        it('does not have fromEntity without SimpleDtoDoctrineTrait', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'fromEntity'))->toBeFalse();
        });

        it('does not have toEntity without SimpleDtoDoctrineTrait', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'toEntity'))->toBeFalse();
        });
    });

    describe('SimpleDtoDoctrineType (Doctrine)', function(): void {
        it('SimpleDtoDoctrineType class exists', function(): void {
            expect(class_exists('event4u\DataHelpers\SimpleDto\SimpleDtoDoctrineType'))->toBeTrue();
        });

        it('extends Doctrine DBAL Type', function(): void {
            $reflection = new ReflectionClass('event4u\DataHelpers\SimpleDto\SimpleDtoDoctrineType');
            $parentClass = $reflection->getParentClass();

            expect($parentClass)->not->toBeFalse();
            assert($parentClass instanceof ReflectionClass);
            // Parent class can be either the real Doctrine Type or the stub Type
            expect($parentClass->getName())->toBeIn(['Doctrine\DBAL\Types\Type', 'event4u\DataHelpers\SimpleDto\Type']);
        });

        it('has required methods', function(): void {
            $reflection = new ReflectionClass('event4u\DataHelpers\SimpleDto\SimpleDtoDoctrineType');

            expect($reflection->hasMethod('getSQLDeclaration'))->toBeTrue();
            expect($reflection->hasMethod('convertToPHPValue'))->toBeTrue();
            expect($reflection->hasMethod('convertToDatabaseValue'))->toBeTrue();
            expect($reflection->hasMethod('getName'))->toBeTrue();
        });
    });

    describe('Multiple Framework Traits', function(): void {
        it('can use both SimpleDtoEloquentTrait and SimpleDtoDoctrineTrait (both empty when frameworks not installed)', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;
                use SimpleDtoDoctrineTrait;

                public function __construct(
                    public readonly string $name = 'Test',
                ) {}
            };

            $instance = $dto::fromArray([]);

            // Both Eloquent and Doctrine methods do NOT exist when frameworks are not installed
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'fromModel'))->toBeFalse();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'toModel'))->toBeFalse();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'fromEntity'))->toBeFalse();
            /** @phpstan-ignore-next-line unknown */
            expect(method_exists($instance, 'toEntity'))->toBeFalse();

            // Core functionality still works
            expect($instance->toArray())->toBe(['name' => 'Test']);
        });
    });
});
