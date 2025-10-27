<?php

/** @noinspection PhpExpressionResultUnusedInspection */

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\DataCollection;
use function PHPStan\Testing\assertType;

// Test Dto class
class ProductDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
        public readonly bool $active = true,
    ) {}
}

// Test forDto factory method
$products = DataCollection::forDto(ProductDto::class, [
    ['id' => 1, 'name' => 'Laptop', 'price' => 999.99],
    ['id' => 2, 'name' => 'Mouse', 'price' => 29.99],
]);
assertType('event4u\DataHelpers\SimpleDto\DataCollection<event4u\DataHelpers\SimpleDto>', $products);

// Test count
assertType('int<0, max>', $products->count());

// Test isEmpty
assertType('bool', $products->isEmpty());

// Test isNotEmpty
assertType('bool', $products->isNotEmpty());

// Test first
$first = $products->first();
assertType('event4u\DataHelpers\SimpleDto|null', $first);

// Test last
$last = $products->last();
assertType('event4u\DataHelpers\SimpleDto|null', $last);

// Test filter - PHPStan doesn't narrow the generic type
$filtered = $products->filter(fn($p): bool => $p instanceof ProductDto && 100 < $p->price);
assertType('event4u\DataHelpers\SimpleDto\DataCollection<event4u\DataHelpers\SimpleDto>', $filtered);

// Test map - returns array, not collection
$names = $products->map(fn($p): string => $p instanceof ProductDto ? $p->name : '');
assertType('array<int, string>', $names);

// Test toArray
$array = $products->toArray();
assertType('array<int, array<string, mixed>>', $array);

// Test jsonSerialize
$json = $products->jsonSerialize();
assertType('array<int, array<string, mixed>>', $json);

// Test ArrayAccess
assertType('event4u\DataHelpers\SimpleDto|null', $products[0]);
assertType('bool', isset($products[0]));

// Test Iterator
foreach ($products as $product) {
    assertType('event4u\DataHelpers\SimpleDto', $product);
}
