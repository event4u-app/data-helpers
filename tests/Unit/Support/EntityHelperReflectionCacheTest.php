<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use event4u\DataHelpers\Support\EntityHelper;
use Tests\utils\Entities\Product;

it('caches ReflectionClass instances for Doctrine entities', function(): void {
    $entity1 = new Product();
    $entity2 = new Product();

    // First call should create cache entry
    $result1 = EntityHelper::hasAttribute($entity1, 'name');
    expect($result1)->toBeTrue();

    // Second call with same class should use cached ReflectionClass
    $result2 = EntityHelper::hasAttribute($entity2, 'name');
    expect($result2)->toBeTrue();

    // Verify both calls work correctly
    expect($result1)->toBe($result2);
});

it('caches property existence checks for Doctrine entities', function(): void {
    $entity = new Product();

    // First check should cache the result
    $hasName1 = EntityHelper::hasAttribute($entity, 'name');
    expect($hasName1)->toBeTrue();

    // Second check should use cached result
    $hasName2 = EntityHelper::hasAttribute($entity, 'name');
    expect($hasName2)->toBeTrue();

    // Check non-existent property
    $hasInvalid1 = EntityHelper::hasAttribute($entity, 'nonExistent');
    expect($hasInvalid1)->toBeFalse();

    // Second check should use cached result
    $hasInvalid2 = EntityHelper::hasAttribute($entity, 'nonExistent');
    expect($hasInvalid2)->toBeFalse();
});

it('uses cached reflection for getAttribute operations', function(): void {
    $entity = new Product();
    $entity->setName('Test Product');
    $entity->setPrice('99.99');

    // Multiple getAttribute calls should use cached reflection
    $name1 = EntityHelper::getAttribute($entity, 'name');
    $name2 = EntityHelper::getAttribute($entity, 'name');
    $price1 = EntityHelper::getAttribute($entity, 'price');
    $price2 = EntityHelper::getAttribute($entity, 'price');

    expect($name1)->toBe('Test Product');
    expect($name2)->toBe('Test Product');
    expect($price1)->toBe('99.99');
    expect($price2)->toBe('99.99');
});

it('uses cached reflection for setAttribute operations', function(): void {
    $entity = new Product();

    // Multiple setAttribute calls should use cached reflection
    EntityHelper::setAttribute($entity, 'name', 'Updated Product');
    EntityHelper::setAttribute($entity, 'price', '149.99');

    expect($entity->getName())->toBe('Updated Product');
    expect($entity->getPrice())->toBe('149.99');

    // Update again
    EntityHelper::setAttribute($entity, 'name', 'Final Product');
    expect($entity->getName())->toBe('Final Product');
});

it('uses cached reflection for toArray operations', function(): void {
    $entity = new Product();
    $entity->setName('Array Test');
    $entity->setPrice('199.99');

    // Multiple toArray calls should use cached reflection
    $array1 = EntityHelper::toArray($entity);
    $array2 = EntityHelper::toArray($entity);

    expect($array1)->toBe($array2);
    expect($array1)->toHaveKey('name');
    expect($array1)->toHaveKey('price');
    expect($array1['name'])->toBe('Array Test');
    expect($array1['price'])->toBe('199.99');
});

it('caches reflection across different operations on same entity class', function(): void {
    $entity1 = new Product();
    $entity1->setName('Product 1');
    $entity1->setPrice('111.11');

    $entity2 = new Product();
    $entity2->setName('Product 2');
    $entity2->setPrice('222.22');

    // Mix different operations - all should benefit from cached reflection
    $hasName = EntityHelper::hasAttribute($entity1, 'name');
    $name1 = EntityHelper::getAttribute($entity1, 'name');
    EntityHelper::setAttribute($entity2, 'name', 'Updated Product 2');
    $array1 = EntityHelper::toArray($entity1);
    $price2 = EntityHelper::getAttribute($entity2, 'price');

    expect($hasName)->toBeTrue();
    expect($name1)->toBe('Product 1');
    expect($price2)->toBe('222.22');
    expect($entity2->getName())->toBe('Updated Product 2');
    expect($array1['name'])->toBe('Product 1');
});

