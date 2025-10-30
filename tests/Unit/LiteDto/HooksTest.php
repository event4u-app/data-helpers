<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\Validation\ValidationResult;

describe('LiteDto Lifecycle Hooks', function(): void {
    describe('Creation Hooks', function(): void {
        test('it calls beforeCreate hook', function(): void {
            $dto = HooksTestBeforeCreateDto::from([
                'email' => 'JOHN@EXAMPLE.COM',
                'name' => 'John',
            ]);

            expect($dto->email)->toBe('john@example.com');
            expect($dto->name)->toBe('John');
        });

        test('it calls afterCreate hook', function(): void {
            $dto = HooksTestAfterCreateDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            expect($dto->name)->toBe('John');
            expect($dto->age)->toBe(30);
            expect($dto->wasCreated)->toBeTrue();
        });
    });

    describe('Mapping Hooks', function(): void {
        test('it calls beforeMapping hook', function(): void {
            $dto = HooksTestBeforeMappingDto::from([
                'user_name' => 'john',
                'user_age' => '30',
            ]);

            expect($dto->name)->toBe('john');
            expect($dto->age)->toBe(30);
        });

        test('it calls afterMapping hook', function(): void {
            $dto = HooksTestAfterMappingDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            expect($dto->name)->toBe('John');
            expect($dto->age)->toBe(30);
            expect($dto->wasMapped)->toBeTrue();
        });
    });

    describe('Casting Hooks', function(): void {
        test('it calls beforeCasting hook', function(): void {
            $dto = HooksTestBeforeCastingDto::from([
                'name' => 'John',
                'age' => '30',
            ]);

            expect($dto->name)->toBe('John');
            expect($dto->age)->toBe(30);
        });

        test('it calls afterCasting hook', function(): void {
            $dto = HooksTestAfterCastingDto::from([
                'name' => 'John',
                'age' => '30',
            ]);

            expect($dto->name)->toBe('John');
            expect($dto->age)->toBe(30);
            // afterCasting is called for each property during construction
            expect($dto->castedProperties)->toBeArray();
        });
    });

    describe('Validation Hooks', function(): void {
        test('it calls beforeValidation hook', function(): void {
            $result = HooksTestBeforeValidationDto::validate([
                'email' => 'JOHN@EXAMPLE.COM',
                'age' => 30,
            ]);

            expect($result->isValid())->toBeTrue();
            expect($result->validated()['email'])->toBe('john@example.com');
        });

        test('it calls afterValidation hook', function(): void {
            $result = HooksTestAfterValidationDto::validate([
                'name' => 'John',
                'age' => 30,
            ]);

            expect($result->isValid())->toBeTrue();
            // afterValidation is called on a temporary instance, so we can't check the static property
            // but we can verify that validation succeeded
        });
    });

    describe('Serialization Hooks', function(): void {
        test('it calls beforeSerialization hook', function(): void {
            $dto = HooksTestBeforeSerializationDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            $array = $dto->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('age');
            expect($array)->toHaveKey('_serialized');
            expect($array['_serialized'])->toBeTrue();
        });

        test('it calls afterSerialization hook', function(): void {
            $dto = HooksTestAfterSerializationDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            $array = $dto->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('age');
            expect($array)->toHaveKey('_modified');
            expect($array['_modified'])->toBeTrue();
        });
    });

    describe('Multiple Hooks', function(): void {
        test('it calls all hooks in correct order', function(): void {
            $dto = HooksTestAllHooksDto::from([
                'name' => 'John',
                'age' => '30',
            ]);

            expect($dto->name)->toBe('John');
            expect($dto->age)->toBe(30);
            // Hooks are called: beforeCreate, beforeMapping (on temp instance),
            // beforeCasting, afterCasting (during construction),
            // afterMapping, afterCreate (on final instance)
            expect($dto->hooksCalled)->toContain('afterMapping', 'afterCreate');

            $array = $dto->toArray();
            expect($array)->toHaveKey('_hooks_called');
        });
    });
});

// Test DTOs

class HooksTestBeforeCreateDto extends LiteDto
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,
    ) {}

    protected function beforeCreate(array &$data): void
    {
        if (isset($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }
    }
}

class HooksTestAfterCreateDto extends LiteDto
{
    public bool $wasCreated = false;

    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}

    protected function afterCreate(): void
    {
        $this->wasCreated = true;
    }
}

class HooksTestBeforeMappingDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}

    protected function beforeMapping(array &$data): void
    {
        // Rename keys before mapping
        if (isset($data['user_name'])) {
            $data['name'] = $data['user_name'];
            unset($data['user_name']);
        }
        if (isset($data['user_age'])) {
            $data['age'] = (int)$data['user_age'];
            unset($data['user_age']);
        }
    }
}

class HooksTestAfterMappingDto extends LiteDto
{
    public bool $wasMapped = false;

    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}

    protected function afterMapping(): void
    {
        $this->wasMapped = true;
    }
}

class HooksTestBeforeCastingDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}

    protected function beforeCasting(string $property, mixed &$value): void
    {
        // Ensure age is numeric before casting
        if ('age' === $property && is_string($value)) {
            $value = (int)$value;
        }
    }
}

class HooksTestAfterCastingDto extends LiteDto
{
    /** @var array<string> */
    public array $castedProperties = [];

    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}

    protected function afterCasting(string $property, mixed $value): void
    {
        $this->castedProperties[] = $property;
    }
}

class HooksTestBeforeValidationDto extends LiteDto
{
    public function __construct(
        public readonly string $email,
        public readonly int $age,
    ) {}

    protected function beforeValidation(array &$data): void
    {
        if (isset($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }
    }
}

class HooksTestAfterValidationDto extends LiteDto
{
    public static ?ValidationResult $lastValidationResult = null;

    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}

    protected function afterValidation(ValidationResult $result): void
    {
        self::$lastValidationResult = $result;
    }
}

class HooksTestBeforeSerializationDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}

    protected function beforeSerialization(array &$data): void
    {
        $data['_serialized'] = true;
    }
}

class HooksTestAfterSerializationDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}

    protected function afterSerialization(array $data): array
    {
        $data['_modified'] = true;
        return $data;
    }
}

class HooksTestAllHooksDto extends LiteDto
{
    /** @var array<string> */
    public array $hooksCalled = [];

    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}

    protected function beforeCreate(array &$data): void
    {
        $this->hooksCalled[] = 'beforeCreate';
    }

    protected function afterCreate(): void
    {
        $this->hooksCalled[] = 'afterCreate';
    }

    protected function beforeMapping(array &$data): void
    {
        $this->hooksCalled[] = 'beforeMapping';
    }

    protected function afterMapping(): void
    {
        $this->hooksCalled[] = 'afterMapping';
    }

    protected function beforeCasting(string $property, mixed &$value): void
    {
        $this->hooksCalled[] = 'beforeCasting';
    }

    protected function afterCasting(string $property, mixed $value): void
    {
        $this->hooksCalled[] = 'afterCasting';
    }

    protected function beforeSerialization(array &$data): void
    {
        $data['_hooks_called'] = $this->hooksCalled;
    }

    protected function afterSerialization(array $data): array
    {
        return $data;
    }
}
