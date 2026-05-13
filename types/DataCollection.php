<?php

declare(strict_types=1);

/** @noinspection PhpExpressionResultUnusedInspection */

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\DtoCollection;
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
$products = DtoCollection::forDto(ProductDto::class, [
    ['id' => 1, 'name' => 'Laptop', 'price' => 999.99],
    ['id' => 2, 'name' => 'Mouse', 'price' => 29.99],
]);
assertType('event4u\DataHelpers\SimpleDto\DtoCollection<event4u\DataHelpers\SimpleDto>', $products);

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
assertType('event4u\DataHelpers\SimpleDto\DtoCollection<event4u\DataHelpers\SimpleDto>', $filtered);

// Test map - returns DtoCollection with transformed DTOs
$transformed = $products->map(
    /** @param SimpleDto $p */
    fn($p): SimpleDto => $p instanceof ProductDto ? ProductDto::fromArray([
        'id' => $p->id,
        'name' => strtoupper($p->name),
        'price' => $p->price,
    ]) : $p
);
assertType('event4u\DataHelpers\SimpleDto\DtoCollection<event4u\DataHelpers\SimpleDto>', $transformed);

// Test toArray
$array = $products->toArray();
assertType('array<int|string, array<string, mixed>>', $array);

// Test jsonSerialize
$json = $products->jsonSerialize();
assertType('array<int|string, array<string, mixed>>', $json);

// Test ArrayAccess
assertType('event4u\DataHelpers\SimpleDto|null', $products[0]);
assertType('bool', isset($products[0]));

// Test Iterator
foreach ($products as $product) {
    assertType('event4u\DataHelpers\SimpleDto', $product);
}
