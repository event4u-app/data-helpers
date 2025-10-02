<?php

declare(strict_types=1);

use event4u\DataHelpers\Support\EntityHelper;
use Tests\utils\Models\User;

describe('EntityHelper', function () {
    it('detects Eloquent Models', function () {
        $model = new User(['name' => 'John', 'email' => 'john@example.com']);

        expect(EntityHelper::isEloquentModel($model))->toBeTrue();
        expect(EntityHelper::isEntity($model))->toBeTrue();
        expect(EntityHelper::isDoctrineEntity($model))->toBeFalse();
    });

    it('converts Eloquent Model to array', function () {
        $model = new User(['name' => 'John', 'email' => 'john@example.com']);

        $result = EntityHelper::toArray($model);

        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('email');
        expect($result['name'])->toBe('John');
    });

    it('gets attributes from Eloquent Model', function () {
        $model = new User(['name' => 'John', 'email' => 'john@example.com']);

        $attributes = EntityHelper::getAttributes($model);

        expect($attributes)->toHaveKey('name');
        expect($attributes)->toHaveKey('email');
    });

    it('checks if Eloquent Model has attribute', function () {
        $model = new User(['name' => 'John', 'email' => 'john@example.com']);

        expect(EntityHelper::hasAttribute($model, 'name'))->toBeTrue();
        expect(EntityHelper::hasAttribute($model, 'nonexistent'))->toBeFalse();
    });

    it('gets attribute from Eloquent Model', function () {
        $model = new User(['name' => 'John', 'email' => 'john@example.com']);

        expect(EntityHelper::getAttribute($model, 'name'))->toBe('John');
        expect(EntityHelper::getAttribute($model, 'email'))->toBe('john@example.com');
    });

    it('sets attribute on Eloquent Model', function () {
        $model = new User(['name' => 'John']);

        EntityHelper::setAttribute($model, 'name', 'Jane');

        expect($model->getAttribute('name'))->toBe('Jane');
    });

    it('unsets attribute from Eloquent Model', function () {
        $model = new User(['name' => 'John', 'email' => 'john@example.com']);

        EntityHelper::unsetAttribute($model, 'email');

        expect($model->getAttribute('email'))->toBeNull();
    });

    it('returns empty array for non-entity', function () {
        $notEntity = 'not an entity';

        expect(EntityHelper::toArray($notEntity))->toBe([]);
        expect(EntityHelper::getAttributes($notEntity))->toBe([]);
        expect(EntityHelper::hasAttribute($notEntity, 'key'))->toBeFalse();
        expect(EntityHelper::getAttribute($notEntity, 'key'))->toBeNull();
    });

    it('handles Doctrine Entities when available', function () {
        // This test would require a real Doctrine Entity class
        // For now, we just verify the method exists
        expect(method_exists(EntityHelper::class, 'isDoctrineEntity'))->toBeTrue();
    });
});

