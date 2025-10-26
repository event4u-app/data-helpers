<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter;
use event4u\DataHelpers\DataFilter\Operators\AbstractOperator;
use event4u\DataHelpers\DataFilter\Operators\OperatorContext;
use event4u\DataHelpers\DataFilter\Operators\OperatorRegistry;
use Illuminate\Support\Collection;

describe('DataFilter', function(): void {
    it('filters array with where condition', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 75],
        ];

        $result = DataFilter::query($products)
            ->where('price', '>', 100)
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(1);
        expect($values[0]['name'])->toBe('Laptop');
    });

    it('filters with between condition', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Item A', 'price' => 10],
            ['id' => 2, 'name' => 'Item B', 'price' => 50],
            ['id' => 3, 'name' => 'Item C', 'price' => 100],
            ['id' => 4, 'name' => 'Item D', 'price' => 150],
            ['id' => 5, 'name' => 'Item E', 'price' => 200],
        ];

        $result = DataFilter::query($products)
            ->between('price', 50, 150)
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(3);
        expect($values[0]['price'])->toBe(50);
        expect($values[1]['price'])->toBe(100);
        expect($values[2]['price'])->toBe(150);
    });

    it('filters with whereIn condition', function(): void {
        $users = [
            ['id' => 1, 'name' => 'Alice', 'role' => 'admin'],
            ['id' => 2, 'name' => 'Bob', 'role' => 'user'],
            ['id' => 3, 'name' => 'Charlie', 'role' => 'moderator'],
            ['id' => 4, 'name' => 'David', 'role' => 'user'],
        ];

        $result = DataFilter::query($users)
            ->whereIn('role', ['admin', 'moderator'])
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(2);
        expect($values[0]['name'])->toBe('Alice');
        expect($values[1]['name'])->toBe('Charlie');
    });

    it('filters with whereNull condition', function(): void {
        $data = [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
            ['id' => 2, 'name' => 'Bob', 'email' => null],
            ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com'],
        ];

        $result = DataFilter::query($data)
            ->whereNull('email')
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(1);
        expect($values[0]['name'])->toBe('Bob');
    });

    it('sorts with orderBy', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 75],
        ];

        $result = DataFilter::query($products)
            ->orderBy('price', 'ASC')
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values[0]['name'])->toBe('Mouse');
        expect($values[1]['name'])->toBe('Keyboard');
        expect($values[2]['name'])->toBe('Laptop');
    });

    it('combines multiple conditions', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200, 'stock' => 5],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25, 'stock' => 50],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 75, 'stock' => 30],
            ['id' => 4, 'name' => 'Monitor', 'price' => 400, 'stock' => 8],
        ];

        $result = DataFilter::query($products)
            ->where('price', '>', 50)
            ->where('stock', '<', 20)
            ->orderBy('price', 'DESC')
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(2);
        expect($values[0]['name'])->toBe('Laptop');
        expect($values[1]['name'])->toBe('Monitor');
    });

    it('applies limit and offset', function(): void {
        $items = [];
        for ($i = 1; 10 >= $i; $i++) {
            $items[] = ['id' => $i, 'name' => 'Item ' . $i];
        }

        $result = DataFilter::query($items)
            ->offset(2)
            ->limit(3)
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(3);
        expect($values[0]['id'])->toBe(3);
        expect($values[1]['id'])->toBe(4);
        expect($values[2]['id'])->toBe(5);
    });

    it('filters with like pattern', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop Pro'],
            ['id' => 2, 'name' => 'Laptop Air'],
            ['id' => 3, 'name' => 'Desktop PC'],
            ['id' => 4, 'name' => 'Laptop Mini'],
        ];

        $result = DataFilter::query($products)
            ->like('name', 'Laptop%')
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(3);
        expect($values[0]['name'])->toBe('Laptop Pro');
        expect($values[1]['name'])->toBe('Laptop Air');
        expect($values[2]['name'])->toBe('Laptop Mini');
    });

    it('removes duplicates with distinct', function(): void {
        $data = [
            ['id' => 1, 'category' => 'Electronics'],
            ['id' => 2, 'category' => 'Furniture'],
            ['id' => 3, 'category' => 'Electronics'],
            ['id' => 4, 'category' => 'Clothing'],
        ];

        $result = DataFilter::query($data)
            ->distinct('category')
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        expect($result)->toHaveCount(3);
    });

    it('works with nested field paths', function(): void {
        $users = [
            ['id' => 1, 'name' => 'Alice', 'address' => ['city' => 'Berlin']],
            ['id' => 2, 'name' => 'Bob', 'address' => ['city' => 'Munich']],
            ['id' => 3, 'name' => 'Charlie', 'address' => ['city' => 'Berlin']],
        ];

        $result = DataFilter::query($users)
            ->where('address.city', 'Berlin')
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(2);
        expect($values[0]['name'])->toBe('Alice');
        expect($values[1]['name'])->toBe('Charlie');
    });

    it('returns first result with first()', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 75],
        ];

        $result = DataFilter::query($products)
            ->where('price', '>', 100)
            ->first();

        expect($result)->toBeArray();
        assert(is_array($result));
        expect($result['name'])->toBe('Laptop');
    });

    it('returns null when first() finds no results', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25],
        ];

        $result = DataFilter::query($products)
            ->where('price', '>', 5000)
            ->first();

        expect($result)->toBeNull();
    });

    it('counts results with count()', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 75],
            ['id' => 4, 'name' => 'Monitor', 'price' => 400],
        ];

        $count = DataFilter::query($products)
            ->where('price', '>', 100)
            ->count();

        expect($count)->toBe(2);
    });

    it('supports custom operators with addOperator()', function(): void {
        // Create custom operator
        $customOp = new class extends AbstractOperator {
            public function getName(): string
            {
                return 'TEST_EQUALS';
            }

            protected function getConfigSchema(): array
            {
                return ['value'];
            }

            protected function handle(mixed $actualValue, OperatorContext $context): bool
            {
                return $context->getValue('value') === $actualValue;
            }
        };

        OperatorRegistry::register($customOp);

        $products = [
            ['id' => 1, 'name' => 'Laptop'],
            ['id' => 2, 'name' => 'Mouse'],
        ];

        $result = DataFilter::query($products)
            ->addOperator('TEST_EQUALS', ['name' => 'Laptop'])
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(1);
        expect($values[0]['name'])->toBe('Laptop');
    });
});

describe('DataFilter - Edge Cases', function(): void {
    it('handles empty array', function(): void {
        $result = DataFilter::query([])
            ->where('price', '>', 100)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });

    it('handles empty result after filtering', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 50],
        ];

        $result = DataFilter::query($products)
            ->where('price', '>', 1000)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });

    it('handles null values in data', function(): void {
        $data = [
            ['id' => 1, 'name' => 'Alice', 'email' => null],
            ['id' => 2, 'name' => null, 'email' => 'bob@example.com'],
            ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com'],
        ];

        $result = DataFilter::query($data)
            ->whereNotNull('name')
            ->whereNotNull('email')
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(1);
        expect($values[0]['name'])->toBe('Charlie');
    });

    it('handles missing fields gracefully', function(): void {
        $data = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
            ['id' => 3, 'name' => 'Charlie'],
        ];

        $result = DataFilter::query($data)
            ->whereNotNull('email')
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(1);
        expect($values[0]['name'])->toBe('Bob');
    });

    it('handles deeply nested paths', function(): void {
        $data = [
            ['id' => 1, 'user' => ['profile' => ['address' => ['city' => 'Berlin']]]],
            ['id' => 2, 'user' => ['profile' => ['address' => ['city' => 'Munich']]]],
            ['id' => 3, 'user' => ['profile' => ['address' => ['city' => 'Berlin']]]],
        ];

        $result = DataFilter::query($data)
            ->where('user.profile.address.city', 'Berlin')
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(2);
    });

    it('handles limit of 0', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop'],
            ['id' => 2, 'name' => 'Mouse'],
        ];

        $result = DataFilter::query($products)
            ->limit(0)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });

    it('handles offset larger than array size', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop'],
            ['id' => 2, 'name' => 'Mouse'],
        ];

        $result = DataFilter::query($products)
            ->offset(10)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });

    it('handles negative limit gracefully', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop'],
            ['id' => 2, 'name' => 'Mouse'],
        ];

        $result = DataFilter::query($products)
            ->limit(-1)
            ->get();

        expect($result)->toBeArray();
        // Negative limit returns all items (PHP array_slice behavior)
        expect($result)->toHaveCount(2);
    });

    it('handles multiple ORDER BY clauses', function(): void {
        $products = [
            ['id' => 1, 'category' => 'A', 'price' => 100],
            ['id' => 2, 'category' => 'B', 'price' => 50],
            ['id' => 3, 'category' => 'A', 'price' => 75],
            ['id' => 4, 'category' => 'B', 'price' => 200],
        ];

        $result = DataFilter::query($products)
            ->orderBy('category', 'ASC')
            ->orderBy('price', 'DESC')
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        // Multiple ORDER BY: last one wins (price DESC)
        expect($values[0]['price'])->toBe(200);
        expect($values[1]['price'])->toBe(100);
        expect($values[2]['price'])->toBe(75);
        expect($values[3]['price'])->toBe(50);
    });

    it('handles BETWEEN with equal min and max', function(): void {
        $products = [
            ['id' => 1, 'price' => 50],
            ['id' => 2, 'price' => 100],
            ['id' => 3, 'price' => 150],
        ];

        $result = DataFilter::query($products)
            ->between('price', 100, 100)
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(1);
        expect($values[0]['price'])->toBe(100);
    });

    it('handles NOT BETWEEN', function(): void {
        $products = [
            ['id' => 1, 'price' => 50],
            ['id' => 2, 'price' => 100],
            ['id' => 3, 'price' => 150],
            ['id' => 4, 'price' => 200],
        ];

        $result = DataFilter::query($products)
            ->notBetween('price', 100, 150)
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(2);
        expect($values[0]['price'])->toBe(50);
        expect($values[1]['price'])->toBe(200);
    });

    it('handles WHERE NOT IN', function(): void {
        $products = [
            ['id' => 1, 'category' => 'A'],
            ['id' => 2, 'category' => 'B'],
            ['id' => 3, 'category' => 'C'],
            ['id' => 4, 'category' => 'D'],
        ];

        $result = DataFilter::query($products)
            ->whereNotIn('category', ['B', 'D'])
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(2);
        expect($values[0]['category'])->toBe('A');
        expect($values[1]['category'])->toBe('C');
    });

    it('handles empty WHERE IN array', function(): void {
        $products = [
            ['id' => 1, 'category' => 'A'],
            ['id' => 2, 'category' => 'B'],
        ];

        $result = DataFilter::query($products)
            ->whereIn('category', [])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });

    it('handles LIKE with no wildcards', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop'],
            ['id' => 2, 'name' => 'Mouse'],
        ];

        $result = DataFilter::query($products)
            ->like('name', 'Laptop')
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(1);
        expect($values[0]['name'])->toBe('Laptop');
    });

    it('handles LIKE with middle wildcard', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop Pro'],
            ['id' => 2, 'name' => 'Laptop Air'],
            ['id' => 3, 'name' => 'Desktop PC'],
        ];

        $result = DataFilter::query($products)
            ->like('name', '%top%')
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(3); // Laptop Pro, Laptop Air, Desktop
    });

    it('handles case-sensitive comparisons', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop'],
            ['id' => 2, 'name' => 'laptop'],
            ['id' => 3, 'name' => 'LAPTOP'],
        ];

        $result = DataFilter::query($products)
            ->where('name', '=', 'Laptop')
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(1);
        expect($values[0]['name'])->toBe('Laptop');
    });

    it('handles numeric string comparisons', function(): void {
        $products = [
            ['id' => 1, 'code' => '100'],
            ['id' => 2, 'code' => '50'],
            ['id' => 3, 'code' => '200'],
        ];

        $result = DataFilter::query($products)
            ->where('code', '>', '100')
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(1);
        expect($values[0]['code'])->toBe('200');
    });

    it('handles boolean values', function(): void {
        $products = [
            ['id' => 1, 'active' => true],
            ['id' => 2, 'active' => false],
            ['id' => 3, 'active' => true],
        ];

        $result = DataFilter::query($products)
            ->where('active', '=', true)
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(2);
    });

    it('handles mixed data types', function(): void {
        $data = [
            ['id' => 1, 'value' => 100],
            ['id' => 2, 'value' => '100'],
            ['id' => 3, 'value' => 100.0],
        ];

        $result = DataFilter::query($data)
            ->where('value', '=', 100)
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        // PHP loose comparison: 100 == '100' == 100.0
        expect($values)->toHaveCount(3);
    });

    it('handles whereIn as alternative to OR conditions', function(): void {
        $products = [
            ['id' => 1, 'category' => 'A', 'price' => 100],
            ['id' => 2, 'category' => 'B', 'price' => 50],
            ['id' => 3, 'category' => 'C', 'price' => 150],
        ];

        $result = DataFilter::query($products)
            ->whereIn('category', ['A', 'C'])
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(2); // A or C
    });

    it('handles exists() alias', function(): void {
        $data = [
            ['id' => 1, 'email' => 'alice@example.com'],
            ['id' => 2, 'email' => null],
            ['id' => 3, 'email' => 'charlie@example.com'],
        ];

        $result = DataFilter::query($data)
            ->exists('email')
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(2);
    });

    it('handles notExists() alias', function(): void {
        $data = [
            ['id' => 1, 'email' => 'alice@example.com'],
            ['id' => 2, 'email' => null],
            ['id' => 3, 'email' => 'charlie@example.com'],
        ];

        $result = DataFilter::query($data)
            ->notExists('email')
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(1);
        expect($values[0]['id'])->toBe(2);
    });

    it('handles distinct with multiple duplicates', function(): void {
        $data = [
            ['id' => 1, 'category' => 'A'],
            ['id' => 2, 'category' => 'A'],
            ['id' => 3, 'category' => 'A'],
            ['id' => 4, 'category' => 'B'],
            ['id' => 5, 'category' => 'B'],
        ];

        $result = DataFilter::query($data)
            ->distinct('category')
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
    });

    it('handles first() with empty result', function(): void {
        $result = DataFilter::query([])
            ->first();

        expect($result)->toBeNull();
    });

    it('handles count() with empty result', function(): void {
        $count = DataFilter::query([])
            ->count();

        expect($count)->toBe(0);
    });

    it('handles chaining all operators', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop Pro', 'category' => 'Electronics', 'price' => 1200, 'stock' => 5],
            ['id' => 2, 'name' => 'Mouse', 'category' => 'Electronics', 'price' => 25, 'stock' => 50],
            ['id' => 3, 'name' => 'Desk', 'category' => 'Furniture', 'price' => 300, 'stock' => 10],
            ['id' => 4, 'name' => 'Chair', 'category' => 'Furniture', 'price' => 150, 'stock' => 20],
            ['id' => 5, 'name' => 'Monitor', 'category' => 'Electronics', 'price' => 400, 'stock' => 15],
            ['id' => 6, 'name' => 'Keyboard', 'category' => 'Electronics', 'price' => 80, 'stock' => 30],
        ];

        $result = DataFilter::query($products)
            ->where('category', '=', 'Electronics')
            ->where('price', '>', 50)
            ->orderBy('price', 'DESC')
            ->offset(1)
            ->limit(2)
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(2);
        expect($values[0]['name'])->toBe('Monitor'); // 400
        expect($values[1]['name'])->toBe('Keyboard'); // 80
    });

    it('handles null values in SELECT', function(): void {
        $data = [
            ['id' => 1, 'name' => 'John', 'email' => null],
        ];

        $result = DataFilter::query($data)
            ->only(['name', 'email'])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0])->toBe(['name' => 'John', 'email' => null]);
    });

    it('handles numeric keys', function(): void {
        $data = [
            [0 => 'value0', 1 => 'value1', 2 => 'value2'],
        ];

        $result = DataFilter::query($data)
            ->only([0, 1])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0])->toBe([0 => 'value0', 1 => 'value1']);
    });

    it('handles deeply nested transformations', function(): void {
        $data = [
            ['user' => ['profile' => ['name' => 'john']]],
        ];

        $result = DataFilter::query($data)
            ->map(fn($item): array => ['userName' => strtoupper($item['user']['profile']['name'])])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0])->toBe(['userName' => 'JOHN']);
    });
});

describe('DataFilter - Input/Output Formats', function(): void {
    it('accepts Collection as input', function(): void {
        if (!function_exists('collect')) {
            $this->markTestSkipped('Laravel collect() function not available');
        }

        $collection = collect([
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25],
        ]);

        $result = DataFilter::query($collection)
            ->where('price', '>', 100)
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(1);
        expect($values[0]['name'])->toBe('Laptop');
    })->skip(!function_exists('collect'), 'Laravel not available');

    it('accepts JSON string as input', function(): void {
        $json = json_encode([
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25],
        ]);

        $result = DataFilter::query($json)
            ->where('price', '>', 100)
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        $values = array_values($result);
        expect($values)->toHaveCount(1);
        expect($values[0]['name'])->toBe('Laptop');
    });

    it('accepts XML string as input', function(): void {
        $xml = '<?xml version="1.0"?><root><item0><id>1</id><name>Laptop</name><price>1200</price></item0><item1><id>2</id><name>Mouse</name><price>25</price></item1></root>';

        $result = DataFilter::query($xml)
            ->where('price', '>', 100)
            ->get();

        expect($result)->toBeArray();
        assert(is_array($result));
        expect($result)->not->toBeEmpty();
    });

    it('outputs as JSON', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25],
        ];

        $result = DataFilter::query($products)
            ->where('price', '>', 100)
            ->format('json')
            ->get();

        expect($result)->toBeString();
        assert(is_string($result));
        $decoded = json_decode($result, true);
        expect($decoded)->toBeArray();
        assert(is_array($decoded));
        $values = array_values($decoded);
        expect($values)->toHaveCount(1);
        expect($values[0]['name'])->toBe('Laptop');
    });

    it('outputs as Collection', function(): void {
        if (!class_exists(Collection::class)) {
            $this->markTestSkipped('Laravel Collection not available');
        }

        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25],
        ];

        $result = DataFilter::query($products)
            ->where('price', '>', 100)
            ->format('collection')
            ->get();

        expect($result)->toBeInstanceOf(Collection::class);
        assert($result instanceof Collection);
        expect($result->count())->toBe(1);
        expect($result->first()['name'])->toBe('Laptop');
    })->skip(!class_exists(Collection::class), 'Laravel not available');

    it('outputs as XML', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
        ];

        $result = DataFilter::query($products)
            ->format('xml')
            ->get();

        expect($result)->toBeString();
        expect($result)->toContain('<?xml');
        expect($result)->toContain('<root>');
    });

    it('outputs in original format when format is "original"', function(): void {
        $json = json_encode([
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25],
        ]);

        $result = DataFilter::query($json)
            ->where('price', '>', 100)
            ->format('original')
            ->get();

        expect($result)->toBeString();
        assert(is_string($result));
        $decoded = json_decode($result, true);
        expect($decoded)->toBeArray();
    });

    it('handles invalid JSON gracefully', function(): void {
        $invalidJson = '{invalid json}';

        $result = DataFilter::query($invalidJson)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });

    it('handles invalid XML gracefully', function(): void {
        $invalidXml = '<invalid><xml';

        $result = DataFilter::query($invalidXml)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });

    it('handles non-array non-string input', function(): void {
        $result = DataFilter::query(123)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });
});

describe('DataFilter - SELECT Operator', function(): void {
    it('selects only specified fields', function(): void {
        $data = [
            ['id' => 1, 'name' => 'John', 'age' => 25, 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane', 'age' => 30, 'email' => 'jane@example.com'],
        ];

        $result = DataFilter::query($data)
            ->only(['name', 'age'])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
        expect($result[0])->toBe(['name' => 'John', 'age' => 25]);
        expect($result[1])->toBe(['name' => 'Jane', 'age' => 30]);
    });

    it('handles empty field list', function(): void {
        $data = [
            ['id' => 1, 'name' => 'John'],
        ];

        $result = DataFilter::query($data)
            ->only([])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0])->toBe([]);
    });

    it('handles non-existent fields', function(): void {
        $data = [
            ['id' => 1, 'name' => 'John'],
        ];

        $result = DataFilter::query($data)
            ->only(['name', 'email', 'phone'])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0])->toBe(['name' => 'John']);
    });

    it('handles non-array items', function(): void {
        $data = ['string1', 'string2'];

        $result = DataFilter::query($data)
            ->only(['field'])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
        expect($result[0])->toBe('string1');
        expect($result[1])->toBe('string2');
    });

    it('preserves array keys', function(): void {
        $data = [
            'first' => ['id' => 1, 'name' => 'John', 'age' => 25],
            'second' => ['id' => 2, 'name' => 'Jane', 'age' => 30],
        ];

        $result = DataFilter::query($data)
            ->only(['name'])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveKey('first');
        expect($result)->toHaveKey('second');
        expect($result['first'])->toBe(['name' => 'John']);
    });

    it('works with chained operators', function(): void {
        $data = [
            ['id' => 1, 'name' => 'John', 'age' => 25, 'active' => true],
            ['id' => 2, 'name' => 'Jane', 'age' => 30, 'active' => false],
            ['id' => 3, 'name' => 'Bob', 'age' => 35, 'active' => true],
        ];

        $result = DataFilter::query($data)
            ->where('active', true)
            ->only(['name', 'age'])
            ->get();

        expect($result)->toBeArray();
        $values = array_values($result);
        expect($values)->toHaveCount(2);
        expect($values[0])->toBe(['name' => 'John', 'age' => 25]);
        expect($values[1])->toBe(['name' => 'Bob', 'age' => 35]);
    });
});

describe('DataFilter - EXCEPT Operator', function(): void {
    it('excludes specified fields', function(): void {
        $data = [
            ['id' => 1, 'name' => 'John', 'age' => 25, 'password' => 'secret'],
            ['id' => 2, 'name' => 'Jane', 'age' => 30, 'password' => 'secret2'],
        ];

        $result = DataFilter::query($data)
            ->except(['password'])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
        expect($result[0])->toBe(['id' => 1, 'name' => 'John', 'age' => 25]);
        expect($result[1])->toBe(['id' => 2, 'name' => 'Jane', 'age' => 30]);
    });

    it('excludes multiple fields', function(): void {
        $data = [
            ['id' => 1, 'name' => 'John', 'age' => 25, 'password' => 'secret', 'token' => 'abc'],
        ];

        $result = DataFilter::query($data)
            ->except(['password', 'token'])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0])->toBe(['id' => 1, 'name' => 'John', 'age' => 25]);
    });

    it('handles empty exclude list', function(): void {
        $data = [
            ['id' => 1, 'name' => 'John'],
        ];

        $result = DataFilter::query($data)
            ->except([])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0])->toBe(['id' => 1, 'name' => 'John']);
    });

    it('handles non-existent fields', function(): void {
        $data = [
            ['id' => 1, 'name' => 'John'],
        ];

        $result = DataFilter::query($data)
            ->except(['password', 'token'])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0])->toBe(['id' => 1, 'name' => 'John']);
    });

    it('handles non-array items', function(): void {
        $data = ['string1', 'string2'];

        $result = DataFilter::query($data)
            ->except(['field'])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
        expect($result[0])->toBe('string1');
        expect($result[1])->toBe('string2');
    });
});

describe('DataFilter - MAP Operator', function(): void {
    it('transforms items with callback', function(): void {
        $data = [
            ['id' => 1, 'name' => 'john'],
            ['id' => 2, 'name' => 'jane'],
        ];

        $result = DataFilter::query($data)
            ->map(fn($item): array => ['id' => $item['id'], 'name' => strtoupper($item['name'])])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
        expect($result[0])->toBe(['id' => 1, 'name' => 'JOHN']);
        expect($result[1])->toBe(['id' => 2, 'name' => 'JANE']);
    });

    it('can add new fields', function(): void {
        $data = [
            ['name' => 'John', 'age' => 25],
        ];

        $result = DataFilter::query($data)
            ->map(fn($item): array => [...$item, 'adult' => 18 <= $item['age']])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0])->toBe(['name' => 'John', 'age' => 25, 'adult' => true]);
    });

    it('can completely transform structure', function(): void {
        $data = [
            ['firstName' => 'John', 'lastName' => 'Doe'],
        ];

        $result = DataFilter::query($data)
            ->map(fn($item): array => ['fullName' => $item['firstName'] . ' ' . $item['lastName']])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0])->toBe(['fullName' => 'John Doe']);
    });

    it('preserves array keys', function(): void {
        $data = [
            'first' => ['name' => 'John'],
            'second' => ['name' => 'Jane'],
        ];

        $result = DataFilter::query($data)
            ->map(fn($item): array => ['name' => strtoupper($item['name'])])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveKey('first');
        expect($result)->toHaveKey('second');
        expect($result['first'])->toBe(['name' => 'JOHN']);
    });

    it('works with chained operators', function(): void {
        $data = [
            ['id' => 1, 'name' => 'john', 'age' => 25],
            ['id' => 2, 'name' => 'jane', 'age' => 30],
        ];

        $result = DataFilter::query($data)
            ->where('age', '>', 20)
            ->map(fn($item): array => ['name' => strtoupper($item['name'])])
            ->get();

        expect($result)->toBeArray();
        $values = array_values($result);
        expect($values)->toHaveCount(2);
        expect($values[0])->toBe(['name' => 'JOHN']);
    });

    it('handles empty data', function(): void {
        $data = [];

        $result = DataFilter::query($data)
            ->map(fn($item): array => ['transformed' => true])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });
});

describe('DataFilter - FILTER Operator', function(): void {
    it('filters items with callback', function(): void {
        $data = [
            ['id' => 1, 'name' => 'John', 'active' => true],
            ['id' => 2, 'name' => 'Jane', 'active' => false],
            ['id' => 3, 'name' => 'Bob', 'active' => true],
        ];

        $result = DataFilter::query($data)
            ->filter(fn($item) => $item['active'])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
        expect($result[0]['name'])->toBe('John');
        expect($result[1]['name'])->toBe('Bob');
    });

    it('reindexes array after filtering', function(): void {
        $data = [
            ['id' => 1, 'active' => false],
            ['id' => 2, 'active' => true],
            ['id' => 3, 'active' => false],
        ];

        $result = DataFilter::query($data)
            ->filter(fn($item) => $item['active'])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect(array_keys($result))->toBe([0]);
        expect($result[0]['id'])->toBe(2);
    });

    it('filters with complex conditions', function(): void {
        $data = [
            ['id' => 1, 'age' => 15, 'score' => 80],
            ['id' => 2, 'age' => 25, 'score' => 90],
            ['id' => 3, 'age' => 30, 'score' => 70],
        ];

        $result = DataFilter::query($data)
            ->filter(fn($item): bool => 18 <= $item['age'] && 80 <= $item['score'])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0]['id'])->toBe(2);
    });

    it('handles empty data', function(): void {
        $data = [];

        $result = DataFilter::query($data)
            ->filter(fn($item): true => true)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });

    it('works with chained operators', function(): void {
        $data = [
            ['id' => 1, 'name' => 'john', 'age' => 25, 'active' => true],
            ['id' => 2, 'name' => 'jane', 'age' => 30, 'active' => false],
            ['id' => 3, 'name' => 'bob', 'age' => 35, 'active' => true],
        ];

        $result = DataFilter::query($data)
            ->filter(fn($item) => $item['active'])
            ->map(fn($item): array => ['name' => strtoupper($item['name']), 'age' => $item['age']])
            ->only(['name'])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
        expect($result[0])->toBe(['name' => 'JOHN']);
        expect($result[1])->toBe(['name' => 'BOB']);
    });
});

describe('DataFilter - make() Factory Method', function(): void {
    it('creates instance with make()', function(): void {
        $data = [
            ['id' => 1, 'name' => 'John'],
        ];

        $result = DataFilter::make($data)
            ->where('id', 1)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
    });

    it('is equivalent to query()', function(): void {
        $data = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];

        $result1 = DataFilter::make($data)->where('id', 1)->get();
        $result2 = DataFilter::query($data)->where('id', 1)->get();

        expect($result1)->toBe($result2);
    });
});

describe('DataFilter - Combined Operators', function(): void {
    it('combines SELECT and EXCEPT', function(): void {
        $data = [
            ['id' => 1, 'name' => 'John', 'age' => 25, 'password' => 'secret', 'email' => 'john@example.com'],
        ];

        $result = DataFilter::query($data)
            ->only(['name', 'age', 'email', 'password'])
            ->except(['password'])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0])->toBe(['name' => 'John', 'age' => 25, 'email' => 'john@example.com']);
    });

    it('combines WHERE, MAP, and FILTER', function(): void {
        $data = [
            ['id' => 1, 'name' => 'john', 'age' => 15],
            ['id' => 2, 'name' => 'jane', 'age' => 25],
            ['id' => 3, 'name' => 'bob', 'age' => 30],
        ];

        $result = DataFilter::query($data)
            ->where('age', '>=', 18)
            ->map(fn($item): array => [...$item, 'name' => strtoupper($item['name']), 'adult' => true])
            ->filter(fn($item): bool => 30 > $item['age'])
            ->only(['name', 'age'])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0])->toBe(['name' => 'JANE', 'age' => 25]);
    });

    it('handles complex pipeline', function(): void {
        $data = [
            ['id' => 1, 'firstName' => 'john', 'lastName' => 'doe', 'age' => 25, 'active' => true],
            ['id' => 2, 'firstName' => 'jane', 'lastName' => 'smith', 'age' => 30, 'active' => false],
            ['id' => 3, 'firstName' => 'bob', 'lastName' => 'jones', 'age' => 35, 'active' => true],
        ];

        $result = DataFilter::query($data)
            ->filter(fn($item) => $item['active'])
            ->map(fn($item): array => [
                'id' => $item['id'],
                'fullName' => ucfirst($item['firstName']) . ' ' . ucfirst($item['lastName']),
                'age' => $item['age'],
            ])
            ->where('age', '>', 25)
            ->only(['fullName', 'age'])
            ->get();

        expect($result)->toBeArray();
        $values = array_values($result);
        expect($values)->toHaveCount(1);
        expect($values[0])->toBe(['fullName' => 'Bob Jones', 'age' => 35]);
    });
});
