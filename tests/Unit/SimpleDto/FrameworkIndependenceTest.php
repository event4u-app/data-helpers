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
    });

    describe('SimpleDtoEloquentTrait (Laravel/Eloquent)', function(): void {
        it('throws BadMethodCallException when Laravel is not installed', function(): void {
            if (class_exists('Illuminate\Database\Eloquent\Model')) {
                $this->markTestSkipped('Laravel Eloquent is installed - skipping test');
            }

            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            // Methods exist but throw BadMethodCallException when framework is not installed
            /** @phpstan-ignore-next-line function.alreadyNarrowedType */
            expect(method_exists($instance, 'fromModel'))->toBeTrue();
            /** @phpstan-ignore-next-line function.alreadyNarrowedType */
            expect(method_exists($instance, 'toModel'))->toBeTrue();

            // fromModel should throw BadMethodCallException
            expect(fn(): object => $dto::fromModel((object)['name' => 'Test']))
                ->toThrow(BadMethodCallException::class, 'Laravel Eloquent is not installed');

            // toModel should throw BadMethodCallException
            /** @phpstan-ignore-next-line argument.type */
            expect(fn(): object => $instance->toModel('SomeModel'))
                ->toThrow(BadMethodCallException::class, 'Laravel Eloquent is not installed');
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

    describe('SimpleDtoDoctrineTrait (Doctrine/Symfony)', function(): void {
        it('throws BadMethodCallException when Doctrine is not installed', function(): void {
            if (interface_exists('Doctrine\ORM\EntityManagerInterface')) {
                $this->markTestSkipped('Doctrine is installed - skipping test');
            }

            $dto = new class extends SimpleDto {
                use SimpleDtoDoctrineTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            // Methods exist but throw BadMethodCallException when framework is not installed
            /** @phpstan-ignore-next-line function.alreadyNarrowedType */
            expect(method_exists($instance, 'fromEntity'))->toBeTrue();
            /** @phpstan-ignore-next-line function.alreadyNarrowedType */
            expect(method_exists($instance, 'toEntity'))->toBeTrue();

            // fromEntity should throw BadMethodCallException
            expect(fn(): object => $dto::fromEntity((object)['name' => 'Test']))
                ->toThrow(BadMethodCallException::class, 'Doctrine ORM is not installed');

            // toEntity should throw BadMethodCallException
            /** @phpstan-ignore-next-line argument.type */
            expect(fn(): object => $instance->toEntity('SomeEntity'))
                ->toThrow(BadMethodCallException::class, 'Doctrine ORM is not installed');
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
        it('can use both SimpleDtoEloquentTrait and SimpleDtoDoctrineTrait', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;
                use SimpleDtoDoctrineTrait;

                public function __construct(
                    public readonly string $name = 'Test',
                ) {}
            };

            $instance = $dto::fromArray([]);

            // Core functionality still works
            expect($instance->toArray())->toBe(['name' => 'Test']);
        });
    });
});
