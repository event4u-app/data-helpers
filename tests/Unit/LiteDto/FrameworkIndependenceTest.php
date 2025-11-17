<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\LiteDto;

describe('LiteDto Framework Independence', function(): void {
    describe('Laravel/Eloquent Integration', function(): void {
        it('throws BadMethodCallException when Laravel is not installed', function(): void {
            if (class_exists('Illuminate\Database\Eloquent\Model')) {
                $this->markTestSkipped('Laravel Eloquent is installed - skipping test');
            }

            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::from(['name' => 'Test']);

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

    describe('Doctrine Integration', function(): void {
        it('throws BadMethodCallException when Doctrine is not installed', function(): void {
            if (interface_exists('Doctrine\ORM\EntityManagerInterface')) {
                $this->markTestSkipped('Doctrine is installed - skipping test');
            }

            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::from(['name' => 'Test']);

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

    describe('Plain Object Integration', function(): void {
        it('has fromObject method (always available)', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::from(['name' => 'Test']);

            // Method exists because LiteDtoObjectTrait is always available
            /** @phpstan-ignore-next-line function.alreadyNarrowedType */
            expect(method_exists($instance, 'fromObject'))->toBeTrue();
        });

        it('has toObject method (always available)', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::from(['name' => 'Test']);

            // Method exists because LiteDtoObjectTrait is always available
            /** @phpstan-ignore-next-line function.alreadyNarrowedType */
            expect(method_exists($instance, 'toObject'))->toBeTrue();
        });

        it('can convert from plain object', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = 'John Doe',
                    public readonly int $age = 30,
                ) {}
            };

            $object = (object)['name' => 'Jane', 'age' => 25];
            $instance = $dto::fromObject($object);

            expect($instance->name)->toBe('Jane');
            expect($instance->age)->toBe(25);
        });

        it('can convert to plain object', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = 'John Doe',
                    public readonly int $age = 30,
                ) {}
            };

            $instance = $dto::from(['name' => 'Jane', 'age' => 25]);
            $object = $instance->toObject();

            expect($object)->toBeObject();
            /** @phpstan-ignore-next-line property.notFound */
            expect($object->name)->toBe('Jane');
            /** @phpstan-ignore-next-line property.notFound */
            expect($object->age)->toBe(25);
        });
    });

    describe('Trait Composition', function(): void {
        it('can use LiteDto without framework-specific traits', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = 'Test',
                ) {}
            };

            $instance = $dto::from(['name' => 'Test']);

            // Core functionality works
            expect($instance->toArray())->toBe(['name' => 'Test']);
            expect(json_encode($instance))->toBeJson();

            // Framework-specific methods exist (from traits)
            /** @phpstan-ignore-next-line function.alreadyNarrowedType */
            expect(method_exists($instance, 'fromModel'))->toBeTrue();
            /** @phpstan-ignore-next-line function.alreadyNarrowedType */
            expect(method_exists($instance, 'toModel'))->toBeTrue();
            /** @phpstan-ignore-next-line function.alreadyNarrowedType */
            expect(method_exists($instance, 'fromEntity'))->toBeTrue();
            /** @phpstan-ignore-next-line function.alreadyNarrowedType */
            expect(method_exists($instance, 'toEntity'))->toBeTrue();

            // Object methods are always available
            /** @phpstan-ignore-next-line function.alreadyNarrowedType */
            expect(method_exists($instance, 'fromObject'))->toBeTrue();
            /** @phpstan-ignore-next-line function.alreadyNarrowedType */
            expect(method_exists($instance, 'toObject'))->toBeTrue();
        });
    });
});
