<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\LiteDto;

describe('LiteDto Framework Independence', function(): void {
    describe('Laravel/Eloquent Integration', function(): void {
        it('does not have fromModel method without Laravel/Eloquent', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::from(['name' => 'Test']);

            // Method does not exist when Laravel is not installed
            expect(method_exists($instance, 'fromModel'))->toBeFalse();
        });

        it('does not have toModel method without Laravel/Eloquent', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::from(['name' => 'Test']);

            // Method does not exist when Laravel is not installed
            expect(method_exists($instance, 'toModel'))->toBeFalse();
        });
    });

    describe('Doctrine Integration', function(): void {
        it('does not have fromEntity method without Doctrine', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::from(['name' => 'Test']);

            // Method does not exist when Doctrine is not installed
            expect(method_exists($instance, 'fromEntity'))->toBeFalse();
        });

        it('does not have toEntity method without Doctrine', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::from(['name' => 'Test']);

            // Method does not exist when Doctrine is not installed
            expect(method_exists($instance, 'toEntity'))->toBeFalse();
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
            expect($object->name)->toBe('Jane');
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

            // Framework-specific methods do not exist when frameworks are not installed
            expect(method_exists($instance, 'fromModel'))->toBeFalse();
            expect(method_exists($instance, 'toModel'))->toBeFalse();
            expect(method_exists($instance, 'fromEntity'))->toBeFalse();
            expect(method_exists($instance, 'toEntity'))->toBeFalse();

            // Object methods are always available
            expect(method_exists($instance, 'fromObject'))->toBeTrue();
            expect(method_exists($instance, 'toObject'))->toBeTrue();
        });
    });
});
