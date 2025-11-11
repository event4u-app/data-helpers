<?php

declare(strict_types=1);

namespace Tests\Unit\TransformAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HideWhenNull;

class HideWhenNullTestDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        #[HideWhenNull]
        public readonly ?string $middleName = null,
        #[HideWhenNull]
        public readonly ?string $nickname = null,
        public readonly ?string $email = null,
    ) {}
}

describe('HideWhenNull Attribute', function(): void {
    it('hides null values in toArray', function(): void {
        $dto = HideWhenNullTestDto::from([
            'name' => 'John',
            'middleName' => null,
            'nickname' => 'Johnny',
            'email' => null,
        ]);

        $array = $dto->toArray();

        expect($array)->toHaveKey('name');
        expect($array)->not->toHaveKey('middleName'); // Hidden because null
        expect($array)->toHaveKey('nickname'); // Not hidden because not null
        expect($array)->toHaveKey('email'); // Not hidden because no HideWhenNull attribute
    });

    it('shows non-null values in toArray', function(): void {
        $dto = HideWhenNullTestDto::from([
            'name' => 'John',
            'middleName' => 'Paul',
            'nickname' => 'Johnny',
            'email' => 'john@example.com',
        ]);

        $array = $dto->toArray();

        expect($array)->toHaveKey('name');
        expect($array)->toHaveKey('middleName'); // Shown because not null
        expect($array)->toHaveKey('nickname');
        expect($array)->toHaveKey('email');
        expect($array['middleName'])->toBe('Paul');
    });

    it('hides null values in toJson', function(): void {
        $dto = HideWhenNullTestDto::from([
            'name' => 'John',
            'middleName' => null,
            'nickname' => 'Johnny',
            'email' => null,
        ]);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->toHaveKey('name');
        expect($decoded)->not->toHaveKey('middleName'); // Hidden because null
        expect($decoded)->toHaveKey('nickname'); // Not hidden because not null
        expect($decoded)->toHaveKey('email'); // Not hidden because no HideWhenNull attribute
    });

    it('works with all null values', function(): void {
        $dto = HideWhenNullTestDto::from([
            'name' => 'John',
            'middleName' => null,
            'nickname' => null,
            'email' => null,
        ]);

        $array = $dto->toArray();

        expect($array)->toHaveKey('name');
        expect($array)->not->toHaveKey('middleName');
        expect($array)->not->toHaveKey('nickname');
        expect($array)->toHaveKey('email');
        expect(count($array))->toBe(2); // Only name and email
    });

    it('works with all non-null values', function(): void {
        $dto = HideWhenNullTestDto::from([
            'name' => 'John',
            'middleName' => 'Paul',
            'nickname' => 'Johnny',
            'email' => 'john@example.com',
        ]);

        $array = $dto->toArray();

        expect($array)->toHaveKey('name');
        expect($array)->toHaveKey('middleName');
        expect($array)->toHaveKey('nickname');
        expect($array)->toHaveKey('email');
        expect(count($array))->toBe(4);
    });
});
