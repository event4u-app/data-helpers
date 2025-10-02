<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMutator;
use event4u\DataHelpers\DataMapper;

echo "=== Symfony/Doctrine Support Examples ===\n\n";

// Example 1: Working with Doctrine Collections
echo "1. Doctrine Collections\n";
echo "------------------------\n";

if (class_exists(\Doctrine\Common\Collections\ArrayCollection::class)) {
    $collection = new \Doctrine\Common\Collections\ArrayCollection([
        'users' => [
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'Jane', 'email' => 'jane@example.com'],
        ],
    ]);

    $accessor = new DataAccessor($collection);
    $names = $accessor->get('users.*.name');

    echo "Names from Doctrine Collection: " . json_encode($names) . "\n";
} else {
    echo "Doctrine Collections not installed. Using polyfill.\n";
    echo "Install with: composer require doctrine/collections\n";
}

echo "\n";

// Example 2: Working with Doctrine Entities (Simulated)
echo "2. Doctrine Entities (Simulated)\n";
echo "---------------------------------\n";

// Simulated Doctrine Entity
class Product
{
    private ?int $id = null;
    private string $name = '';
    private float $price = 0.0;
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

    public function getTags(): array
    {
        return $this->tags;
    }

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
echo "Product name: " . $accessor->get('name') . "\n";
echo "Product price: " . $accessor->get('price') . "\n";
echo "Product tags: " . json_encode($accessor->get('tags')) . "\n";

echo "\n";

// Example 3: Mapping between structures
echo "3. Mapping with Doctrine Collections\n";
echo "-------------------------------------\n";

$sourceData = [
    'products' => [
        ['product_name' => 'Widget', 'product_price' => 29.99],
        ['product_name' => 'Gadget', 'product_price' => 49.99],
    ],
];

$mapping = [
    'products.*.product_name' => 'items.*.name',
    'products.*.product_price' => 'items.*.price',
];

$mapper = new DataMapper();
$result = $mapper->map($sourceData, $mapping, []);

echo "Mapped data:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

echo "\n";

// Example 4: Framework Detection
echo "4. Framework Detection\n";
echo "----------------------\n";

use event4u\DataHelpers\Support\CollectionHelper;
use event4u\DataHelpers\Support\EntityHelper;

echo "Laravel Collections available: " . (CollectionHelper::isLaravelCollection(new \stdClass()) ? 'Yes' : 'No') . "\n";
echo "Doctrine Collections available: " . (class_exists(\Doctrine\Common\Collections\ArrayCollection::class) ? 'Yes' : 'No') . "\n";
echo "Doctrine ORM available: " . (class_exists(\Doctrine\ORM\EntityManager::class) ? 'Yes' : 'No') . "\n";

echo "\n";

// Example 5: Working with mixed data
echo "5. Mixed Data Sources\n";
echo "---------------------\n";

$mixedData = [
    'array_data' => ['name' => 'Array Item'],
    'object_data' => (object)['name' => 'Object Item'],
];

if (class_exists(\Doctrine\Common\Collections\ArrayCollection::class)) {
    $mixedData['collection_data'] = new \Doctrine\Common\Collections\ArrayCollection(['name' => 'Collection Item']);
}

$accessor = new DataAccessor($mixedData);
echo "Array name: " . $accessor->get('array_data.name') . "\n";
echo "Object name: " . $accessor->get('object_data.name') . "\n";

if (isset($mixedData['collection_data'])) {
    echo "Collection name: " . $accessor->get('collection_data.name') . "\n";
}

echo "\n";

echo "=== Examples Complete ===\n";
echo "\nNote: Install Doctrine packages for full functionality:\n";
echo "  composer require doctrine/collections\n";
echo "  composer require doctrine/orm\n";

