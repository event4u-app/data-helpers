<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper;

echo '=== Symfony/Doctrine Support Examples ===' . PHP_EOL . PHP_EOL;

// Example 1: Working with Doctrine Collections
echo '1. Doctrine Collections' . PHP_EOL;
echo '------------------------' . PHP_EOL;

$collection = new ArrayCollection([
    'users' => [
        [
            'name' => 'John',
            'email' => 'john@example.com',
        ],
        [
            'name' => 'Jane',
            'email' => 'jane@example.com',
        ],
    ],
]);

$accessor = new DataAccessor($collection);
$names = $accessor->get('users.*.name');

echo 'Names from Doctrine Collection: ' . json_encode($names) . PHP_EOL;

echo PHP_EOL;

// Example 2: Working with Doctrine Entities (Simulated)
echo '2. Doctrine Entities (Simulated)' . PHP_EOL;
echo '---------------------------------' . PHP_EOL;

// Simulated Doctrine Entity
class Product
{
    private ?int $id = null;
    private string $name = '';
    private float $price = 0.0;

    /** @var array<int, string> */
    private array $tags = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    /** @return array<int, string> */
    public function getTags(): array
    {
        return $this->tags;
    }

    /** @param array<int, string> $tags */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }
}

$product = new Product();
$product->setId(1);
$product->setName('Widget');
$product->setPrice(29.99);
$product->setTags(['electronics', 'gadget']);

// DataAccessor works with entities
$accessor = new DataAccessor($product);
echo 'Product name: ' . $accessor->getString('name') . PHP_EOL;
echo 'Product price: ' . $accessor->getString('price') . PHP_EOL;
echo 'Product tags: ' . json_encode($accessor->getArray('tags')) . PHP_EOL;

echo PHP_EOL;

// Example 3: Mapping between structures
echo '3. Mapping with Doctrine Collections' . PHP_EOL;
echo '-------------------------------------' . PHP_EOL;

$sourceData = [
    'products' => [
        [
            'product_name' => 'Widget',
            'product_price' => 29.99,
        ],
        [
            'product_name' => 'Gadget',
            'product_price' => 49.99,
        ],
    ],
];

$mapping = [
    'products.*.product_name' => 'items.*.name',
    'products.*.product_price' => 'items.*.price',
];

$mapper = new DataMapper();
$result = $mapper->map($sourceData, $mapping, []);

echo 'Mapped data:' . PHP_EOL;
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;

echo PHP_EOL;

// Example 4: Framework Detection
echo '4. Framework Detection' . PHP_EOL;
echo '----------------------' . PHP_EOL;

use event4u\DataHelpers\Support\CollectionHelper;

echo 'Laravel Collections available: ' . (CollectionHelper::isLaravelCollection(
    new stdClass()
) ? 'Yes' : 'No') . PHP_EOL;
echo 'Doctrine Collections available: ' . (class_exists(ArrayCollection::class) ? 'Yes' : 'No') . PHP_EOL;
echo 'Doctrine ORM available: ' . (class_exists(EntityManager::class) ? 'Yes' : 'No') . PHP_EOL;

echo PHP_EOL;

// Example 5: Working with mixed data
echo '5. Mixed Data Sources' . PHP_EOL;
echo '---------------------' . PHP_EOL;

$mixedData = [
    'array_data' => [
        'name' => 'Array Item',
    ],
    'object_data' => (object)[
        'name' => 'Object Item',
    ],
    'collection_data' => new ArrayCollection([
        'name' => 'Collection Item',
    ]),
];

$accessor = new DataAccessor($mixedData);
echo 'Array name: ' . $accessor->getString('array_data.name') . PHP_EOL;
echo 'Object name: ' . $accessor->getString('object_data.name') . PHP_EOL;
echo 'Collection name: ' . $accessor->getString('collection_data.name') . PHP_EOL;

echo PHP_EOL;

echo '=== Examples Complete ===' . PHP_EOL . PHP_EOL;
echo 'Note: These examples use Doctrine Collections and Doctrine ORM.' . PHP_EOL;
echo 'The package works with or without Doctrine installed.' . PHP_EOL;
