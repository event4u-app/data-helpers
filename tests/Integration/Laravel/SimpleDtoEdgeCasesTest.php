<?php

declare(strict_types=1);

namespace Tests\Integration\Laravel;

use Tests\Utils\Models\User;
use Tests\Utils\SimpleDtos\EdgeCases\EmployeeExtendedDto;
use Tests\Utils\SimpleDtos\ProductSimpleDto;
use Tests\Utils\SimpleDtos\ProfileSimpleDto;

describe('Laravel SimpleDto Edge Cases - toModel()', function(): void {
    beforeEach(function(): void {
        // Skip if Laravel is not available
        if (!class_exists('Illuminate\Database\Eloquent\Model')) {
            $this->markTestSkipped('Laravel is not available');
        }
    });

    test('Edge Case B: Nullable properties - explicit null is included', function(): void {
        // DTO with explicitly null bio
        $dto = ProfileSimpleDto::from([
            'name' => 'Alice',
            'bio' => null,
            'website' => 'https://alice.dev',
        ]);

        $model = $dto->toModel();

        expect($model->name)->toBe('Alice') // @phpstan-ignore-line property.notFound
            ->and($model->bio)->toBeNull() // @phpstan-ignore-line property.notFound
            ->and($model->website)->toBe('https://alice.dev'); // @phpstan-ignore-line property.notFound
    });

    test('Edge Case C: Mapped properties work with toModel()', function(): void {
        // DTO with mapped properties
        $dto = ProductSimpleDto::from([
            'external_sku' => 'SKU-001',
            'product_name' => 'Widget',
            'unit_price' => 19.99,
        ]);

        $model = $dto->toModel();

        expect($model->external_sku)->toBe('SKU-001') // @phpstan-ignore-line property.notFound
            ->and($model->product_name)->toBe('Widget') // @phpstan-ignore-line property.notFound
            ->and($model->unit_price)->toBe(19.99); // @phpstan-ignore-line property.notFound
    });

    test('Edge Case F: Inheritance works with toModel()', function(): void {
        // EmployeeExtendedDto extends EmployeeDto (which has name, email)
        // and adds department property
        $dto = EmployeeExtendedDto::from([
            'name' => 'John',
            'email' => 'john@example.com',
            'department' => 'Engineering',
        ]);

        $model = $dto->toModel(User::class);

        expect($model->name)->toBe('John') // @phpstan-ignore-line property.notFound
            ->and($model->email)->toBe('john@example.com') // @phpstan-ignore-line property.notFound
            ->and($model->department)->toBe('Engineering'); // @phpstan-ignore-line property.notFound
    });
})->group('laravel');
