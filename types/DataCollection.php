<?php

/** @noinspection PhpExpressionResultUnusedInspection */

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\DataCollection;
use function PHPStan\Testing\assertType;

// Test DTO class
class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
        public readonly bool $active = true,
    ) {}
}

// Test forDto factory method
$products = DataCollection::forDto(ProductDTO::class, [
    ['id' => 1, 'name' => 'Laptop', 'price' => 999.99],
    ['id' => 2, 'name' => 'Mouse', 'price' => 29.99],
]);
assertType('event4u\DataHelpers\SimpleDTO\DataCollection<event4u\DataHelpers\SimpleDTO>', $products);

// Test count
assertType('int<0, max>', $products->count());

// Test isEmpty
assertType('bool', $products->isEmpty());

// Test isNotEmpty
assertType('bool', $products->isNotEmpty());

// Test first
$first = $products->first();
assertType('event4u\DataHelpers\SimpleDTO|null', $first);

// Test last
$last = $products->last();
assertType('event4u\DataHelpers\SimpleDTO|null', $last);

// Test filter - PHPStan doesn't narrow the generic type
$filtered = $products->filter(fn($p): bool => $p instanceof ProductDTO && 100 < $p->price);
assertType('event4u\DataHelpers\SimpleDTO\DataCollection<event4u\DataHelpers\SimpleDTO>', $filtered);

// Test map - returns array, not collection
$names = $products->map(fn($p): string => $p instanceof ProductDTO ? $p->name : '');
assertType('array<int, string>', $names);

// Test toArray
$array = $products->toArray();
assertType('array<int, array<string, mixed>>', $array);

// Test jsonSerialize
$json = $products->jsonSerialize();
assertType('array<int, array<string, mixed>>', $json);

// Test ArrayAccess
assertType('event4u\DataHelpers\SimpleDTO|null', $products[0]);
assertType('bool', isset($products[0]));

// Test Iterator
foreach ($products as $product) {
    assertType('event4u\DataHelpers\SimpleDTO', $product);
}
