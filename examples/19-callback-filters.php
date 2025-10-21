<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\CallbackParameters;
use event4u\DataHelpers\DataMapper\Pipeline\CallbackRegistry;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CallbackFilter;

echo "=== Callback Filters - Custom Transformations ===\n\n";

// ============================================================================
// Example 1: CallbackFilter in Pipeline
// ============================================================================

echo "1️⃣  CallbackFilter in Pipeline\n";
echo str_repeat('-', 80) . "\n";

$source = [
    'user' => [
        'firstName' => 'john',
        'lastName' => 'doe',
        'email' => 'JOHN.DOE@EXAMPLE.COM',
    ],
];

$mapping = [
    'profile.fullName' => '{{ user.firstName }}',
    'profile.email' => '{{ user.email }}',
];

/** @phpstan-ignore-next-line phpstan-error */
$result = DataMapper::pipe([
    new CallbackFilter(function(CallbackParameters $params) {
        // Custom transformation based on key
        if ('fullName' === $params->key && is_string($params->value)) {
            // Capitalize first name
            return ucfirst($params->value);
        }

        if ('email' === $params->key && is_string($params->value)) {
            // Lowercase email
            return strtolower($params->value);
        }

        return $params->value;
    }),
])->map($source, [], $mapping);

echo "Source:\n";
echo json_encode($source, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\nResult:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ============================================================================
// Example 2: CallbackRegistry with Template Expressions
// ============================================================================

echo "2️⃣  CallbackRegistry with Template Expressions\n";
echo str_repeat('-', 80) . "\n";

// Register custom callbacks
CallbackRegistry::register('slugify', function(CallbackParameters $params) {
    if (!is_string($params->value)) {
        return $params->value;
    }

    // Convert to lowercase and replace spaces with hyphens
    return strtolower(str_replace(' ', '-', $params->value));
});

CallbackRegistry::register('initials', function(CallbackParameters $params) {
    if (!is_string($params->value)) {
        return $params->value;
    }

    // Get initials from name
    $parts = explode(' ', $params->value);
    return strtoupper(implode('', array_map(fn(string $p): string => $p[0] ?? '', $parts)));
});

$source = [
    'article' => [
        'title' => 'Hello World Example',
        'author' => 'John Doe',
    ],
];

$template = [
    'post' => [
        'slug' => '{{ article.title | callback:slugify }}',
        'authorInitials' => '{{ article.author | callback:initials }}',
    ],
];

/** @phpstan-ignore-next-line phpstan-error */
$result = DataMapper::mapFromTemplate($template, ['article' => $source['article']]);

echo "Source:\n";
echo json_encode($source, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\nTemplate:\n";
echo json_encode($template, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\nResult:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ============================================================================
// Example 3: Access to Full Context
// ============================================================================

echo "3️⃣  Access to Full Context in Callback\n";
echo str_repeat('-', 80) . "\n";

$source = [
    'order' => [
        'items' => [
            ['name' => 'Product A', 'price' => 100],
            ['name' => 'Product B', 'price' => 200],
        ],
        'discount' => 10, // 10% discount
    ],
];

$mapping = [
    'invoice.items.*.name' => '{{ order.items.*.name }}',
    'invoice.items.*.price' => '{{ order.items.*.price }}',
];

/** @phpstan-ignore-next-line phpstan-error */
$result = DataMapper::pipe([
    new CallbackFilter(function(CallbackParameters $params) {
        // Apply discount to prices
        if ('price' === $params->key && is_numeric($params->value)) {
            // Access source data to get discount
            $discount = 0;
            if (is_array($params->source) && isset($params->source['order']) && is_array(
                $params->source['order']
            ) && isset($params->source['order']['discount'])) {
                $discount = $params->source['order']['discount'];
            }
            $discountedPrice = (float)$params->value * (1 - (float)$discount / 100);

            return round($discountedPrice, 2);
        }

        return $params->value;
    }),
])->map($source, [], $mapping);

echo "Source:\n";
echo json_encode($source, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\nResult (with 10% discount applied):\n";
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ============================================================================
// Example 4: Conditional Skipping with __skip__
// ============================================================================

echo "4️⃣  Conditional Skipping with __skip__\n";
echo str_repeat('-', 80) . "\n";

$source = [
    'users' => [
        ['name' => 'Alice', 'age' => 25, 'active' => true],
        ['name' => 'Bob', 'age' => 17, 'active' => false],
        ['name' => 'Charlie', 'age' => 30, 'active' => true],
    ],
];

$mapping = [
    'activeAdults.*.name' => '{{ users.*.name }}',
    'activeAdults.*.age' => '{{ users.*.age }}',
];

/** @phpstan-ignore-next-line phpstan-error */
$result = DataMapper::pipe([
    new CallbackFilter(function(CallbackParameters $params) {
        // Skip users under 18 or inactive
        if ('activeAdults.*.age' === $params->keyPath) {
            $age = $params->value;
            if (!is_numeric($age) || 18 > $age) {
                return '__skip__';
            }
        }

        return $params->value;
    }),
])->map($source, [], $mapping);

echo "Source:\n";
echo json_encode($source, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\nResult (only active adults):\n";
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ============================================================================
// Example 5: Multiple Callbacks in Chain
// ============================================================================

echo "5️⃣  Multiple Callbacks in Chain\n";
echo str_repeat('-', 80) . "\n";

CallbackRegistry::register('sanitize', function(CallbackParameters $params) {
    if (!is_string($params->value)) {
        return $params->value;
    }

    // Remove HTML tags and trim
    return trim(strip_tags($params->value));
});

CallbackRegistry::register('truncate', function(CallbackParameters $params) {
    if (!is_string($params->value)) {
        return $params->value;
    }

    // Truncate to 20 characters
    return strlen($params->value) > 20 ? substr($params->value, 0, 20) . '...' : $params->value;
});

$source = [
    'post' => [
        'content' => '<p>This is a very long post content with HTML tags that needs to be sanitized and truncated.</p>',
    ],
];

$template = [
    'preview' => '{{ post.content | callback:sanitize | callback:truncate }}',
];

/** @phpstan-ignore-next-line phpstan-error */
$result = DataMapper::mapFromTemplate($template, ['post' => $source['post']]);

echo "Source:\n";
echo json_encode($source, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\nResult (sanitized and truncated):\n";
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ============================================================================
// Example 6: Error Handling
// ============================================================================

echo "6️⃣  Error Handling in Callbacks\n";
echo str_repeat('-', 80) . "\n";

use event4u\DataHelpers\DataMapper\MapperExceptions;

MapperExceptions::setCollectExceptionsEnabled(true);

CallbackRegistry::register('divide', function(CallbackParameters $params): int|float {
    if (!is_numeric($params->value)) {
        throw new RuntimeException('Value must be numeric');
    }

    // Divide by 2
    return $params->value / 2;
});

$source = [
    'data' => [
        'value1' => 100,
        'value2' => 'not a number',
    ],
];

$template = [
    'result1' => '{{ data.value1 | callback:divide }}',
    'result2' => '{{ data.value2 | callback:divide }}',
];

try {
    /** @phpstan-ignore-next-line phpstan-error */
    $result = DataMapper::mapFromTemplate($template, ['data' => $source['data']]);

    echo "Result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $throwable) {
    echo "❌  Exception caught: " . $throwable->getMessage() . "\n";
}

echo "\n";

// ============================================================================
// Summary
// ============================================================================

echo "✅  Callback Filters Summary:\n";
echo "   • CallbackFilter: Use closures in pipeline for custom transformations\n";
echo "   • CallbackRegistry: Register named callbacks for template expressions\n";
echo "   • CallbackParameters: Access full context (source, target, key, keyPath, value)\n";
echo "   • Return '__skip__': Skip values conditionally\n";
echo "   • Error Handling: Exceptions are collected and thrown at the end\n";
echo "   • Chainable: Combine multiple callbacks for complex transformations\n";

