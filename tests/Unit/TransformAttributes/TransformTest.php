<?php

declare(strict_types=1);

namespace Tests\Unit\TransformAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\CamelCase;
use event4u\DataHelpers\SimpleDto\Attributes\Lcfirst;
use event4u\DataHelpers\SimpleDto\Attributes\Lowercase;
use event4u\DataHelpers\SimpleDto\Attributes\SnakeCase;
use event4u\DataHelpers\SimpleDto\Attributes\Trim;
use event4u\DataHelpers\SimpleDto\Attributes\Ucfirst;
use event4u\DataHelpers\SimpleDto\Attributes\Uppercase;

class TransformTestDto extends SimpleDto
{
    public function __construct(
        #[Lowercase]
        public readonly ?string $email = null,
        #[Uppercase]
        public readonly ?string $sku = null,
        #[Ucfirst]
        public readonly ?string $name = null,
        #[CamelCase]
        public readonly ?string $fieldName = null,
        #[SnakeCase]
        public readonly ?string $columnName = null,
        #[Trim]
        public readonly ?string $description = null,
        #[Lcfirst]
        public readonly ?string $variableName = null,
    ) {}
}

describe('Transform Attributes', function(): void {
    it('transforms lowercase', function(): void {
        $dto = TransformTestDto::from(['email' => 'USER@EXAMPLE.COM']);
        expect($dto->email)->toBe('user@example.com');
    });

    it('transforms uppercase', function(): void {
        $dto = TransformTestDto::from(['sku' => 'abc123']);
        expect($dto->sku)->toBe('ABC123');
    });

    it('transforms ucfirst', function(): void {
        $dto = TransformTestDto::from(['name' => 'john doe']);
        expect($dto->name)->toBe('John doe');
    });

    it('handles null values', function(): void {
        $dto = TransformTestDto::from(['email' => null, 'sku' => null, 'name' => null]);
        expect($dto->email)->toBeNull();
        expect($dto->sku)->toBeNull();
        expect($dto->name)->toBeNull();
    });

    it('handles empty strings', function(): void {
        $dto = TransformTestDto::from(['email' => '', 'sku' => '', 'name' => '']);
        expect($dto->email)->toBe('');
        expect($dto->sku)->toBe('');
        expect($dto->name)->toBe('');
    });

    it('transforms multiple attributes on same property', function(): void {
        $dto = TransformTestDto::from([
            'email' => 'USER@EXAMPLE.COM',
            'sku' => 'abc-123',
            'name' => 'john doe',
        ]);
        expect($dto->email)->toBe('user@example.com');
        expect($dto->sku)->toBe('ABC-123');
        expect($dto->name)->toBe('John doe');
    });

    it('transforms camelCase', function(): void {
        $dto = TransformTestDto::from(['fieldName' => 'user_name']);
        expect($dto->fieldName)->toBe('userName');

        $dto = TransformTestDto::from(['fieldName' => 'user-name']);
        expect($dto->fieldName)->toBe('userName');

        $dto = TransformTestDto::from(['fieldName' => 'UserName']);
        expect($dto->fieldName)->toBe('userName');
    });

    it('transforms snakeCase', function(): void {
        $dto = TransformTestDto::from(['columnName' => 'userName']);
        expect($dto->columnName)->toBe('user_name');

        $dto = TransformTestDto::from(['columnName' => 'UserName']);
        expect($dto->columnName)->toBe('user_name');

        $dto = TransformTestDto::from(['columnName' => 'user-name']);
        expect($dto->columnName)->toBe('user_name');
    });

    it('transforms trim', function(): void {
        $dto = TransformTestDto::from(['description' => '  hello world  ']);
        expect($dto->description)->toBe('hello world');

        $dto = TransformTestDto::from(['description' => "\t\nhello\n\t"]);
        expect($dto->description)->toBe('hello');
    });

    it('transforms lcfirst', function(): void {
        $dto = TransformTestDto::from(['variableName' => 'UserName']);
        expect($dto->variableName)->toBe('userName');

        $dto = TransformTestDto::from(['variableName' => 'USERNAME']);
        expect($dto->variableName)->toBe('uSERNAME');
    });
});
