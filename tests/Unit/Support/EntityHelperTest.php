<?php

declare(strict_types=1);

use event4u\DataHelpers\Support\EntityHelper;
use Tests\utils\Entities\Product;
use Tests\utils\Models\User;

describe('EntityHelper', function(): void {
    it('detects Eloquent Models', function(): void {
        $model = new User([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        expect(EntityHelper::isEloquentModel($model))->toBeTrue();
        expect(EntityHelper::isEntity($model))->toBeTrue();
        expect(EntityHelper::isDoctrineEntity($model))->toBeFalse();
    });

    it('converts Eloquent Model to array', function(): void {
        $model = new User([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        $result = EntityHelper::toArray($model);

        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('email');
        expect($result['name'])->toBe('John');
    });

    it('gets attributes from Eloquent Model', function(): void {
        $model = new User([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        $attributes = EntityHelper::getAttributes($model);

        expect($attributes)->toHaveKey('name');
        expect($attributes)->toHaveKey('email');
    });

    it('checks if Eloquent Model has attribute', function(): void {
        $model = new User([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        expect(EntityHelper::hasAttribute($model, 'name'))->toBeTrue();
        expect(EntityHelper::hasAttribute($model, 'nonexistent'))->toBeFalse();
    });

    it('gets attribute from Eloquent Model', function(): void {
        $model = new User([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        expect(EntityHelper::getAttribute($model, 'name'))->toBe('John');
        expect(EntityHelper::getAttribute($model, 'email'))->toBe('john@example.com');
    });

    it('sets attribute on Eloquent Model', function(): void {
        $model = new User([
            'name' => 'John',
        ]);

        EntityHelper::setAttribute($model, 'name', 'Jane');

        expect($model->getAttribute('name'))->toBe('Jane');
    });

    it('unsets attribute from Eloquent Model', function(): void {
        $model = new User([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        EntityHelper::unsetAttribute($model, 'email');

        expect($model->getAttribute('email'))->toBeNull();
    });

    it('returns empty array for non-entity', function(): void {
        $notEntity = 'not an entity';

        expect(EntityHelper::toArray($notEntity))->toBe([]);
        expect(EntityHelper::getAttributes($notEntity))->toBe([]);
        expect(EntityHelper::hasAttribute($notEntity, 'key'))->toBeFalse();
        expect(EntityHelper::getAttribute($notEntity, 'key'))->toBeNull();
    });

    it('handles Doctrine Entities when available', function(): void {
        // This test would require a real Doctrine Entity class
        // For now, we just verify the method exists
        expect(EntityHelper::class)->toHaveMethod('isDoctrineEntity');
    });

    it('detects Doctrine Entities', function(): void {
        $entity = new Product('Laptop', '999.99');

        expect(EntityHelper::isDoctrineEntity($entity))->toBeTrue();
        expect(EntityHelper::isEntity($entity))->toBeTrue();
        expect(EntityHelper::isEloquentModel($entity))->toBeFalse();
    });

    it('converts Doctrine Entity to array', function(): void {
        $entity = new Product('Laptop', '999.99');
        $entity->setDescription('A powerful laptop');

        $result = EntityHelper::toArray($entity);

        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('price');
        expect($result)->toHaveKey('description');
        expect($result['name'])->toBe('Laptop');
        expect($result['price'])->toBe('999.99');
    });

    it('gets attributes from Doctrine Entity', function(): void {
        $entity = new Product('Laptop', '999.99');

        $attributes = EntityHelper::getAttributes($entity);

        expect($attributes)->toHaveKey('name');
        expect($attributes)->toHaveKey('price');
        expect($attributes['name'])->toBe('Laptop');
    });

    it('checks if Doctrine Entity has attribute', function(): void {
        $entity = new Product('Laptop', '999.99');

        expect(EntityHelper::hasAttribute($entity, 'name'))->toBeTrue();
        expect(EntityHelper::hasAttribute($entity, 'price'))->toBeTrue();
        expect(EntityHelper::hasAttribute($entity, 'nonexistent'))->toBeFalse();
    });

    it('gets attribute from Doctrine Entity', function(): void {
        $entity = new Product('Laptop', '999.99');

        expect(EntityHelper::getAttribute($entity, 'name'))->toBe('Laptop');
        expect(EntityHelper::getAttribute($entity, 'price'))->toBe('999.99');
        expect(EntityHelper::getAttribute($entity, 'nonexistent'))->toBeNull();
    });

    it('sets attribute on Doctrine Entity', function(): void {
        $entity = new Product('Laptop', '999.99');

        EntityHelper::setAttribute($entity, 'name', 'Desktop');
        EntityHelper::setAttribute($entity, 'price', '1299.99');

        expect($entity->getName())->toBe('Desktop');
        expect($entity->getPrice())->toBe('1299.99');
    });

    it('unsets attribute from Doctrine Entity', function(): void {
        $entity = new Product('Laptop', '999.99');
        $entity->setDescription('A powerful laptop');

        EntityHelper::unsetAttribute($entity, 'description');

        expect($entity->getDescription())->toBeNull();
    });
});
